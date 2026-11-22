import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
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
export type BoardRole = 'owner' | 'editor' | 'viewer'
export type MemberRole = Exclude<BoardRole, 'owner'>
export interface BoardSummary extends Summary {
  role: BoardRole
}
export interface Board extends BoardSummary {
  columns: Column[]
}
export interface User {
  id: number
  email: string
  name: string
}
export interface BoardMember extends User {
  role: BoardRole
}

export const useBoard = defineStore('workspace', () => {
  const boards = ref<BoardSummary[]>([])
  const active = ref<Board | null>(null)
  const selected = ref<number | null>(null)
  const role = computed(() => {
    const board = active.value
    if (!board || board.id !== selected.value) return null
    const summary = boards.value.find((item) => item.id === board.id)
    if (!summary) return null
    if (summary.role === 'owner' && board.role === 'owner') return 'owner'
    if (
      (summary.role === 'owner' || summary.role === 'editor') &&
      (board.role === 'owner' || board.role === 'editor')
    )
      return 'editor'
    return 'viewer'
  })
  const canEdit = computed(() => role.value === 'owner' || role.value === 'editor')
  let version = 0
  async function refresh() {
    const seq = ++version
    const list = await request<BoardSummary[]>('/api/boards')
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
    if (!canEdit.value || before === id) return
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
  return { active, boards, selected, role, canEdit, refresh, select, reset, move }
})
