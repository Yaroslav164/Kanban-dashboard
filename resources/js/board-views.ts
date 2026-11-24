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

export function sortTasks(a: Pick<Card, 'due_date'>, b: Pick<Card, 'due_date'>) {
  return (a.due_date ?? '9999-12-31').localeCompare(b.due_date ?? '9999-12-31')
}

export interface CardFilters {
  query: string
  assignee: '' | 'unassigned' | number
  label: string
  deadline: '' | 'overdue' | 'today' | 'dated' | 'undated'
  status: '' | 'open' | 'complete'
}

export function emptyCardFilters(): CardFilters {
  return { query: '', assignee: '', label: '', deadline: '', status: '' }
}

export function matchesCard(card: Card, filters: CardFilters, today: string) {
  const query = filters.query.trim().toLocaleLowerCase()
  if (
    query &&
    ![card.title, card.description].some((field) => field?.toLocaleLowerCase().includes(query))
  )
    return false
  if (filters.label && !card.labels.some(({ name }) => name === filters.label)) return false
  if (filters.assignee === 'unassigned' && card.assignee_ids.length) return false
  if (typeof filters.assignee === 'number' && !card.assignee_ids.includes(filters.assignee))
    return false
  if (filters.status && card.completed !== (filters.status === 'complete')) return false
  const due = card.due_date
  switch (filters.deadline) {
    case 'overdue':
      return !!due && !card.completed && due < today
    case 'today':
      return due === today
    case 'dated':
      return !!due
    case 'undated':
      return !due
    default:
      return true
  }
}

export interface AssignedTask {
  id: number
  title: string
  board_id: number
  board_title: string
  column_id: number
  column_title: string
  due_date: string | null
}

export function groupAssignedTasks(tasks: AssignedTask[], now: Date) {
  const today = dateKey(now)
  const sunday = new Date(
    now.getFullYear(),
    now.getMonth(),
    now.getDate() + ((7 - now.getDay()) % 7),
  )
  const endOfWeek = dateKey(sunday)
  const groups = [
    { title: 'Просрочено', tasks: [] as AssignedTask[] },
    { title: 'Сегодня', tasks: [] as AssignedTask[] },
    { title: 'На этой неделе', tasks: [] as AssignedTask[] },
    { title: 'Позже', tasks: [] as AssignedTask[] },
    { title: 'Без срока', tasks: [] as AssignedTask[] },
  ]
  for (const task of [...tasks].sort(sortTasks)) {
    const due = task.due_date
    if (!due) groups[4].tasks.push(task)
    else if (due < today) groups[0].tasks.push(task)
    else if (due === today) groups[1].tasks.push(task)
    else if (due <= endOfWeek) groups[2].tasks.push(task)
    else groups[3].tasks.push(task)
  }
  return groups
}
