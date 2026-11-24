import { createApp, nextTick } from 'vue'
import { afterEach, expect, it, vi } from 'vitest'
import Inbox from './Inbox.vue'
import { request } from './api'

vi.mock('./api', () => ({ request: vi.fn() }))
let unmount: (() => void) | undefined
afterEach(() => {
  unmount?.()
  vi.resetAllMocks()
})

it('открывает карточку из уведомления и помечает уведомление прочитанным', async () => {
  const entry = {
    id: 3,
    kind: 'mentioned',
    board_id: 4,
    card_id: 5,
    card_title: 'Отчёт',
    read: false,
  }
  vi.mocked(request).mockImplementation(async (_url, method) => {
    if (method === 'POST') {
      entry.read = true
      return
    }
    return [{ ...entry }]
  })
  const host = document.createElement('div')
  const onOpen = vi.fn()
  const app = createApp(Inbox, { onOpen })
  app.mount(host)
  unmount = () => app.unmount()
  await vi.waitFor(() => expect(host.querySelector('.unread')?.textContent).toBe('1'))
  host.querySelector('button')?.click()
  await vi.waitFor(() => expect(onOpen).toHaveBeenCalledWith({ boardId: 4, cardId: 5 }))
  await nextTick()
  expect(request).toHaveBeenCalledWith('/api/me/notifications/3/read', 'POST')
  expect(host.querySelector('.unread')).toBeNull()
})
