import { expect, test } from '@playwright/test'
import type { Card } from '../../resources/js/board'

test('заголовок остаётся на месте при независимой прокрутке карточки и комментариев', async ({
  page,
}) => {
  const user = { id: 1, email: 'layout@example.test', name: 'Мария' }
  const board = { id: 1, title: 'Проект', role: 'owner' }
  const card: Card = {
    id: 1,
    title: 'Длинная карточка '.repeat(14).trim(),
    description: null,
    column_id: 1,
    position: 1,
    completed: false,
    start_date: null,
    due_date: null,
    labels: [],
    assignee_ids: [],
    checklists: [
      {
        id: 'list',
        title: 'Подготовка',
        items: Array.from({ length: 14 }, (_, index) => ({
          id: 'item-' + index,
          title: 'Пункт ' + index,
          completed: false,
        })),
      },
    ],
  }
  await page.route('**/sanctum/csrf-cookie', (route) => route.fulfill({ status: 204 }))
  const data = new Map<string, unknown>([
    ['/api/auth/me', user],
    ['/api/boards', [board]],
    ['/api/boards/1', { ...board, columns: [{ id: 1, title: 'Колонка', cards: [card] }] }],
    ['/api/boards/1/members', [user]],
    [
      '/api/cards/1/activity',
      {
        attachments: [],
        comments: Array.from({ length: 18 }, (_, index) => ({
          id: index,
          author: user,
          body: 'Комментарий ' + index,
          created_at: '2026-11-24T10:00:00Z',
        })),
      },
    ],
  ])
  await page.route('**/api/**', (route) =>
    route.fulfill({ json: { data: data.get(new URL(route.request().url()).pathname) } }),
  )
  await page.routeWebSocket('**/app/**', () => {})

  await page.goto('/boards?board=1')
  await page.getByRole('button', { name: card.title, exact: true }).click()
  const dialog = page.getByRole('dialog', { name: card.title })
  const heading = dialog.getByRole('heading', { name: card.title })
  const close = dialog.getByRole('button', { name: 'Закрыть карточку' })
  const content = dialog.getByRole('region', { name: 'Содержание карточки' })
  const comments = dialog.getByRole('region', { name: 'Комментарии' })
  await expect(comments.getByText('Комментарий 17', { exact: true })).toBeAttached()

  for (const width of [1280, 768, 375]) {
    await page.setViewportSize({ width, height: 650 })
    const top = await heading.boundingBox()
    const closeTop = await close.boundingBox()
    expect(top).not.toBeNull()
    expect(closeTop).not.toBeNull()

    await comments.evaluate((node) => (node.scrollTop = 0))
    await content.evaluate((node) => (node.scrollTop = 200))
    expect(await content.evaluate((node) => node.scrollTop)).toBe(200)
    expect(await comments.evaluate((node) => node.scrollTop)).toBe(0)

    await comments.evaluate((node) => (node.scrollTop = 200))
    expect(await comments.evaluate((node) => node.scrollTop)).toBe(200)
    expect(await content.evaluate((node) => node.scrollTop)).toBe(200)
    expect(await heading.boundingBox()).toEqual(top)
    expect(await close.boundingBox()).toEqual(closeTop)
    expect(await content.evaluate((node) => node.scrollWidth)).toBe(
      await content.evaluate((node) => node.clientWidth),
    )

    const [left, right] = await Promise.all([content.boundingBox(), comments.boundingBox()])
    if (width > 640) expect(right!.x).toBeGreaterThan(left!.x)
    else expect(right!.y).toBeGreaterThan(left!.y)
  }

  await close.click()
  await expect(dialog).toBeHidden()
})
