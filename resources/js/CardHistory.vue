<script setup lang="ts">
import { ref, watch } from 'vue'
import { request } from './api'
import type { Card } from './board'

const props = defineProps<{ card: Card }>()
const descriptions = {
  moved: 'Перемещение',
  start_date: 'Дата начала',
  due_date: 'Срок',
  assignees: 'Изменение исполнителей',
  completed: 'Задача выполнена',
  reopened: 'Задача снова в работе',
}
interface HistoryEntry {
  id: number
  kind: keyof typeof descriptions
  detail: string | null
  actor_name: string | null
  created_at: string
}
const expanded = ref(false)
const entries = ref<HistoryEntry[]>([])
const status = ref<'loading' | 'ready' | 'error'>('loading')
const formatDate = new Intl.DateTimeFormat('ru', { dateStyle: 'short', timeStyle: 'short' })

watch([expanded, () => props.card], async ([open, card], _, cleanup) => {
  if (!open) return
  let active = true
  cleanup(() => (active = false))
  status.value = 'loading'
  try {
    const result = await request<HistoryEntry[]>(`/api/cards/${card.id}/history`)
    if (!active) return
    entries.value = result
    status.value = 'ready'
  } catch {
    if (active) status.value = 'error'
  }
})
</script>

<template>
  <details
    class="card-history"
    @toggle="expanded = ($event.currentTarget as HTMLDetailsElement).open"
  >
    <summary>История изменений</summary>
    <p v-if="status === 'loading'">Загрузка истории…</p>
    <p v-else-if="status === 'error'" role="alert">Не удалось загрузить историю.</p>
    <p v-else-if="!entries.length">Изменений пока нет.</p>
    <ul v-else class="card-history-list">
      <li v-for="entry in entries" :key="entry.id">
        <strong
          >{{ descriptions[entry.kind]
          }}<template v-if="entry.detail"> · {{ entry.detail }}</template></strong
        >
        <small
          >{{ entry.actor_name ?? 'Удалённый пользователь' }} ·
          <time :datetime="entry.created_at">{{
            formatDate.format(new Date(entry.created_at))
          }}</time></small
        >
      </li>
    </ul>
  </details>
</template>

<style scoped>
.card-history summary {
  cursor: pointer;
  font-weight: 600;
}
.card-history-list {
  display: grid;
  gap: 0.5rem;
  padding-inline-start: 1.25rem;
}
.card-history-list small {
  display: block;
}
</style>
