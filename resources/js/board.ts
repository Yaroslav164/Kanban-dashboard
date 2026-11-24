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
  start_date: string | null
  due_date: string | null
  completed: boolean
  labels: CardLabel[]
  assignee_ids: number[]
  checklists: CardChecklist[]
}
export interface CardLabel {
  name: string
  color: string
}
export interface CardChecklist {
  id: string
  title: string
  items: { id: string; title: string; completed: boolean }[]
}
export interface Column extends Summary {
  cards: Card[]
  wip_limit?: number | null
}
export const permissionLabels = {
  'board.rename': 'Переименовывать доску',
  'columns.create': 'Создавать колонки',
  'columns.edit': 'Переименовывать колонки',
  'columns.delete': 'Удалять колонки вместе с карточками',
  'cards.create': 'Создавать карточки',
  'cards.edit': 'Редактировать карточки и их содержимое',
  'cards.move': 'Перемещать карточки',
  'cards.delete': 'Удалять карточки',
  'cards.comment': 'Добавлять комментарии',
  'cards.attach': 'Управлять вложениями',
} as const
export type Permission = keyof typeof permissionLabels
export type BoardRole = 'owner' | 'editor' | 'viewer' | `custom-${number}`
export interface CustomRole {
  key: `custom-${number}`
  id: number
  name: string
  permissions: Permission[]
}
export type MemberRole = Exclude<BoardRole, 'owner'>
export interface BoardSummary extends Summary {
  permissions?: Permission[]
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
  const summary = computed(() => boards.value.find((board) => board.id === selected.value))
  const role = computed(() => {
    const board = active.value
    if (!board || board.id !== selected.value || !summary.value) return null
    return summary.value.role === board.role ? board.role : 'viewer'
  })

  function grants(board: BoardSummary, permission: Permission) {
    return (
      board.permissions?.includes(permission) ?? (board.role === 'owner' || board.role === 'editor')
    )
  }

  function can(permission: Permission) {
    const board = active.value
    return (
      !!board &&
      board.id === selected.value &&
      !!summary.value &&
      grants(summary.value, permission) &&
      grants(board, permission)
    )
  }
  const canEdit = computed(() => can('cards.edit'))
  let version = 0
  let latestRefresh: Promise<void> | undefined
  async function loadBoards() {
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
  function refresh() {
    latestRefresh = loadBoards()
    return latestRefresh
  }
  async function select(id: number | null) {
    selected.value = id
    if (active.value?.id !== id) active.value = null
    let pending = refresh()
    await pending
    while (selected.value === id && latestRefresh && latestRefresh !== pending) {
      pending = latestRefresh
      await pending
    }
  }
  function reset() {
    version++
    latestRefresh = undefined
    active.value = null
    selected.value = null
    boards.value = []
  }
  async function move(id: number, column: Column, before: number | null) {
    if (!can('cards.move') || before === id) return
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
  return { active, boards, selected, role, can, canEdit, refresh, select, reset, move }
})
