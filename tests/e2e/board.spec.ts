import { test, expect, type Page } from '@playwright/test'
import { randomUUID } from 'node:crypto'

async function createItem(page: Page, button: string, title: string) {
  await page.getByRole('button', { name: button, exact: true }).first().click()
  const dialog = page.getByRole('dialog')
  await dialog.getByLabel('Название').fill(title)
  await dialog.getByRole('button', { name: 'Сохранить' }).click()
  await expect(dialog).toBeHidden()
}

async function register(page: Page, name: string) {
  const email = randomUUID() + '@example.test'
  await page.goto('/')
  await page.getByRole('button', { name: 'Создать аккаунт' }).click()
  await page.getByLabel('Имя', { exact: true }).fill(name)
  await page.getByLabel('Электронная почта').fill(email)
  await page.getByLabel('Пароль', { exact: true }).fill('Test-pass123')
  await page.getByLabel('Повторите пароль').fill('Test-pass123')
  await page.getByRole('button', { name: 'Создать аккаунт', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Мои доски' })).toBeVisible()
  return email
}

test('создание, перенос, push во вторую вкладку и порядок после перезагрузки', async ({
  page,
  context,
}) => {
  await register(page, 'Мария')
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

test('два участника видят правки, смену роли и отзыв доступа в открытой доске', async ({
  page: owner,
  browser,
  baseURL,
}) => {
  const member = await browser.newPage({ baseURL })
  try {
    const email = await register(member, 'Редактор')
    await register(owner, 'Владелец')
    await createItem(owner, '＋ Создать доску', 'Общий проект')
    await owner.getByRole('button', { name: 'Команда', exact: true }).click()
    const team = owner.getByRole('dialog', { name: 'Команда доски' })
    await team.getByLabel('Email участника').fill(email)
    await team.getByLabel('Роль нового участника').selectOption('editor')
    await team.getByRole('button', { name: 'Добавить участника' }).click()
    const assigned = team.getByLabel('Роль для ' + email, { exact: true })
    await expect(assigned).toHaveValue('editor')

    await member.getByRole('button', { name: 'Общий проект', exact: true }).first().click()
    await createItem(member, '＋ Колонка', 'Совместная работа')
    await owner.keyboard.press('Escape')
    await expect(owner.getByRole('heading', { name: 'Совместная работа' })).toBeVisible()
    await owner.getByRole('button', { name: 'Команда', exact: true }).click()
    await assigned.selectOption('viewer')
    await expect(member.getByRole('button', { name: '＋ Колонка' })).toBeHidden()
    await expect(member.getByRole('heading', { name: 'Совместная работа' })).toBeVisible()
    await team.getByRole('button', { name: 'Удалить ' + email + ' из команды' }).click()
    await expect(member.getByRole('heading', { name: 'Мои доски' })).toBeVisible()
    await expect(member.getByRole('button', { name: 'Общий проект', exact: true })).toHaveCount(0)
  } finally {
    await member.close()
  }
})
