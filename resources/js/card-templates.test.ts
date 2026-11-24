import { createApp, nextTick, type Component } from 'vue'
import { afterEach, expect, it, vi } from 'vitest'
import { request } from './api'
import CardTemplatePicker from './CardTemplatePicker.vue'
import SaveCardTemplate from './SaveCardTemplate.vue'

vi.mock('./api', () => ({ request: vi.fn() }))

const cleanups: (() => void)[] = []
afterEach(() => {
  cleanups.splice(0).forEach((cleanup) => cleanup())
  vi.resetAllMocks()
})

function mount(component: Component, props: Record<string, unknown>) {
  const app = createApp(component, props)
  const host = document.createElement('div')
  document.body.append(host)
  app.mount(host)
  cleanups.push(() => {
    app.unmount()
    host.remove()
  })
  return host
}

it('сохраняет шаблон после исправления ошибки и обновляет статус', async () => {
  vi.mocked(request).mockRejectedValueOnce(new Error('Имя занято')).mockResolvedValueOnce({ id: 7 })
  const host = mount(SaveCardTemplate, { boardId: 2, cardId: 3, title: 'Задача' })
  host.querySelector('button')!.click()
  await nextTick()
  const name = host.querySelector('input')!
  expect(name.value).toBe('Задача')
  const form = host.querySelector('form')!
  form.dispatchEvent(new Event('submit', { cancelable: true }))
  await vi.waitFor(() => expect(host.textContent).toContain('Имя занято'))
  name.value = 'Рабочий шаблон'
  name.dispatchEvent(new Event('input'))
  form.dispatchEvent(new Event('submit', { cancelable: true }))
  await vi.waitFor(() => expect(host.textContent).toContain('Шаблон сохранён'))
  expect(request).toHaveBeenLastCalledWith('/api/boards/2/card-templates', 'POST', {
    name: 'Рабочий шаблон',
    card_id: 3,
  })
})

it('повторяет загрузку шаблонов и отдаёт выбранный шаблон или пустой выбор', async () => {
  const template = { id: 7, name: 'Шаблон', title: 'Задача', description: null }
  vi.mocked(request).mockRejectedValueOnce(new Error('Сбой')).mockResolvedValueOnce([template])
  const onSelect = vi.fn()
  const host = mount(CardTemplatePicker, { boardId: 2, onSelect })
  await vi.waitFor(() => expect(host.textContent).toContain('Не удалось загрузить'))
  host.querySelector('button')!.click()
  await vi.waitFor(() => expect(host.querySelector('select')).not.toBeNull())
  const select = host.querySelector('select')!
  select.selectedIndex = 1
  select.dispatchEvent(new Event('change'))
  expect(onSelect).toHaveBeenLastCalledWith(template)
  select.selectedIndex = 0
  select.dispatchEvent(new Event('change'))
  expect(onSelect).toHaveBeenLastCalledWith(null)
})
