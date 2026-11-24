<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { HttpError, request } from './api'
import type { Column, Summary } from './board'

interface ArchivedCard extends Summary {
  column_id: number
  column_title: string
}

const props = defineProps<{
  boardId: number
  columns: Column[]
  canRestore: boolean
  revision: number
}>()
const emit = defineEmits<{ changed: [] }>()
const dialog = ref<HTMLDialogElement>()
const opened = ref(false)
const cards = ref<ArchivedCard[]>([])
const destinations = ref<Record<number, number | ''>>({})
const loading = ref(false)
const restoring = ref<number | null>(null)
const loadError = ref('')
const actionError = ref('')
const notice = ref('')
const retry = ref(0)
const activeColumns = computed(() => new Set(props.columns.map(({ id }) => id)))

function show() {
  actionError.value = ''
  notice.value = ''
  dialog.value?.showModal()
  opened.value = true
}

watch(
  () => [opened.value, props.revision, retry.value] as const,
  async ([visible], _, onCleanup) => {
    if (!visible) return
    let current = true
    onCleanup(() => {
      current = false
    })
    loading.value = true
    loadError.value = ''
    try {
      const items = await request<ArchivedCard[]>('/api/boards/' + props.boardId + '/archive')
      if (current) cards.value = items
    } catch (error) {
      if (current)
        loadError.value = error instanceof Error ? error.message : 'Не удалось загрузить архив.'
    } finally {
      if (current) loading.value = false
    }
  },
)

async function restore(card: ArchivedCard) {
  const destination = restoreDestination(card)
  if (!props.canRestore || restoring.value !== null || destination === null) return
  restoring.value = card.id
  actionError.value = ''
  notice.value = ''
  try {
    await request(
      '/api/cards/' + card.id + '/restore',
      'POST',
      destination !== card.column_id ? { column_id: destination } : undefined,
    )
    cards.value = cards.value.filter(({ id }) => id !== card.id)
    delete destinations.value[card.id]
    notice.value = 'Карточка восстановлена.'
    emit('changed')
    retry.value++
  } catch (error) {
    actionError.value =
      error instanceof HttpError && (error.status === 404 || error.status === 409)
        ? 'Архив изменился. Проверьте выбранную колонку и повторите.'
        : 'Не удалось восстановить карточку.'
    emit('changed')
  } finally {
    restoring.value = null
  }
}

function restoreDestination(card: ArchivedCard) {
  if (activeColumns.value.has(card.column_id)) return card.column_id
  const selected = destinations.value[card.id]
  return selected && activeColumns.value.has(selected) ? selected : null
}
</script>

<template>
  <button class="secondary" @click="show">Архив</button>
  <dialog ref="dialog" class="board-archive" aria-label="Архив карточек" @close="opened = false">
    <div class="archive-header">
      <h2>Архив карточек</h2>
      <button type="button" class="secondary" aria-label="Закрыть архив" @click="dialog?.close()">
        ×
      </button>
    </div>
    <p v-if="notice" role="status">{{ notice }}</p>
    <p v-if="actionError" class="error" role="alert">{{ actionError }}</p>
    <p v-if="loadError" class="error" role="alert">
      {{ loadError }} <button type="button" class="secondary" @click="retry++">Повторить</button>
    </p>
    <p v-if="loading" role="status">Загрузка архива…</p>
    <ul v-if="cards.length" class="archive-list" :aria-busy="loading">
      <li v-for="card in cards" :key="card.id">
        <div>
          <strong>{{ card.title }}</strong><small>{{ card.column_title }}</small>
        </div>
        <template v-if="canRestore">
          <label v-if="!activeColumns.has(card.column_id) && columns.length">
            Колонка для «{{ card.title }}»
            <select v-model="destinations[card.id]">
              <option value="">Выберите колонку</option>
              <option v-for="column in columns" :key="column.id" :value="column.id">
                {{ column.title }}
              </option>
            </select>
          </label>
          <p v-else-if="!columns.length">Добавьте колонку на доску, чтобы восстановить карточку.</p>
          <button
            type="button"
            class="secondary"
            :disabled="restoring !== null || restoreDestination(card) === null"
            :aria-label="'Восстановить ' + card.title"
            @click="restore(card)"
          >
            {{ restoring === card.id ? 'Восстановление…' : 'Восстановить' }}
          </button>
        </template>
      </li>
    </ul>
    <p v-else-if="!loading && !loadError">В архиве пока нет карточек.</p>
  </dialog>
</template>

<style scoped>
.board-archive {
  padding: 20px;
}
.archive-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
}
.archive-header h2 {
  margin: 0;
}
.archive-header button {
  min-width: 44px;
}
.archive-list {
  list-style: none;
  padding: 0;
}
.archive-list li {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 10px;
  padding: 12px 0;
  border-top: 1px solid #d4dbd2;
  overflow-wrap: anywhere;
}
.archive-list small {
  display: block;
}
.archive-list label {
  min-width: 0;
  margin: 0;
}
.archive-list select {
  width: 100%;
}
.secondary,
.archive-list select {
  min-height: 44px;
}
</style>
