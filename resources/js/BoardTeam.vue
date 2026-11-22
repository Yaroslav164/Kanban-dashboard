<script setup lang="ts">
import { onUnmounted, ref, watch } from 'vue'
import { HttpError, request } from './api'
import type { Board, BoardMember, BoardRole, MemberRole } from './board'

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
const labels: Record<BoardRole, string> = {
  owner: 'Владелец',
  editor: 'Редактор',
  viewer: 'Наблюдатель',
}
let loadVersion = 0
let panelVersion = 0
const path = () => '/api/boards/' + props.board.id + '/members'

function errorMessage(error: unknown) {
  return error instanceof Error ? error.message : 'Не удалось выполнить запрос.'
}

async function loadMembers() {
  const seq = ++loadVersion
  loading.value = true
  loadError.value = ''
  try {
    const list = await request<BoardMember[]>(path())
    if (seq === loadVersion) members.value = list
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
  if (next !== 'editor' && next !== 'viewer') return
  const saved = await changeTeam(
    () => request(path() + '/' + member.id, 'PATCH', { role: next }),
    'Роль обновлена.',
  )
  if (!saved) field.value = member.role
}

function remove(member: BoardMember) {
  void changeTeam(() => request(path() + '/' + member.id, 'DELETE'), 'Доступ участника отозван.')
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
              <option value="editor">{{ labels.editor }}</option>
              <option value="viewer">{{ labels.viewer }}</option>
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
          <span v-else class="team-role">{{ labels[member.role] }}</span>
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
            <option value="editor">{{ labels.editor }}</option>
            <option value="viewer">{{ labels.viewer }}</option>
          </select>
        </label>
      </div>
      <button class="primary" :disabled="busy || !email.trim()">Добавить участника</button>
    </form>
    <p v-if="actionError" class="error" role="alert">{{ actionError }}</p>
    <p v-if="status" class="team-status" role="status">{{ status }}</p>
  </dialog>
</template>
