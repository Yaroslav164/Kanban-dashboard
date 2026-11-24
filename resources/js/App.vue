<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { HttpError, request } from './api'
import { useBoard, type User, type Summary, type Card, type Column } from './board'
import BoardTeam from './BoardTeam.vue'
import BoardViews from './BoardViews.vue'
import CardDetails from './CardDetails.vue'
import CardTemplatePicker from './CardTemplatePicker.vue'
import SaveCardTemplate from './SaveCardTemplate.vue'
import type { CardTemplate } from './card-templates'
import { subscribe } from './realtime'

const store = useBoard(),
  user = ref<User | null>(null),
  loading = ref(true),
  busy = ref(false)
const register = ref(false),
  message = ref(''),
  query = ref('')
const name = ref(''),
  email = ref(''),
  password = ref(''),
  confirm = ref('')
const dragged = ref<number | null>(null),
  hover = ref<string | null>(null)
const views = [
  { id: 'board', label: 'Доска' },
  { id: 'list', label: 'Список' },
  { id: 'tasks', label: 'Дела' },
  { id: 'calendar', label: 'Календарь' },
] as const
const view = ref<(typeof views)[number]['id']>('board')
const detail = ref<{ boardId: number; cardId: number } | null>(null),
  detailDialog = ref<HTMLDialogElement>()
const detailCard = computed(() => {
  const board = store.active
  const selected = detail.value
  if (!board || selected?.boardId !== board.id) return null
  return board.columns.flatMap((column) => column.cards).find(({ id }) => id === selected.cardId)
})
const editor = ref<{
  kind: 'доска' | 'колонка' | 'карточка'
  path: string
  boardId: number | null
  id?: number
  remove?: boolean
} | null>(null)
const title = ref(''),
  description = ref(''),
  dialog = ref<HTMLDialogElement>(),
  firstField = ref<HTMLInputElement>()
const template = ref<CardTemplate | null>(null)
const visible = computed(() =>
  store.boards.filter((b) => b.title.toLocaleLowerCase().includes(query.value.toLocaleLowerCase())),
)
let disconnect: (() => void) | undefined
const oauthErrors: Record<string, string> = {
  invalid_state: 'Срок запроса истёк. Войдите снова.',
  denied: 'Вход через GitHub отменён.',
  email_unverified: 'Подтвердите основной email в GitHub.',
  account_exists: 'Войдите по паролю и привяжите GitHub в меню профиля.',
  identity_in_use: 'Этот аккаунт GitHub уже привязан.',
  provider_unavailable: 'GitHub временно недоступен.',
}
function toggleRegister() {
  register.value = !register.value
  message.value = ''
}
function endDrag() {
  dragged.value = null
  hover.value = null
}
function report(e: unknown) {
  message.value =
    e instanceof HttpError && e.status === 409
      ? 'Порядок изменился в другой вкладке. Повторите перенос.'
      : e instanceof Error
        ? e.message
        : 'Не удалось выполнить запрос.'
}
async function run(fn: () => Promise<void>) {
  if (busy.value) return
  busy.value = true
  message.value = ''
  try {
    await fn()
  } catch (e) {
    report(e)
    if (store.active && e instanceof HttpError && (e.status === 403 || e.status === 404)) {
      syncBoard()
    }
  } finally {
    busy.value = false
  }
}
async function session(u: User) {
  user.value = u
  disconnect?.()
  disconnect = subscribe(u.id, syncBoard)
  const id = Number(new URLSearchParams(location.search).get('board'))
  await store.select(id > 0 ? id : null)
}
function syncBoard() {
  void store.refresh().catch(report)
}
onMounted(async () => {
  const oauth = new URLSearchParams(location.search).get('oauth')
  if (oauth) message.value = oauthErrors[oauth] || 'Не удалось войти через GitHub.'
  try {
    await request('/sanctum/csrf-cookie')
    await session(await request<User>('/api/auth/me'))
  } catch (e) {
    if (!(e instanceof HttpError && e.status === 401)) report(e)
  } finally {
    loading.value = false
  }
})
onUnmounted(() => disconnect?.())
async function authenticate() {
  await run(async () => {
    await request('/sanctum/csrf-cookie')
    const fields = { email: email.value, password: password.value }
    const u = register.value
      ? await request<User>('/api/auth/register', 'POST', {
          ...fields,
          name: name.value,
          password_confirmation: confirm.value,
        })
      : await request<User>('/api/auth/login', 'POST', fields)
    password.value = ''
    confirm.value = ''
    history.replaceState(null, '', '/boards')
    await session(u)
  })
}
async function logout() {
  await run(async () => {
    await request('/api/auth/logout', 'POST')
    disconnect?.()
    store.reset()
    user.value = null
    history.replaceState(null, '', '/login')
  })
}
async function select(id: number | null) {
  history.replaceState(null, '', id ? '/boards?board=' + id : '/boards')
  await store.select(id)
}
async function openCard(card: Card) {
  if (!store.active) return
  detail.value = { boardId: store.active.id, cardId: card.id }
  await nextTick()
  if (detailCard.value && !detailDialog.value?.open) detailDialog.value?.showModal()
}
function closeCard() {
  detailDialog.value?.close()
  detail.value = null
}
watch(detailCard, (card) => {
  if (!card && detail.value) closeCard()
})
async function completeCard(card: Card, completed: boolean) {
  if (!store.can('cards.edit')) return
  await run(async () => {
    await request('/api/columns/' + card.column_id + '/cards/' + card.id, 'PATCH', { completed })
    await store.refresh()
  })
}
async function edit(
  kind: 'доска' | 'колонка' | 'карточка',
  path: string,
  item?: Summary,
  remove = false,
) {
  const boardId = kind === 'доска' && !item ? null : store.active?.id
  if (boardId === undefined) return
  const entry = { kind, path, boardId, id: item?.id, remove }
  if (!canSave(entry)) return
  editor.value = entry
  template.value = null
  title.value = item?.title ?? ''
  description.value = (item as Card)?.description ?? ''
  await nextTick()
  dialog.value?.showModal()
  firstField.value?.focus()
}
function close() {
  dialog.value?.close()
  editor.value = null
}
function canSave(entry: NonNullable<typeof editor.value>) {
  if (entry.boardId === null) return true
  if (entry.boardId !== store.active?.id) return false
  if (entry.kind === 'доска')
    return entry.remove ? store.role === 'owner' : store.can('board.rename')
  if (entry.kind === 'колонка') {
    return store.can(entry.remove ? 'columns.delete' : entry.id ? 'columns.edit' : 'columns.create')
  }
  return store.can(entry.remove ? 'cards.delete' : entry.id ? 'cards.edit' : 'cards.create')
}
watch(
  () => store.can('cards.move'),
  (canMove) => {
    if (!canMove) endDrag()
  },
)
watch(
  () => editor.value && canSave(editor.value),
  (allowed) => {
    if (allowed === false) close()
  },
)
async function save() {
  const entry = editor.value
  if (!entry || !canSave(entry)) return
  await run(async () => {
    const path = entry.path + (entry.id ? '/' + entry.id : '')
    if (entry.kind === 'карточка' && !entry.id && !entry.remove && template.value) {
      await request(path + '/from-template', 'POST', { template_id: template.value.id })
      close()
      await store.refresh()
      return
    }
    const result = await request<Summary>(
      path,
      entry.remove ? 'DELETE' : entry.id ? 'PATCH' : 'POST',
      entry.remove
        ? undefined
        : {
            title: title.value.trim(),
            ...(entry.kind === 'карточка' ? { description: description.value.trim() || null } : {}),
          },
    )
    close()
    if (entry.kind === 'доска' && !entry.id) await select(result.id)
    else await store.refresh()
  })
}
function beginDrag(e: DragEvent, id: number) {
  if (!store.can('cards.move')) {
    e.preventDefault()
    return
  }
  dragged.value = id
  if (e.dataTransfer) {
    e.dataTransfer.setData('text/plain', String(id))
    e.dataTransfer.effectAllowed = 'move'
  }
}
function allowDrop(e: DragEvent, before: number | null = null) {
  if (!store.can('cards.move') || dragged.value === null) return
  e.preventDefault()
  hover.value = before === null ? null : String(before)
}
async function drop(column: Column, before: number | null) {
  const id = dragged.value
  dragged.value = null
  hover.value = null
  if (id !== null && store.can('cards.move')) await run(() => store.move(id, column, before))
}
const nouns = {
  доска: ['Новая доска', 'доску'],
  колонка: ['Новая колонка', 'колонку'],
  карточка: ['Новая карточка', 'карточку'],
}
</script>
<template>
  <div v-if="loading" class="waiting" role="status">Загрузка…</div>
  <main v-else-if="!user" class="login">
    <section class="poster" aria-hidden="true">
      <div class="wordmark"><i /> Полоса</div>
      <div class="poster-art"><span /><span /><span /></div>
      <span class="poster-caption">КАНБАН-ДОСКА</span>
    </section>
    <section class="login-panel">
      <form @submit.prevent="authenticate">
        <p class="kicker">ЛИЧНОЕ ПРОСТРАНСТВО</p>
        <h1>{{ register ? 'Регистрация' : 'Вход' }}</h1>
        <label v-if="register"
          >Имя<input v-model="name" autocomplete="name" required maxlength="255"
        /></label>
        <label
          >Электронная почта<input v-model="email" type="email" autocomplete="email" required
        /></label>
        <label
          >Пароль<input
            v-model="password"
            type="password"
            :autocomplete="register ? 'new-password' : 'current-password'"
            required
        /></label>
        <label v-if="register"
          >Повторите пароль<input
            v-model="confirm"
            type="password"
            autocomplete="new-password"
            required
        /></label>
        <p v-if="message" role="alert" class="error">{{ message }}</p>
        <button class="primary" :disabled="busy">
          {{ register ? 'Создать аккаунт' : 'Войти' }}</button><a
          class="secondary"
          href="/auth/github/redirect"
          >Войти через GitHub ↗</a
        >
        <button type="button" class="text" @click="toggleRegister">
          {{ register ? 'Уже есть аккаунт? Войти' : 'Создать аккаунт' }}
        </button>
      </form>
    </section>
  </main>
  <div v-else class="workspace">
    <aside>
      <div class="wordmark"><i /> Полоса</div>
      <button class="nav" @click="select(null).catch(report)">▦ &nbsp; Мои доски</button>
      <div class="side-label">
        ДОСКИ <button title="Создать доску" @click="edit('доска', '/api/boards')">+</button>
      </div>
      <button
        v-for="b in store.boards"
        :key="b.id"
        class="side-board"
        :class="{ chosen: b.id === store.selected }"
        @click="select(b.id).catch(report)"
      >
        <span class="dot" />{{ b.title }}
      </button>
      <details class="profile">
        <summary>
          <b>{{ user.name.slice(0, 1).toUpperCase() }}</b> {{ user.name }}
        </summary>
        <a href="/auth/github/redirect?intent=link">Привязать GitHub</a
        ><button @click="logout">Выйти</button>
      </details>
    </aside>
    <main class="desk">
      <header>
        <button class="text" @click="select(null).catch(report)">Мои доски</button
        ><span v-if="store.active"> / &nbsp; {{ store.active.title }}</span>
      </header>
      <div v-if="message && !editor" class="error" role="alert">
        {{ message }} <button aria-label="Закрыть" @click="message = ''">×</button>
      </div>
      <template v-if="!store.active"
        ><div class="page-head">
          <div>
            <p class="kicker">РАБОЧЕЕ ПРОСТРАНСТВО</p>
            <h1>Мои доски</h1>
          </div>
          <button class="primary" @click="edit('доска', '/api/boards')">＋ Создать доску</button>
        </div>
        <input
          v-if="store.boards.length"
          v-model="query"
          type="search"
          aria-label="Поиск досок"
          placeholder="Поиск досок"
          class="search"
        />
        <div v-if="visible.length" class="boards">
          <button
            v-for="(b, i) in visible"
            :key="b.id"
            class="board-tile"
            @click="select(b.id).catch(report)"
          >
            <span class="tile-art" :class="'art-' + (i % 3)"><i /><i /><i /></span
            ><strong>{{ b.title }}</strong
            ><span aria-hidden="true">↗</span>
          </button>
        </div>
        <div v-else class="empty">
          <h2>{{ query ? 'Доски не найдены' : 'Создайте первую доску' }}</h2>
          <p>
            {{
              query
                ? 'Попробуйте другое название.'
                : 'Добавьте колонки для этапов работы и карточки для задач.'
            }}
          </p>
        </div></template
      >
      <template v-else
        ><div class="page-head">
          <div>
            <p class="kicker">КАНБАН-ДОСКА</p>
            <h1>{{ store.active.title }}</h1>
            <p v-if="store.role === 'viewer'" class="board-readonly">Только просмотр</p>
          </div>
          <div class="actions board-actions">
            <BoardTeam
              v-if="store.role"
              :key="store.active.id"
              :board="store.active"
              :role="store.role"
              @access-changed="syncBoard"
            />
            <details v-if="store.can('board.rename') || store.role === 'owner'">
              <summary aria-label="Действия с доской">•••</summary>
              <div class="menu">
                <button
                  v-if="store.can('board.rename')"
                  @click="edit('доска', '/api/boards', store.active)"
                >
                  Переименовать доску</button
                ><button
                  v-if="store.role === 'owner'"
                  @click="edit('доска', '/api/boards', store.active, true)"
                >
                  Удалить доску
                </button>
              </div>
            </details>
            <button
              v-if="store.can('columns.create')"
              class="primary"
              @click="edit('колонка', '/api/boards/' + store.active.id + '/columns')"
            >
              ＋ Колонка
            </button>
          </div>
        </div>
        <div class="board-views" role="group" aria-label="Представление доски">
          <button
            v-for="option in views"
            :key="option.id"
            :aria-pressed="view === option.id"
            @click="view = option.id"
          >
            {{ option.label }}
          </button>
        </div>
        <BoardViews
          v-if="view !== 'board'"
          :key="store.active.id"
          :view="view"
          :board-id="store.active.id"
          :columns="store.active.columns"
          :can-complete="store.can('cards.edit')"
          :busy="busy"
          @open="openCard"
          @complete="completeCard"
        />
        <div v-else class="lanes">
          <section
            v-for="(col, i) in store.active.columns"
            :key="col.id"
            class="lane"
            :class="'lane-' + (i % 3)"
            @dragover="allowDrop($event)"
            @drop.prevent="drop(col, null)"
          >
            <div class="lane-head">
              <span class="dot" />
              <h2>{{ col.title }}</h2>
              <small>{{ col.cards.length }}</small>
              <details v-if="store.can('columns.edit') || store.can('columns.delete')">
                <summary :aria-label="'Действия с колонкой ' + col.title">•••</summary>
                <div class="menu">
                  <button
                    v-if="store.can('columns.edit')"
                    @click="edit('колонка', '/api/boards/' + store.active.id + '/columns', col)"
                  >
                    Переименовать</button
                  ><button
                    v-if="store.can('columns.delete')"
                    @click="
                      edit('колонка', '/api/boards/' + store.active.id + '/columns', col, true)
                    "
                  >
                    Удалить колонку
                  </button>
                </div>
              </details>
            </div>
            <article
              v-for="card in col.cards"
              :key="card.id"
              :draggable="store.can('cards.move')"
              :class="{ target: hover === String(card.id) }"
              @dragstart="beginDrag($event, card.id)"
              @dragend="endDrag"
              @dragover.stop="allowDrop($event, card.id)"
              @drop.stop.prevent="drop(col, card.id)"
            >
              <button class="card-body" :aria-label="card.title" @click="openCard(card)">
                <strong>{{ card.title }}</strong>
                <span v-if="card.description" class="card-description">{{ card.description }}</span>
                <span v-if="card.labels.length" class="card-meta-labels">
                  <span v-for="label in card.labels" :key="label.name">
                    <i :style="{ background: label.color }" aria-hidden="true" />{{ label.name }}
                  </span>
                </span>
                <small v-if="card.completed" class="card-status">Выполнено</small>
                <time v-if="card.due_date" class="card-status" :datetime="card.due_date">
                  Срок {{ card.due_date.split('-').reverse().join('.') }}
                </time>
              </button>
              <details v-if="store.can('cards.delete') || store.can('cards.move')">
                <summary :aria-label="'Действия с карточкой ' + card.title">•••</summary>
                <div class="menu">
                  <button
                    v-if="store.can('cards.delete')"
                    @click="edit('карточка', '/api/columns/' + col.id + '/cards', card, true)"
                  >
                    Удалить карточку
                  </button>
                  <template v-if="store.can('cards.move')">
                    <button
                      v-for="dest in store.active.columns"
                      :key="dest.id"
                      @click="run(() => store.move(card.id, dest, null))"
                    >
                      В конец · {{ dest.title }}
                    </button>
                  </template>
                </div>
              </details>
            </article>
            <button
              v-if="store.can('cards.create')"
              class="add-card"
              @click="edit('карточка', '/api/columns/' + col.id + '/cards')"
            >
              ＋ Добавить карточку
            </button>
          </section>
          <div v-if="!store.active.columns.length" class="empty">
            <h2>
              {{
                store.can('columns.create')
                  ? 'Добавьте первую колонку'
                  : 'На доске пока нет колонок'
              }}
            </h2>
            <p v-if="store.can('columns.create')">Каждая колонка обозначает этап работы.</p>
          </div>
        </div></template
      >
    </main>
    <dialog
      ref="detailDialog"
      class="card-dialog"
      aria-labelledby="card-dialog-title"
      @close="detail = null"
    >
      <div v-if="detail && detailCard" class="card-dialog-head">
        <h2 id="card-dialog-title">{{ detailCard.title }}</h2>
        <button type="button" class="secondary" aria-label="Закрыть карточку" @click="closeCard">
          ×
        </button>
      </div>
      <CardDetails
        v-if="detail && detailCard"
        :key="detailCard.id"
        :card="detailCard"
        :board-id="detail.boardId"
        :user-id="user.id"
        :can-edit="store.can('cards.edit')"
        :can-comment="store.can('cards.comment')"
        :can-attach="store.can('cards.attach')"
        @changed="syncBoard"
      >
        <template v-if="store.can('cards.create')" #template="{ dirty }">
          <SaveCardTemplate
            :board-id="detail.boardId"
            :card-id="detailCard.id"
            :title="detailCard.title"
            :dirty="dirty"
          />
        </template>
      </CardDetails>
    </dialog>
    <dialog ref="dialog" @cancel="editor = null">
      <form v-if="editor" @submit.prevent="save">
        <p class="kicker">{{ editor.kind.toUpperCase() }}</p>
        <h2>
          {{
            editor.remove
              ? 'Удалить ' + nouns[editor.kind][1] + '?'
              : editor.id
                ? 'Редактировать ' + nouns[editor.kind][1]
                : nouns[editor.kind][0]
          }}
        </h2>
        <template v-if="!editor.remove">
          <CardTemplatePicker
            v-if="editor.kind === 'карточка' && !editor.id && editor.boardId !== null"
            :board-id="editor.boardId"
            @select="template = $event"
          />
          <template v-if="template">
            <p>
              <strong>{{ template.title }}</strong>
            </p>
            <p v-if="template.description">{{ template.description }}</p>
          </template>
          <template v-else>
            <label
              >Название<input ref="firstField" v-model="title" required maxlength="255"
            /></label>
            <label v-if="editor.kind === 'карточка'">
              Описание<textarea v-model="description" maxlength="10000" rows="4" />
            </label>
          </template>
        </template>
        <p v-else>
          «{{ title }}» будет удалена{{ editor.kind === 'карточка' ? '' : ' вместе с содержимым' }}.
        </p>
        <p v-if="message" class="error" role="alert">{{ message }}</p>
        <div class="actions">
          <button type="button" class="secondary" @click="close">Отмена</button
          ><button
            class="primary"
            :disabled="busy || (!editor.remove && !template && !title.trim())"
          >
            {{ editor.remove ? 'Удалить' : 'Сохранить' }}
          </button>
        </div>
      </form>
    </dialog>
  </div>
</template>
