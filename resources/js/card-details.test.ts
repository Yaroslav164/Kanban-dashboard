import { createApp, nextTick } from 'vue'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import CardDetails from './CardDetails.vue'
import { HttpError, request } from './api'
import type { Card } from './board'

vi.mock('./api', async (importOriginal) => ({
  ...(await importOriginal<typeof import('./api')>()),
  request: vi.fn(),
}))

const card: Card = {
  id: 1,
  column_id: 1,
  title: 'Карточка',
  description: null,
  position: 1,
  start_date: null,
  due_date: null,
  completed: false,
  assignee_ids: [],
  labels: [],
  checklists: [],
}

let unmount: (() => void) | undefined

function mount(details: Card = card, userId = 7, canEdit = true) {
  unmount?.()
  const host = document.createElement('div')
  document.body.append(host)
  const app = createApp(CardDetails, {
    card: details,
    boardId: 1,
    userId,
    canEdit,
    canAttach: true,
    canComment: true,
  })
  app.mount(host)
  unmount = () => {
    app.unmount()
    host.remove()
  }
  return {
    host,
    sections: () =>
      [...host.querySelectorAll('.detail-section h3')].map((heading) => heading.textContent),
    async click(text: string) {
      await nextTick()
      const button = [...host.querySelectorAll('button')].find(
        (button) =>
          button.getAttribute('aria-label') === text || button.textContent?.trim() === text,
      )
      expect(button).toBeDefined()
      button?.click()
      await nextTick()
    },
    setValue(text: string, value: string) {
      const label = [...host.querySelectorAll('label')].find(
        (label) => label.textContent?.trim() === text,
      )
      const input = label?.querySelector('input, textarea') as HTMLInputElement
      expect(input).toBeDefined()
      input.value = value
      input.dispatchEvent(new Event('input'))
    },
  }
}

beforeEach(() => {
  localStorage.clear()
  vi.mocked(request).mockReset()
  vi.mocked(request).mockImplementation(async (url) =>
    url.endsWith('/activity') ? { comments: [], attachments: [] } : [],
  )
})

afterEach(() => {
  unmount?.()
  unmount = undefined
})

describe('блоки карточки', () => {
  it('вставляет упоминание участника в комментарий', async () => {
    vi.mocked(request).mockImplementation(async (url) =>
      url.endsWith('/members')
        ? [{ id: 2, name: 'Участник', email: 'teammate@example.test', role: 'viewer' }]
        : { comments: [], attachments: [] },
    )
    const { host, setValue } = mount()
    setValue('Комментарий', 'Посмотри задачу')
    await vi.waitFor(() => expect(host.querySelector('select')).not.toBeNull())
    const select = host.querySelector('select')!
    select.value = 'teammate@example.test'
    select.dispatchEvent(new Event('change'))
    await nextTick()
    expect(host.querySelectorAll('textarea')[1]?.value).toBe(
      'Посмотри задачу @teammate@example.test ',
    )
    expect(select.value).toBe('')
  })

  it('добавляет секции вниз и запоминает перестановку для карточки и пользователя', async () => {
    const first = mount()
    expect(first.sections()).toEqual([])
    await first.click('Даты')
    await first.click('Метки')
    await first.click('Вложения')
    expect(first.sections()).toEqual(['Даты', 'Метки', 'Вложения'])
    await first.click('Переместить выше: Вложения')
    await first.click('Переместить ниже: Даты')
    expect(first.sections()).toEqual(['Вложения', 'Даты', 'Метки'])
    await first.click('Переместить выше: Вложения')
    expect(first.sections()).toEqual(['Вложения', 'Даты', 'Метки'])
    expect(mount().sections()).toEqual(['Вложения', 'Даты', 'Метки'])
    expect(mount({ ...card, id: 2 }).sections()).toEqual([])
    expect(mount(card, 8).sections()).toEqual([])
  })

  it('показывает секции уже заполненной карточки и загруженные вложения', async () => {
    vi.mocked(request).mockImplementation(async (url) =>
      url.endsWith('/activity')
        ? { comments: [], attachments: [{ id: 1, name: 'file.txt', mime_type: 'text/plain' }] }
        : [],
    )
    const existing = mount({ ...card, due_date: '2026-11-24', assignee_ids: [3] })
    expect(existing.sections()).toEqual(['Даты', 'Участники'])
    await vi.waitFor(() => expect(existing.sections()).toEqual(['Даты', 'Участники', 'Вложения']))
  })

  it('сохраняет изменённые поля и значения для проверки конфликта', async () => {
    const mockRequest = vi.mocked(request)
    mockRequest.mockImplementation(async (url, method, input) => {
      if (method === 'PATCH') return { ...card, ...input }
      if (url.endsWith('/activity')) return { comments: [], attachments: [] }
      return []
    })
    const opened = mount()
    await opened.click('Метки')
    opened.setValue('Новая метка', '  Срочно  ')
    await opened.click('Добавить метку')
    opened.setValue('Новая метка', 'срочно')
    await opened.click('Добавить метку')
    await opened.click('Чеклисты')
    opened.setValue('Новый чеклист', 'Проверка')
    await opened.click('Добавить чеклист')
    opened.setValue('Новый пункт', 'Собрать отчёт')
    await opened.click('Добавить пункт')
    await opened.click('Даты')
    opened.setValue('Срок', '2026-12-01')
    opened.setValue('Описание', 'Новые данные')
    await opened.click('Сохранить карточку')
    expect(mockRequest).toHaveBeenCalledWith('/api/columns/1/cards/1', 'PATCH', {
      description: 'Новые данные',
      due_date: '2026-12-01',
      labels: [{ name: 'Срочно', color: '#5268c1' }],
      checklists: [
        {
          id: expect.any(String),
          title: 'Проверка',
          items: [{ id: expect.any(String), title: 'Собрать отчёт', completed: false }],
        },
      ],
      base: { description: null, due_date: null, labels: [], checklists: [] },
    })
    await vi.waitFor(() =>
      expect(opened.host.querySelector('[role="status"]')?.textContent).toContain('сохранена'),
    )
    await opened.click('Сохранить карточку')
    expect(mockRequest.mock.calls.filter(([, method]) => method === 'PATCH')).toHaveLength(1)
  })

  it('оставляет введённый комментарий при ошибке и очищает после отправки', async () => {
    const mockRequest = vi.mocked(request)
    mockRequest.mockImplementation(async (url, method) => {
      if (method === 'POST') throw new HttpError(403, 'Запрос отклонён.')
      if (url.endsWith('/activity')) return { comments: [], attachments: [] }
      return []
    })
    const opened = mount()
    opened.setValue('Комментарий', ' Письмо готово ')
    await opened.click('Отправить')
    const comments = opened.host.querySelector('[aria-label="Комментарии"]')
    await vi.waitFor(() =>
      expect(comments?.querySelector('[role="alert"]')?.textContent?.trim()).toBe(
        'Запрос отклонён.',
      ),
    )
    const entry = opened.host.querySelector<HTMLTextAreaElement>(
      '[aria-label="Комментарии"] textarea',
    )
    expect(entry?.value).toBe(' Письмо готово ')
    mockRequest.mockImplementation(async (url, method) => {
      if (method === 'POST' || method === 'DELETE') return undefined
      if (url.endsWith('/activity')) {
        return {
          comments: [
            {
              id: 11,
              body: 'Письмо готово',
              author: { id: 7, name: 'Мария' },
              created_at: '2026-11-24',
            },
          ],
          attachments: [],
        }
      }
      return []
    })
    await opened.click('Отправить')
    await vi.waitFor(() => expect(entry?.value).toBe(''))
    expect(mockRequest).toHaveBeenCalledWith('/api/cards/1/comments', 'POST', {
      body: 'Письмо готово',
    })
    await opened.click('Удалить комментарий')
    expect(mockRequest).toHaveBeenCalledWith('/api/cards/1/comments/11', 'DELETE')
  })

  it('прикрепляет файл и отклоняет размер свыше 10 МБ', async () => {
    const opened = mount()
    await opened.click('Вложения')
    const input = opened.host.querySelector<HTMLInputElement>('input[type="file"]')
    expect(input).toBeInstanceOf(HTMLInputElement)
    const largeFile = new File([], 'large.txt')
    Object.defineProperty(largeFile, 'size', { value: 10 * 1024 * 1024 + 1 })
    Object.defineProperty(input, 'files', { configurable: true, value: [largeFile] })
    await opened.click('Прикрепить')
    expect(opened.host.querySelector('[role="alert"]')?.textContent).toContain('10 МБ')
    const file = new File(['hello'], 'hello.txt')
    Object.defineProperty(input, 'files', { value: [file] })
    await opened.click('Прикрепить')
    expect(request).toHaveBeenCalledWith('/api/cards/1/attachments', 'POST', expect.any(FormData))
    const upload = vi.mocked(request).mock.calls.find(([, method]) => method === 'POST')?.[2]
    expect((upload as FormData).get('file')).toBe(file)
  })

  it('разрешает управлять вложениями отдельно от редактирования полей карточки', async () => {
    const mockRequest = vi.mocked(request)
    mockRequest.mockImplementation(async (url, method) => {
      if (method === 'DELETE') return undefined
      if (url.endsWith('/activity')) {
        return {
          comments: [],
          attachments: [{ id: 5, name: 'photo.png', mime_type: 'image/png' }],
        }
      }
      return []
    })
    const opened = mount(card, 7, false)
    await vi.waitFor(() => expect(opened.sections()).toEqual(['Вложения']))
    expect(opened.host.querySelector('fieldset')?.disabled).toBe(true)
    expect(opened.host.querySelector('img')?.getAttribute('src')).toBe(
      '/api/cards/1/attachments/5?inline=1',
    )
    await opened.click('Удалить файл')
    expect(mockRequest).toHaveBeenCalledWith('/api/cards/1/attachments/5', 'DELETE')
  })

  it('восстанавливает данные после ошибки загрузки даже с повреждённым порядком секций', async () => {
    localStorage.setItem('kanban:card-layout:7:1', 'invalid')
    vi.mocked(request).mockRejectedValueOnce(new Error('Network error'))
    const opened = mount()
    const comments = opened.host.querySelector('[aria-label="Комментарии"]')
    await vi.waitFor(() =>
      expect(comments?.querySelector('[role="alert"]')?.textContent).toContain(
        'Не удалось загрузить комментарии.',
      ),
    )
    await opened.click('Повторить')
    await vi.waitFor(() => expect(comments?.textContent).toContain('Комментариев пока нет.'))
  })

  it('показывает пустой список комментариев при сбое загрузки участников', async () => {
    vi.mocked(request).mockImplementation(async (url) => {
      if (url.endsWith('/members')) throw new Error('Network error')
      return { comments: [], attachments: [] }
    })
    const opened = mount()
    await opened.click('Участники')
    await vi.waitFor(() =>
      expect(opened.host.querySelector('[role="alert"]')?.textContent).toContain(
        'Не удалось загрузить участников.',
      ),
    )
    expect(opened.host.querySelector('[aria-label="Комментарии"]')?.textContent).toContain(
      'Комментариев пока нет.',
    )
  })
})
