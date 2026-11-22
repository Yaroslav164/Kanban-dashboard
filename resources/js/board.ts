import { defineStore } from 'pinia'
import { ref } from 'vue'
import { HttpError, request } from './api'
import { gap } from './ordering'

export interface Summary {
  id: number
  title: string
}
export interface Card extends Summary {
  column_id: number
  description: string | null
  position: number
}
export interface Column extends Summary {
  cards: Card[]
}
export interface Board extends Summary {
  columns: Column[]
}
export interface User {
  id: number
  email: string
  name: string
}

export const useBoard = defineStore('workspace', () => {
  const boards = ref<Summary[]>([])
  const active = ref<Board | null>(null)
  const selected = ref<number | null>(null)
  let version = 0
  async function refresh() {
    const seq = ++version
    const list = await request<Summary[]>('/api/boards')
    if (seq !== version) return
    boards.value = list
    const current = selected.value
    if (!current || !list.some((b) => b.id === current)) {
      selected.value = null
      active.value = null
      return
    }
    try {
      const snapshot = await request<Board>('/api/boards/' + current)
      if (seq === version) active.value = snapshot
    } catch (e) {
      if (!(e instanceof HttpError && e.status === 404)) throw e
      if (seq === version) {
        selected.value = null
        active.value = null
        await refresh()
      }
    }
  }
  async function select(id: number | null) {
    selected.value = id
    if (active.value?.id !== id) active.value = null
    await refresh()
  }
  function reset() {
    version++
    active.value = null
    selected.value = null
    boards.value = []
  }
  async function move(id: number, column: Column, before: number | null) {
    if (before === id) return
    const body = {
      column_id: column.id,
      ...gap(
        column.cards.map((c) => c.id),
        id,
        before,
      ),
    }
    try {
      await request('/api/cards/' + id + '/move', 'POST', body)
    } finally {
      await refresh()
    }
  }
  return { active, boards, selected, refresh, select, reset, move }
})
