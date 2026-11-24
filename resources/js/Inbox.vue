<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { request } from './api'

const props = defineProps<{ revision?: number }>()
const emit = defineEmits<{ open: [target: { boardId: number; cardId: number }] }>()
const labels = {
  assigned: 'Вам назначили задачу',
  mentioned: 'Вас упомянули в комментарии',
  due_soon: 'Приближается срок задачи',
}
interface Notification {
  id: number
  kind: keyof typeof labels
  board_id: number
  card_id: number
  card_title: string
  read: boolean
}
const entries = ref<Notification[]>([])
const unread = computed(() => entries.value.filter((entry) => !entry.read).length)
const loading = ref(true)
const error = ref(false)
const pending = ref<number | null>(null)
let timer: ReturnType<typeof setInterval> | undefined
let requestSequence = 0

async function refresh() {
  const current = ++requestSequence
  try {
    const result = await request<Notification[]>('/api/me/notifications')
    if (current === requestSequence) {
      entries.value = result
      error.value = false
    }
  } catch {
    if (current === requestSequence) error.value = true
  } finally {
    if (current === requestSequence) loading.value = false
  }
}

async function open(entry: Notification) {
  if (pending.value !== null) return
  pending.value = entry.id
  try {
    if (!entry.read) {
      await request(`/api/me/notifications/${entry.id}/read`, 'POST')
      entries.value = entries.value.map((item) =>
        item.id === entry.id ? { ...item, read: true } : item,
      )
      void refresh()
    }
    emit('open', { boardId: entry.board_id, cardId: entry.card_id })
  } catch {
    error.value = true
  } finally {
    pending.value = null
  }
}

watch(() => props.revision, refresh)
onMounted(() => {
  void refresh()
  timer = setInterval(() => {
    if (!document.hidden) void refresh()
  }, 60000)
})
onUnmounted(() => {
  ++requestSequence
  clearInterval(timer)
})
</script>

<template>
  <details class="inbox">
    <summary>
      Уведомления <span v-if="unread" class="unread">{{ unread }}</span>
    </summary>
    <p v-if="error" role="alert">
      Не удалось обновить уведомления. <button type="button" @click="refresh">Повторить</button>
    </p>
    <p v-else-if="loading">Загрузка…</p>
    <p v-else-if="!entries.length">Уведомлений пока нет.</p>
    <ul v-if="entries.length">
      <li v-for="entry in entries" :key="entry.id">
        <button type="button" :disabled="pending !== null" @click="open(entry)">
          <strong>{{ entry.card_title }}</strong>
          <small>{{ labels[entry.kind] }}<span v-if="!entry.read"> · новое</span></small>
        </button>
      </li>
    </ul>
  </details>
</template>

<style scoped>
.inbox summary {
  cursor: pointer;
}
.inbox ul {
  display: grid;
  gap: 4px;
  list-style: none;
  margin: 8px 0 0;
  padding: 0;
  max-height: 16rem;
  overflow: auto;
}
.inbox li button {
  display: grid;
  justify-items: start;
  text-align: left;
  width: 100%;
  overflow-wrap: anywhere;
}
.unread {
  font-weight: 700;
}
</style>
