<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { HttpError, request } from './api'
import type { BoardMember, Card } from './board'
import CardHistory from './CardHistory.vue'

const props = defineProps<{
  card: Card
  boardId: number
  userId: number
  canEdit: boolean
  canComment: boolean
  canAttach: boolean
}>()
const emit = defineEmits<{ changed: [] }>()

interface Comment {
  id: number
  body: string
  author: { id: number; name: string } | null
  created_at: string
}
interface Attachment {
  id: number
  name: string
  mime_type: string
  size: number
}
interface Activity {
  comments: Comment[]
  attachments: Attachment[]
}

function fields(card: Card) {
  return {
    title: card.title,
    description: card.description ?? '',
    start_date: card.start_date ?? '',
    due_date: card.due_date ?? '',
    completed: card.completed,
    labels: card.labels.map((label) => ({ ...label })),
    assignee_ids: [...card.assignee_ids],
    checklists: card.checklists.map((list) => ({
      ...list,
      items: list.items.map((item) => ({ ...item })),
    })),
  }
}
let baseline = fields(props.card)
const draft = ref(fields(props.card))
const activity = ref<Activity>({ comments: [], attachments: [] })
const people = ref<BoardMember[]>([])
const comment = ref('')
const mentionable = computed(() => people.value.filter((person) => person.id !== props.userId))
function insertMention(event: Event) {
  const select = event.currentTarget as HTMLSelectElement
  const body = [comment.value.trimEnd(), `@${select.value} `].filter(Boolean).join(' ')
  if (select.value && body.length <= 10000) comment.value = body
  select.value = ''
}
const labelName = ref('')
const labelColor = ref('#5268c1')
const checklistTitle = ref('')
const itemTitles = ref<Record<string, string>>({})
const fileInput = ref<HTMLInputElement>()
function setFileInput(element: unknown) {
  fileInput.value = element instanceof HTMLInputElement ? element : undefined
}
const busy = ref(false)
const error = ref('')
const commentError = ref('')
const commentErrorId = ref<number | null>(null)
const status = ref('')
const activityLoading = ref(true)
const activityError = ref(false)
const peopleLoading = ref(true)
const peopleError = ref(false)
let generation = 0
const path = () => '/api/cards/' + props.card.id
const attachmentPath = (attachment: Attachment) => path() + '/attachments/' + attachment.id
const imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
const keys = Object.keys(baseline) as (keyof typeof baseline)[]
const same = (a: unknown, b: unknown) => JSON.stringify(a) === JSON.stringify(b)
const dirtyFields = computed(() => keys.filter((key) => !same(draft.value[key], baseline[key])))
const conflictMessage = 'Эти поля уже изменились. Обновите форму и внесите правки заново.'

const sectionNames = {
  dates: 'Даты',
  labels: 'Метки',
  assignees: 'Участники',
  checklists: 'Чеклисты',
  attachments: 'Вложения',
} as const
type Section = keyof typeof sectionNames
const sectionKeys = Object.keys(sectionNames) as Section[]
const layoutKey = 'kanban:card-layout:' + props.userId + ':' + props.card.id
const preferredOrder = ref<Section[]>(readOrder())
const sectionContent = computed<Record<Section, boolean>>(() => ({
  dates: !!(draft.value.start_date || draft.value.due_date),
  labels: draft.value.labels.length > 0,
  assignees: draft.value.assignee_ids.length > 0,
  checklists: draft.value.checklists.length > 0,
  attachments: activity.value.attachments.length > 0,
}))
const visibleSections = computed(() =>
  preferredOrder.value.concat(
    sectionKeys.filter(
      (section) => sectionContent.value[section] && !preferredOrder.value.includes(section),
    ),
  ),
)
const sectionButtons = computed(() =>
  sectionKeys.filter(
    (section) =>
      visibleSections.value.includes(section) ||
      (section === 'attachments' ? props.canAttach : props.canEdit),
  ),
)

function readOrder(): Section[] {
  try {
    const saved: unknown = JSON.parse(localStorage.getItem(layoutKey) ?? '[]')
    if (Array.isArray(saved)) {
      return [...new Set(saved.filter((key): key is Section => sectionKeys.includes(key)))]
    }
  } catch {
    // Storage can be blocked or contain an older layout format.
  }
  return []
}

function saveOrder(order: Section[]) {
  preferredOrder.value = order
  try {
    localStorage.setItem(layoutKey, JSON.stringify(order))
  } catch {
    // The current layout remains usable with storage disabled.
  }
}

const headingId = (section: Section) => 'card-section-' + props.card.id + '-' + section

async function openSection(section: Section) {
  if (!visibleSections.value.includes(section)) saveOrder([...visibleSections.value, section])
  await nextTick()
  document.getElementById(headingId(section))?.focus()
}

async function moveSection(index: number, offset: number, event: MouseEvent) {
  const order = [...visibleSections.value]
  const destination = index + offset
  if (destination < 0 || destination >= order.length) return
  const [section] = order.splice(index, 1)
  order.splice(destination, 0, section)
  const button = event.currentTarget
  saveOrder(order)
  await nextTick()
  if (button instanceof HTMLButtonElement) button.focus()
}

function reset() {
  baseline = fields(props.card)
  draft.value = fields(props.card)
}

async function refresh() {
  const version = ++generation
  const results = await Promise.allSettled([
    request<Activity>(path() + '/activity'),
    request<BoardMember[]>('/api/boards/' + props.boardId + '/members'),
  ])
  if (version !== generation) return
  activityLoading.value = false
  peopleLoading.value = false
  const [cardActivity, members] = results
  if (cardActivity.status === 'fulfilled') activity.value = cardActivity.value
  if (members.status === 'fulfilled') people.value = members.value
  activityError.value = cardActivity.status === 'rejected'
  peopleError.value = members.status === 'rejected'
}

watch(
  () => props.card,
  () => {
    if (keys.every((key) => same(draft.value[key], baseline[key]))) reset()
    void refresh()
  },
  { immediate: true },
)
watch(
  () => props.canEdit,
  (allowed) => {
    if (!allowed) reset()
  },
)

async function write(action: () => Promise<unknown>, success = '', feedback = error) {
  if (busy.value) return false
  busy.value = true
  feedback.value = ''
  status.value = ''
  try {
    await action()
    await refresh()
    emit('changed')
    status.value = success
    return true
  } catch (failure) {
    feedback.value =
      failure instanceof HttpError && failure.status === 409
        ? conflictMessage
        : failure instanceof Error
          ? failure.message
          : 'Не удалось сохранить изменения.'
    if (failure instanceof HttpError && [403, 404, 409].includes(failure.status)) emit('changed')
    return false
  } finally {
    busy.value = false
  }
}

async function save() {
  if (!props.canEdit) return
  const current = fields(props.card)
  const dirty = dirtyFields.value
  if (!dirty.length) return
  if (dirty.some((key) => !same(current[key], baseline[key]))) {
    error.value = conflictMessage
    return
  }
  const values = (snapshot: typeof baseline) =>
    Object.fromEntries(dirty.map((key) => [key, snapshot[key] === '' ? null : snapshot[key]]))
  await write(async () => {
    const saved = await request<Card>(
      '/api/columns/' + props.card.column_id + '/cards/' + props.card.id,
      'PATCH',
      { ...values(draft.value), base: values(baseline) },
    )
    baseline = fields(saved)
    draft.value = fields(saved)
  }, 'Карточка сохранена.')
}

function addLabel() {
  const name = labelName.value.trim()
  if (!name || draft.value.labels.some((label) => label.name.toLowerCase() === name.toLowerCase()))
    return
  draft.value.labels.push({ name, color: labelColor.value })
  labelName.value = ''
}
function addChecklist() {
  const title = checklistTitle.value.trim()
  if (!title) return
  draft.value.checklists.push({ id: crypto.randomUUID(), title, items: [] })
  checklistTitle.value = ''
}
function addItem(list: Card['checklists'][number]) {
  const title = itemTitles.value[list.id]?.trim()
  if (!title) return
  list.items.push({ id: crypto.randomUUID(), title, completed: false })
  itemTitles.value[list.id] = ''
}
async function postComment() {
  const body = comment.value.trim()
  if (
    props.canComment &&
    body &&
    (await writeComment(() => request(path() + '/comments', 'POST', { body })))
  ) {
    comment.value = ''
  }
}

function writeComment(action: () => Promise<unknown>, id: number | null = null) {
  if (busy.value) return false
  commentErrorId.value = id
  return write(action, '', commentError)
}
async function upload() {
  const file = fileInput.value?.files?.[0]
  if (!props.canAttach || !file) return
  if (file.size > 10 * 1024 * 1024) {
    error.value = 'Размер вложения не должен превышать 10 МБ.'
    return
  }
  const data = new FormData()
  data.append('file', file)
  if ((await write(() => request(path() + '/attachments', 'POST', data))) && fileInput.value)
    fileInput.value.value = ''
}
</script>

<template>
  <div class="card-details">
    <section class="card-main" aria-label="Содержание карточки" tabindex="0">
      <slot name="template" :dirty="dirtyFields.length > 0" />
      <p v-if="error" class="error" role="alert">{{ error }}</p>
      <div
        v-if="sectionButtons.length"
        class="section-buttons"
        role="group"
        aria-label="Блоки карточки"
      >
        <button
          v-for="section in sectionButtons"
          :key="section"
          type="button"
          class="secondary"
          :class="{ 'section-present': visibleSections.includes(section) }"
          :aria-label="sectionNames[section]"
          @click="openSection(section)"
        >
          <span v-if="!visibleSections.includes(section)" aria-hidden="true">+</span
          >{{ sectionNames[section] }}
        </button>
      </div>
      <form @submit.prevent="save">
        <fieldset :disabled="!canEdit || busy">
          <label>Название<input v-model="draft.title" required maxlength="255" /></label>
          <label>Описание<textarea v-model="draft.description" maxlength="10000" rows="3" /></label>
          <label class="detail-check"
            ><input v-model="draft.completed" type="checkbox" /> Карточка выполнена</label
          >
        </fieldset>
        <section
          v-for="(section, sectionIndex) in visibleSections"
          :key="section"
          class="detail-section"
          :aria-labelledby="headingId(section)"
        >
          <div class="section-heading">
            <h3 :id="headingId(section)" tabindex="-1">{{ sectionNames[section] }}</h3>
            <div v-if="visibleSections.length > 1" class="section-order">
              <button
                type="button"
                class="secondary"
                :aria-label="'Переместить выше: ' + sectionNames[section]"
                :aria-disabled="sectionIndex === 0"
                @click="moveSection(sectionIndex, -1, $event)"
              >
                ↑
              </button>
              <button
                type="button"
                class="secondary"
                :aria-label="'Переместить ниже: ' + sectionNames[section]"
                :aria-disabled="sectionIndex === visibleSections.length - 1"
                @click="moveSection(sectionIndex, 1, $event)"
              >
                ↓
              </button>
            </div>
          </div>

          <template v-if="section === 'attachments'">
            <p v-if="activityLoading">Вложения загружаются…</p>
            <p v-else-if="activityError" class="error" role="alert">
              Не удалось загрузить вложения.
              <button type="button" class="secondary" @click="refresh">Повторить</button>
            </p>
            <p v-if="canAttach">До 20 файлов и 50 МБ на карточку.</p>
            <ul v-if="activity.attachments.length" class="card-activity">
              <li v-for="attachment in activity.attachments" :key="attachment.id">
                <img
                  v-if="imageTypes.includes(attachment.mime_type)"
                  :src="attachmentPath(attachment) + '?inline=1'"
                  :alt="attachment.name"
                  loading="lazy"
                />
                <div class="detail-row">
                  <a :href="attachmentPath(attachment)" download>{{ attachment.name }}</a>
                  <button
                    v-if="canAttach"
                    type="button"
                    class="secondary"
                    :disabled="busy"
                    @click="write(() => request(attachmentPath(attachment), 'DELETE'))"
                  >
                    Удалить файл
                  </button>
                </div>
              </li>
            </ul>
            <div v-if="canAttach" class="detail-row">
              <label>Файл до 10 МБ<input :ref="setFileInput" type="file" :disabled="busy" /></label>
              <button type="button" class="primary" :disabled="busy" @click="upload">
                Прикрепить
              </button>
            </div>
          </template>
          <fieldset v-else :disabled="!canEdit || busy">
            <div v-if="section === 'dates'" class="detail-row">
              <label>Начало<input v-model="draft.start_date" type="date" /></label>
              <label
                >Срок<input
                  v-model="draft.due_date"
                  type="date"
                  :min="draft.start_date || undefined"
              /></label>
            </div>
            <template v-else-if="section === 'labels'">
              <ul v-if="draft.labels.length" class="card-labels">
                <li v-for="(label, index) in draft.labels" :key="label.name">
                  <span :style="{ background: label.color }" aria-hidden="true" />
                  {{ label.name }}
                  <button
                    v-if="canEdit"
                    type="button"
                    class="secondary"
                    :aria-label="'Удалить метку ' + label.name"
                    @click="draft.labels.splice(index, 1)"
                  >
                    ×
                  </button>
                </li>
              </ul>
              <div v-if="canEdit" class="detail-row">
                <label
                  >Новая метка<input
                    v-model="labelName"
                    maxlength="50"
                    @keydown.enter.prevent="addLabel"
                /></label>
                <label>Цвет<input v-model="labelColor" type="color" /></label>
                <button
                  type="button"
                  class="secondary"
                  :disabled="draft.labels.length >= 50"
                  @click="addLabel"
                >
                  Добавить метку
                </button>
              </div>
            </template>
            <template v-else-if="section === 'assignees'">
              <p v-if="peopleLoading">Список участников загружается…</p>
              <p v-else-if="peopleError" class="error" role="alert">
                Не удалось загрузить участников.
                <button type="button" class="secondary" @click="refresh">Повторить</button>
              </p>
              <p v-else-if="!people.length">Нет доступных участников.</p>
              <label v-for="person in people" :key="person.id" class="detail-check">
                <input v-model="draft.assignee_ids" type="checkbox" :value="person.id" />
                {{ person.name }}
              </label>
            </template>
            <template v-else-if="section === 'checklists'">
              <div v-for="(list, index) in draft.checklists" :key="list.id" class="card-checklist">
                <div class="detail-row">
                  <label
                    >Название чеклиста<input v-model="list.title" required maxlength="255"
                  /></label>
                  <button
                    v-if="canEdit"
                    type="button"
                    class="secondary"
                    @click="draft.checklists.splice(index, 1)"
                  >
                    Удалить чеклист
                  </button>
                </div>
                <div v-for="(item, itemIndex) in list.items" :key="item.id" class="detail-row">
                  <label class="detail-check"
                    ><input v-model="item.completed" type="checkbox" /> Выполнен</label
                  >
                  <label>Пункт<input v-model="item.title" required maxlength="255" /></label>
                  <button
                    v-if="canEdit"
                    type="button"
                    class="secondary"
                    :aria-label="'Удалить пункт ' + item.title"
                    @click="list.items.splice(itemIndex, 1)"
                  >
                    ×
                  </button>
                </div>
                <div v-if="canEdit" class="detail-row">
                  <label
                    >Новый пункт<input
                      v-model="itemTitles[list.id]"
                      maxlength="255"
                      @keydown.enter.prevent="addItem(list)"
                  /></label>
                  <button
                    type="button"
                    class="secondary"
                    :disabled="list.items.length >= 100"
                    @click="addItem(list)"
                  >
                    Добавить пункт
                  </button>
                </div>
              </div>
              <div v-if="canEdit" class="detail-row">
                <label
                  >Новый чеклист<input
                    v-model="checklistTitle"
                    maxlength="255"
                    @keydown.enter.prevent="addChecklist"
                /></label>
                <button
                  type="button"
                  class="secondary"
                  :disabled="draft.checklists.length >= 20"
                  @click="addChecklist"
                >
                  Добавить чеклист
                </button>
              </div>
            </template>
          </fieldset>
        </section>
        <div v-if="canEdit" class="detail-row">
          <button class="primary" :disabled="busy">Сохранить карточку</button>
          <button type="button" class="secondary" :disabled="busy" @click="reset">
            Обновить форму
          </button>
          <span v-if="status" role="status">{{ status }}</span>
        </div>
      </form>
    </section>
    <section class="card-comments" aria-label="Комментарии" tabindex="0">
      <h3>Комментарии</h3>
      <p v-if="activityLoading">Комментарии загружаются…</p>
      <p v-else-if="activityError" class="error" role="alert">
        Не удалось загрузить комментарии.
        <button type="button" class="secondary" @click="refresh">Повторить</button>
      </p>
      <p v-else-if="!activity.comments.length">Комментариев пока нет.</p>
      <ul v-if="activity.comments.length" class="card-activity">
        <li v-for="entry in activity.comments" :key="entry.id">
          <strong>{{ entry.author?.name ?? 'Удалённый пользователь' }}</strong>
          <time :datetime="entry.created_at">{{
            new Date(entry.created_at).toLocaleString('ru-RU')
          }}</time>
          <p>{{ entry.body }}</p>
          <p v-if="commentError && commentErrorId === entry.id" class="error" role="alert">
            {{ commentError }}
          </p>
          <button
            v-if="canComment && entry.author?.id === userId"
            type="button"
            class="secondary"
            :disabled="busy"
            @click="
              writeComment(() => request(path() + '/comments/' + entry.id, 'DELETE'), entry.id)
            "
          >
            Удалить комментарий
          </button>
        </li>
      </ul>
      <form v-if="canComment" @submit.prevent="postComment">
        <label>Комментарий<textarea v-model="comment" maxlength="10000" rows="3" required /></label>
        <label v-if="mentionable.length">
          Упомянуть участника
          <select :disabled="busy" @change="insertMention">
            <option value="">Выберите участника</option>
            <option v-for="person in mentionable" :key="person.id" :value="person.email">
              {{ person.name }} ({{ person.email }})
            </option>
          </select>
        </label>
        <p v-if="commentError && commentErrorId === null" class="error" role="alert">
          {{ commentError }}
        </p>
        <button class="primary" :disabled="busy || !comment.trim()">Отправить</button>
      </form>
      <CardHistory :card="card" />
    </section>
  </div>
</template>

<style scoped>
.card-details {
  display: grid;
  grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
  min-height: 0;
}
.card-main,
.card-comments {
  min-width: 0;
  min-height: 0;
  overflow: auto;
  overscroll-behavior: contain;
  padding: clamp(10px, 2.5vw, 22px);
}
.card-main:focus-visible,
.card-comments:focus-visible {
  outline-offset: -2px;
}
.card-comments {
  border-inline-start: 1px solid #d8dfea;
}
.card-comments > h3 {
  margin-top: 0;
}
.section-buttons,
.section-heading,
.section-order {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.section-buttons {
  flex-wrap: wrap;
  margin-block: 0 0.9rem;
}
.section-buttons button,
.section-order button {
  min-height: 44px;
  padding: 3px 9px;
}
.section-buttons button {
  gap: 0.25rem;
}
.section-buttons .section-present {
  background: #e8efe9;
}
.section-heading {
  justify-content: space-between;
}
.section-heading > h3 {
  margin: 0;
}
.section-order button {
  min-width: 44px;
}
.section-order button[aria-disabled='true'] {
  opacity: 0.5;
  cursor: default;
}
.detail-section {
  border: 1px solid #d8dfea;
  border-radius: 0.5rem;
  padding: 0.6rem;
  margin-block: 0.5rem;
}
@media (max-width: 640px) {
  .card-details {
    grid-template-columns: minmax(0, 1fr);
    grid-template-rows: minmax(0, 3fr) minmax(0, 2fr);
  }
  .card-comments {
    border-inline-start: 0;
    border-top: 1px solid #d8dfea;
  }
}
.card-details fieldset {
  border: 0;
  margin: 0;
  padding: 0;
  min-width: 0;
}
.card-details h3 {
  margin: 1rem 0 0.4rem;
}
.detail-row {
  display: flex;
  flex-wrap: wrap;
  align-items: end;
  gap: 0.5rem;
  margin-block: 0.5rem;
}
.detail-row label {
  flex: 1 1 10rem;
  min-width: 0;
  margin: 0;
}
.card-details .detail-check {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.detail-check input {
  width: auto;
  margin: 0;
}
.card-labels,
.card-activity {
  list-style: none;
  padding: 0;
}
.card-labels {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.card-labels li {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.card-labels span {
  width: 0.8rem;
  height: 0.8rem;
  border-radius: 50%;
}
.card-checklist,
.card-activity li {
  border: 1px solid #d8dfea;
  border-radius: 0.5rem;
  padding: 0.65rem;
  margin-block: 0.5rem;
  overflow-wrap: anywhere;
}
.card-activity img {
  display: block;
  max-width: 100%;
  max-height: 14rem;
  object-fit: contain;
}
.card-activity time {
  margin-inline-start: 0.5rem;
  font-size: 0.85em;
}
.card-activity p {
  white-space: pre-wrap;
}
</style>
