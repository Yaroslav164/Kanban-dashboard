import { describe, expect, it } from 'vitest'
import type { Card } from './board'
import { calendarMonth, datesFor, sortTasks } from './board-views'

function card(id: number, dates: { start_date?: string; due_date?: string } = {}): Card {
  return {
    id,
    title: 'Задача ' + id,
    column_id: 1,
    description: null,
    position: id,
    completed: false,
    labels: [],
    assignee_ids: [],
    checklists: [],
    start_date: null,
    due_date: null,
    ...dates,
  }
}

describe('представления доски', () => {
  it('показывает февраль високосного года с понедельника', () => {
    const days = calendarMonth(new Date(2024, 1, 1))
    expect(days).toHaveLength(35)
    expect(days.slice(0, 3)).toEqual([null, null, null])
    expect(days[3]).toEqual({ date: '2024-02-01', day: 1 })
    expect(days[31]).toEqual({ date: '2024-02-29', day: 29 })
    expect(days[34]).toBeNull()
  })

  it('объединяет начало и срок на одной дате', () => {
    expect(datesFor(card(1, { start_date: '2026-12-01', due_date: '2026-12-01' }))).toEqual([
      { date: '2026-12-01', label: 'Начало и срок' },
    ])
    expect(datesFor(card(2, { due_date: '2026-12-02' }))).toEqual([
      { date: '2026-12-02', label: 'Срок' },
    ])
    expect(datesFor(card(3))).toEqual([])
  })

  it('размещает дела с ближайшим сроком выше дел без срока', () => {
    const cards = [
      card(1),
      card(2, { due_date: '2027-01-01' }),
      card(3, { due_date: '2026-12-01' }),
      card(4),
    ]
    expect([...cards].sort(sortTasks).map(({ id }) => id)).toEqual([3, 2, 1, 4])
  })
})
