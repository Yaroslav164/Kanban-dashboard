<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { request } from './api'
import type { BoardMember, Card, Column } from './board'
import { calendarMonth, dateKey, datesFor, sortTasks } from './board-views'

const props = defineProps<{
  view: 'list' | 'tasks' | 'calendar'
  boardId: number
  columns: Column[]
  canComplete: boolean
  busy: boolean
}>()
const emit = defineEmits<{
  open: [card: Card]
  complete: [card: Card, completed: boolean]
}>()

const rows = computed(() =>
  props.columns.flatMap((column) => column.cards.map((card) => ({ card, column }))),
)
const memberNames = ref(new Map<number, string>())
const memberError = ref(false)
watch(
  () => [props.view, props.boardId, props.columns] as const,
  async ([view, board], _, onCleanup) => {
    if (view !== 'tasks' || !rows.value.some(({ card }) => card.assignee_ids.length)) return
    let active = true
    onCleanup(() => {
      active = false
    })
    try {
      const members = await request<BoardMember[]>('/api/boards/' + board + '/members')
      if (active) {
        memberNames.value = new Map(members.map(({ id, name }) => [id, name]))
        memberError.value = false
      }
    } catch {
      if (active) memberError.value = true
    }
  },
  { immediate: true },
)

function assignees(card: Card) {
  if (!card.assignee_ids.length) return 'Без исполнителя'
  return card.assignee_ids.map((id) => memberNames.value.get(id) ?? 'Участник #' + id).join(', ')
}
const tasks = computed(() => {
  const sorted = [...rows.value].sort((a, b) => sortTasks(a.card, b.card))
  return [
    { title: 'К выполнению', rows: sorted.filter(({ card }) => !card.completed) },
    { title: 'Выполнено', rows: sorted.filter(({ card }) => card.completed) },
  ]
})

const month = ref(new Date())
const monthName = computed(() =>
  new Intl.DateTimeFormat('ru', { month: 'long', year: 'numeric' }).format(month.value),
)
const days = computed(() => calendarMonth(month.value))
const weekdays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']
const events = computed(() => {
  const byDate = new Map<string, { card: Card; label: string }[]>()
  for (const { card } of rows.value) {
    for (const { date, label } of datesFor(card)) {
      const day = byDate.get(date) ?? []
      day.push({ card, label })
      byDate.set(date, day)
    }
  }
  return byDate
})

function changeMonth(direction: number) {
  month.value = new Date(month.value.getFullYear(), month.value.getMonth() + direction, 1)
}
function displayDate(date: string) {
  return date.split('-').reverse().join('.')
}
function toggle(card: Card, event: Event) {
  const checkbox = event.target as HTMLInputElement
  const completed = checkbox.checked
  checkbox.checked = Boolean(card.completed)
  emit('complete', card, completed)
}
</script>

<template>
  <section v-if="view === 'calendar'" class="calendar" aria-label="Календарь карточек">
    <div class="calendar-head">
      <h2 aria-live="polite">{{ monthName }}</h2>
      <div class="actions">
        <button class="secondary" @click="month = new Date()">Сегодня</button>
        <button class="secondary" aria-label="Предыдущий месяц" @click="changeMonth(-1)">‹</button>
        <button class="secondary" aria-label="Следующий месяц" @click="changeMonth(1)">›</button>
      </div>
    </div>
    <div class="calendar-scroll">
      <div class="calendar-grid">
        <span v-for="day in weekdays" :key="day" class="calendar-weekday">{{ day }}</span>
        <div v-for="(day, index) in days" :key="index" class="calendar-day">
          <template v-if="day">
            <time
              :datetime="day.date"
              :aria-current="day.date === dateKey(new Date()) ? 'date' : undefined"
            >
              {{ day.day }}
            </time>
            <button
              v-for="{ card, label } in events.get(day.date)"
              :key="card.id"
              class="calendar-event"
              @click="emit('open', card)"
            >
              <small>{{ label }}</small>
              {{ card.title }}
            </button>
          </template>
        </div>
      </div>
    </div>
  </section>
  <div v-else-if="!rows.length" class="empty"><h2>На доске пока нет карточек</h2></div>
  <div v-else-if="view === 'list'" class="card-list">
    <table aria-label="Список карточек">
      <thead>
        <tr>
          <th scope="col">Карточка</th>
          <th scope="col">Колонка</th>
          <th scope="col">Начало</th>
          <th scope="col">Срок</th>
          <th scope="col">Статус</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="{ card, column } in rows" :key="card.id">
          <td>
            <button class="text row-title" @click="emit('open', card)">{{ card.title }}</button>
          </td>
          <td>{{ column.title }}</td>
          <td>
            <time v-if="card.start_date" :datetime="card.start_date">{{
              displayDate(card.start_date)
            }}</time><span v-else>—</span>
          </td>
          <td>
            <time v-if="card.due_date" :datetime="card.due_date">{{
              displayDate(card.due_date)
            }}</time><span v-else>—</span>
          </td>
          <td>{{ card.completed ? 'Выполнено' : 'Открыто' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div v-else class="tasks">
    <p v-if="memberError" class="error" role="status">Не удалось загрузить имена исполнителей.</p>
    <section v-for="group in tasks" :key="group.title" class="task-group">
      <h2>
        {{ group.title }} <small>{{ group.rows.length }}</small>
      </h2>
      <p v-if="!group.rows.length">Нет дел</p>
      <ul v-else>
        <li v-for="{ card, column } in group.rows" :key="card.id">
          <input
            type="checkbox"
            :checked="card.completed"
            :disabled="!canComplete || busy"
            :aria-label="'Выполнение: ' + card.title"
            @change="toggle(card, $event)"
          />
          <div>
            <button class="text row-title" @click="emit('open', card)">{{ card.title }}</button>
            <small>{{ column.title }}</small>
            <small>Исполнители: {{ assignees(card) }}</small>
          </div>
          <time v-if="card.due_date" :datetime="card.due_date">{{
            displayDate(card.due_date)
          }}</time>
        </li>
      </ul>
    </section>
  </div>
</template>
