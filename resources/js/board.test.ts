import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { HttpError, request } from './api'
import { useBoard } from './board'

vi.mock('./api', async (original) => ({
  ...(await original<typeof import('./api')>()),
  request: vi.fn(),
}))
const api = vi.mocked(request)
const summary = { id: 1, title: 'Work' }
const snapshot = { ...summary, columns: [{ id: 2, title: 'Queue', cards: [] }] }
beforeEach(() => {
  setActivePinia(createPinia())
  api.mockReset()
})
describe('workspace store', () => {
  it('refreshes the saved order after an accepted move', async () => {
    api.mockResolvedValueOnce([summary]).mockResolvedValueOnce(snapshot)
    const store = useBoard()
    await store.select(1)
    api.mockResolvedValueOnce({}).mockResolvedValueOnce([summary]).mockResolvedValueOnce(snapshot)
    await store.move(7, snapshot.columns[0], null)
    expect(api).toHaveBeenCalledWith('/api/cards/7/move', 'POST', {
      column_id: 2,
      previous_card_id: null,
      next_card_id: null,
    })
    expect(store.active).toEqual(snapshot)
  })
  it('reloads the board after a conflict and surfaces the failure', async () => {
    const store = useBoard()
    store.selected = 1
    api
      .mockRejectedValueOnce(new HttpError(409, 'stale'))
      .mockResolvedValueOnce([summary])
      .mockResolvedValueOnce(snapshot)
    await expect(store.move(7, snapshot.columns[0], null)).rejects.toThrow('stale')
    expect(store.active).toEqual(snapshot)
  })
  it('clears an open board deleted on another device', async () => {
    const store = useBoard()
    api.mockResolvedValueOnce([summary]).mockResolvedValueOnce(snapshot)
    await store.select(1)
    api.mockResolvedValueOnce([])
    await store.refresh()
    expect(store.active).toBeNull()
  })
  it('discards a delayed snapshot from a previous selection', async () => {
    let finish!: (value: unknown) => void
    api.mockResolvedValueOnce([summary]).mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          finish = resolve
        }),
    )
    const store = useBoard()
    const first = store.select(1)
    await vi.waitFor(() => expect(finish).toBeTypeOf('function'))
    api.mockResolvedValueOnce([])
    await store.select(null)
    finish(snapshot)
    await first
    expect(store.active).toBeNull()
  })
})
