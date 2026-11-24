<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'
import { HttpError, request } from './api'
import { permissionLabels } from './board'
import type { Board, BoardMember, BoardRole, CustomRole, MemberRole, Permission } from './board'

const props = defineProps<{ board: Board; role: BoardRole }>()
const emit = defineEmits<{ accessChanged: [] }>()
const dialog = ref<HTMLDialogElement>()
const open = ref(false)
const members = ref<BoardMember[]>([])
const loading = ref(false)
const busy = ref(false)
const loadError = ref('')
const actionError = ref('')
const status = ref('')
const email = ref('')
const newMemberRole = ref<MemberRole>('editor')
const roles = ref<CustomRole[]>([])
const roleEditor = ref<'new' | number | null>(null)
const roleName = ref('')
const rolePermissions = ref<Permission[]>([])
const options = computed(() => [
  { key: 'editor', name: 'Редактор' },
  { key: 'viewer', name: 'Наблюдатель' },
  ...roles.value,
])
function roleLabel(role: BoardRole) {
  return role === 'owner'
    ? 'Владелец'
    : (options.value.find((item) => item.key === role)?.name ?? 'Пользовательская роль')
}
let loadVersion = 0
let panelVersion = 0
const rolesPath = () => '/api/boards/' + props.board.id + '/roles'
const path = () => '/api/boards/' + props.board.id + '/members'

function errorMessage(error: unknown) {
  return error instanceof Error ? error.message : 'Не удалось выполнить запрос.'
}

async function loadMembers() {
  const seq = ++loadVersion
  loading.value = true
  loadError.value = ''
  try {
    const [list, custom] = await Promise.all([
      request<BoardMember[]>(path()),
      request<CustomRole[]>(rolesPath()),
    ])
    if (seq === loadVersion) {
      members.value = list
      roles.value = custom
    }
  } catch (error) {
    if (seq !== loadVersion) return
    loadError.value = errorMessage(error)
    if (error instanceof HttpError && error.status === 404) {
      members.value = []
      emit('accessChanged')
    }
  } finally {
    if (seq === loadVersion) loading.value = false
  }
}

function show() {
  dialog.value?.showModal()
  open.value = true
  void loadMembers()
}

function reset() {
  panelVersion++
  loadVersion++
  open.value = false
  members.value = []
  roles.value = []
  roleEditor.value = null
  email.value = ''
  newMemberRole.value = 'editor'
  status.value = ''
  actionError.value = ''
}

watch(
  () => props.board,
  () => {
    if (open.value) void loadMembers()
  },
)
onUnmounted(reset)

async function changeTeam(action: () => Promise<unknown>, success: string) {
  if (!open.value || props.role !== 'owner' || busy.value) return false
  const seq = panelVersion
  busy.value = true
  status.value = ''
  actionError.value = ''
  try {
    await action()
    if (open.value) await loadMembers()
    if (seq !== panelVersion) return false
    status.value = success
    return true
  } catch (error) {
    if (seq !== panelVersion) return false
    actionError.value = errorMessage(error)
    if (error instanceof HttpError && (error.status === 403 || error.status === 404)) {
      emit('accessChanged')
      void loadMembers()
    }
    return false
  } finally {
    busy.value = false
  }
}

async function add() {
  const address = email.value.trim()
  if (!address) return
  const saved = await changeTeam(
    () => request(path(), 'POST', { email: address, role: newMemberRole.value }),
    'Участник добавлен.',
  )
  if (saved) email.value = ''
}

async function update(member: BoardMember, event: Event) {
  const field = event.target as HTMLSelectElement
  const next = field.value
  if (!options.value.some((item) => item.key === next)) return
  const saved = await changeTeam(
    () => request(path() + '/' + member.id, 'PATCH', { role: next }),
    'Роль обновлена.',
  )
  if (!saved) field.value = member.role
}

function remove(member: BoardMember) {
  void changeTeam(() => request(path() + '/' + member.id, 'DELETE'), 'Доступ участника отозван.')
}
function editRole(role?: CustomRole) {
  roleEditor.value = role?.id ?? 'new'
  roleName.value = role?.name ?? ''
  rolePermissions.value = [...(role?.permissions ?? [])]
  actionError.value = ''
}

async function saveRole() {
  const id = roleEditor.value
  if (id === null || !roleName.value.trim()) return
  const url = id === 'new' ? rolesPath() : rolesPath() + '/' + id
  const saved = await changeTeam(
    () =>
      request(url, id === 'new' ? 'POST' : 'PATCH', {
        name: roleName.value.trim(),
        permissions: rolePermissions.value,
      }),
    'Роль сохранена.',
  )
  if (saved) roleEditor.value = null
}

async function deleteRole(role: CustomRole) {
  const saved = await changeTeam(
    () => request(rolesPath() + '/' + role.id, 'DELETE'),
    'Роль удалена.',
  )
  if (saved) {
    if (newMemberRole.value === role.key) newMemberRole.value = 'editor'
    if (roleEditor.value === role.id) roleEditor.value = null
  }
}
</script>

<template>
  <button class="secondary team-button" @click="show">Команда</button>
  <dialog ref="dialog" class="team-dialog" aria-labelledby="board-team-title" @close="reset">
    <div class="team-heading">
      <h2 id="board-team-title">Команда доски</h2>
      <button
        type="button"
        class="secondary"
        aria-label="Закрыть команду"
        autofocus
        @click="dialog?.close()"
      >
        ×
      </button>
    </div>

    <div class="team-roster" :aria-busy="loading">
      <p v-if="loading && !members.length" role="status">Загружаем участников…</p>
      <p v-else-if="!loadError && !members.length">Участников пока нет.</p>
      <ul v-if="members.length" class="team-members" aria-label="Участники доски">
        <li v-for="member in members" :key="member.id" class="team-member">
          <div>
            <strong>{{ member.name }}</strong>
            <p>{{ member.email }}</p>
          </div>
          <div v-if="props.role === 'owner' && member.role !== 'owner'" class="team-controls">
            <select
              :value="member.role"
              :aria-label="'Роль для ' + member.email"
              :disabled="busy"
              @change="update(member, $event)"
            >
              <option v-for="item in options" :key="item.key" :value="item.key">
                {{ item.name }}
              </option>
            </select>
            <button
              class="team-remove"
              :aria-label="'Удалить ' + member.email + ' из команды'"
              :disabled="busy"
              @click="remove(member)"
            >
              Удалить
            </button>
          </div>
          <span v-else class="team-role">{{ roleLabel(member.role) }}</span>
        </li>
      </ul>
    </div>
    <div v-if="loadError" class="error" role="alert">
      <p>{{ loadError }}</p>
      <button class="text" :disabled="loading" @click="loadMembers">Повторить загрузку</button>
    </div>

    <form v-if="props.role === 'owner'" class="team-form" @submit.prevent="add">
      <h3>Добавить участника</h3>
      <p>Введите email зарегистрированного пользователя.</p>
      <div class="team-fields">
        <label>
          Email участника
          <input v-model="email" type="email" required maxlength="255" :disabled="busy" />
        </label>
        <label>
          Роль нового участника
          <select v-model="newMemberRole" :disabled="busy">
            <option v-for="item in options" :key="item.key" :value="item.key">
              {{ item.name }}
            </option>
          </select>
        </label>
      </div>
      <button class="primary" :disabled="busy || !email.trim()">Добавить участника</button>
    </form>
    <section v-if="props.role === 'owner'" class="role-settings" aria-label="Пользовательские роли">
      <div class="team-heading">
        <h3>Пользовательские роли</h3>
        <button class="secondary" :disabled="busy" @click="editRole()">Создать роль</button>
      </div>
      <ul v-if="roles.length" class="team-members">
        <li v-for="customRole in roles" :key="customRole.id" class="team-member">
          <strong>{{ customRole.name }}</strong>
          <div class="team-controls">
            <button
              class="secondary"
              :aria-label="'Настроить роль ' + customRole.name"
              :disabled="busy"
              @click="editRole(customRole)"
            >
              Настроить
            </button>
            <button
              class="team-remove"
              :aria-label="'Удалить роль ' + customRole.name"
              :disabled="busy"
              @click="deleteRole(customRole)"
            >
              Удалить
            </button>
          </div>
        </li>
      </ul>
      <form v-if="roleEditor !== null" class="team-form" @submit.prevent="saveRole">
        <label
          >Название роли <input v-model="roleName" required maxlength="80" :disabled="busy"
        /></label>
        <fieldset :disabled="busy">
          <legend>Разрешения</legend>
          <div class="role-grants">
            <label v-for="(label, key) in permissionLabels" :key="key">
              <input v-model="rolePermissions" type="checkbox" :value="key" /> {{ label }}
            </label>
          </div>
        </fieldset>
        <div class="team-controls">
          <button class="primary" :disabled="busy || !roleName.trim()">Сохранить роль</button>
          <button type="button" class="secondary" :disabled="busy" @click="roleEditor = null">
            Отмена
          </button>
        </div>
      </form>
    </section>
    <p v-if="actionError" class="error" role="alert">{{ actionError }}</p>
    <p v-if="status" class="team-status" role="status">{{ status }}</p>
  </dialog>
</template>

<style scoped>
.role-settings {
  border-top: 1px solid var(--line, #d8dce2);
  margin-top: 1.2rem;
  padding-top: 1rem;
}
.role-grants {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.45rem;
}
.role-grants label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.role-grants input {
  width: auto;
}
.role-settings fieldset {
  border: 0;
  padding: 0;
  margin: 0.6rem 0;
}
.role-settings legend {
  margin-bottom: 0.5rem;
}
</style>
