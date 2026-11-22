export function gap(ids: number[], moving: number, before: number | null) {
  const others = ids.filter((id) => id !== moving)
  const index = before === null ? others.length : others.indexOf(before)
  if (index < 0) throw new Error('Порядок изменился. Обновите доску.')
  return { previous_card_id: others[index - 1] ?? null, next_card_id: others[index] ?? null }
}
