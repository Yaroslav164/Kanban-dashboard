import type { Card } from './board'

export function dateKey(date: Date) {
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${date.getFullYear()}-${month}-${day}`
}

export function calendarMonth(month: Date) {
  const year = month.getFullYear()
  const index = month.getMonth()
  const leading = (new Date(year, index, 1).getDay() + 6) % 7
  const days = new Date(year, index + 1, 0).getDate()
  return Array.from({ length: Math.ceil((leading + days) / 7) * 7 }, (_, cell) => {
    const day = cell - leading + 1
    return day > 0 && day <= days ? { day, date: dateKey(new Date(year, index, day)) } : null
  })
}

export function datesFor(card: Card) {
  const start = card.start_date
  const due = card.due_date
  if (start && start === due) return [{ date: start, label: 'Начало и срок' }]

  return [
    ...(start ? [{ date: start, label: 'Начало' }] : []),
    ...(due ? [{ date: due, label: 'Срок' }] : []),
  ]
}

export function sortTasks(a: Card, b: Card) {
  return (a.due_date ?? '9999-12-31').localeCompare(b.due_date ?? '9999-12-31')
}
