import { describe, expect, it } from 'vitest'
import { gap } from './ordering'

describe('карточки при перетаскивании', () => {
  it('исключает перемещаемую карточку перед вычислением соседей', () => {
    expect(gap([1, 2, 3], 1, 3)).toEqual({ previous_card_id: 2, next_card_id: 3 })
  })
  it('переносит в пустую колонку и в конец', () => {
    expect(gap([], 4, null)).toEqual({ previous_card_id: null, next_card_id: null })
    expect(gap([2], 4, null)).toEqual({ previous_card_id: 2, next_card_id: null })
  })
})
