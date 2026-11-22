import { test, expect, type Page } from '@playwright/test'
import { randomUUID } from 'node:crypto'

async function createItem(page: Page, button: string, title: string) {
  await page.getByRole('button', { name: button, exact: true }).first().click()
  const dialog = page.getByRole('dialog')
  await dialog.getByLabel('Название').fill(title)
  await dialog.getByRole('button', { name: 'Сохранить' }).click()
  await expect(dialog).toBeHidden()
}

test('создание, перенос, push во вторую вкладку и порядок после перезагрузки', async ({
  page,
  context,
}) => {
  await page.goto('/')
  await page.getByRole('button', { name: 'Создать аккаунт' }).click()
  await page.getByLabel('Имя', { exact: true }).fill('Мария')
  await page.getByLabel('Электронная почта').fill(randomUUID() + '@example.test')
  await page.getByLabel('Пароль', { exact: true }).fill('Test-pass123')
  await page.getByLabel('Повторите пароль').fill('Test-pass123')
  await page.getByRole('button', { name: 'Создать аккаунт', exact: true }).click()
  await createItem(page, '＋ Создать доску', 'Релиз')
  await createItem(page, '＋ Колонка', 'В работе')
  await createItem(page, '＋ Колонка', 'Готово')
  await createItem(page, '＋ Добавить карточку', 'Проверить сборку')
  const second = await context.newPage()
  await second.goto(page.url())
  const source = page
    .locator('.lane')
    .filter({ has: page.getByRole('heading', { name: 'В работе' }) })
  const dest = page.locator('.lane').filter({ has: page.getByRole('heading', { name: 'Готово' }) })
  await expect(second.locator('article')).toHaveText(/Проверить сборку/)
  await source.locator('article').dragTo(dest.getByRole('button', { name: 'Добавить карточку' }))
  await expect(dest.locator('article')).toHaveText(/Проверить сборку/)
  await expect(
    second
      .locator('.lane')
      .filter({ has: second.getByRole('heading', { name: 'Готово' }) })
      .locator('article'),
  ).toHaveText(/Проверить сборку/)
  await page.reload()
  await expect(dest.locator('article')).toHaveText(/Проверить сборку/)
})
