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
  await expect(page.getByRole('button', { name: 'Демо-доска', exact: true }).first()).toBeVisible()
  return email
}

test('упоминание отправляет уведомление, а изменения видны в истории карточки', async ({
  page: owner,
  browser,
  baseURL,
}) => {
  const member = await browser.newPage({ baseURL })
  try {
    const email = await register(member, 'Участник')
    await register(owner, 'Автор изменений')
    await createItem(owner, '＋ Создать доску', 'Проверка уведомлений')
    await owner.getByRole('button', { name: 'Команда', exact: true }).click()
    const team = owner.getByRole('dialog', { name: 'Команда доски' })
    await team.getByLabel('Email участника').fill(email)
    await team.getByRole('button', { name: 'Добавить участника' }).click()
    await expect(team.getByLabel('Роль для ' + email, { exact: true })).toBeVisible()
    await owner.keyboard.press('Escape')
    await createItem(owner, '＋ Колонка', 'Новые')
    await createItem(owner, '＋ Добавить карточку', 'Проверить отчёт')
    await owner.getByRole('button', { name: 'Проверить отчёт', exact: true }).click()
    const card = owner.getByRole('dialog', { name: 'Проверить отчёт' })
    await card.getByLabel('Комментарий', { exact: true }).fill('Посмотрите отчёт')
    await card.getByLabel('Упомянуть участника').selectOption(email)
    await card.getByRole('button', { name: 'Отправить', exact: true }).click()
    await expect(card.getByText('Посмотрите отчёт @' + email)).toBeVisible()

    await member.getByText('Уведомления', { exact: false }).first().click()
    const mention = member.getByRole('button', { name: /Проверить отчёт.*упомянули/ })
    await expect(mention).toBeVisible()
    await mention.click()
    await expect(member.getByRole('dialog', { name: 'Проверить отчёт' })).toBeVisible()

    await card.getByRole('button', { name: 'Даты', exact: true }).click()
    await card.getByLabel('Срок', { exact: true }).fill('2030-01-06')
    await card.getByRole('button', { name: 'Сохранить карточку', exact: true }).click()
    await card.getByText('История изменений', { exact: true }).click()
    await expect(card.getByText('Срок · 2030-01-06')).toBeVisible()
  } finally {
    await member.close()
  }
})

test('лимит колонки предупреждает о превышении, архив освобождает место и позволяет восстановить карточку', async ({
  page,
}) => {
  await register(page, 'Пользователь архива')
  await createItem(page, '＋ Создать доску', 'Проверка лимита')
  await createItem(page, '＋ Колонка', 'В работе')
  await page.getByLabel('Действия с колонкой В работе').click()
  await page.getByRole('button', { name: 'Настройки колонки' }).click()
  const settings = page.getByRole('dialog')
  await settings.getByLabel('Лимит задач в работе (WIP)').fill('1')
  await settings.getByRole('button', { name: 'Сохранить', exact: true }).click()
  await expect(settings).toBeHidden()
  await createItem(page, '＋ Добавить карточку', 'Первая задача')
  await createItem(page, '＋ Добавить карточку', 'Вторая задача')
  await expect(page.locator('.wip-count')).toContainText('2 из 1')
  await expect(page.getByText('Лимит превышен')).toBeVisible()
  await page.getByLabel('Действия с карточкой Вторая задача').click()
  await page.getByRole('button', { name: 'Архивировать карточку', exact: true }).click()
  await expect(page.locator('.wip-count')).toHaveText('1 из 1')
  await page.getByRole('button', { name: 'Архив', exact: true }).click()
  const archive = page.getByRole('dialog', { name: 'Архив карточек' })
  await archive.getByRole('button', { name: 'Восстановить Вторая задача' }).click()
  await expect(archive.getByText('Карточка восстановлена.', { exact: true })).toBeVisible()
  await archive.getByRole('button', { name: 'Закрыть архив' }).click()
  await expect(page.getByRole('button', { name: 'Вторая задача', exact: true })).toBeVisible()
  await expect(page.locator('.wip-count')).toContainText('2 из 1')
})

test('находит карточки по описанию в разных видах и открывает назначенную из моих задач', async ({
  page,
}) => {
  await register(page, 'Исполнитель')
  await page.getByRole('button', { name: 'Демо-доска', exact: true }).first().click()
  const example = 'Подготовить демо проекта'
  await page.getByLabel('Поиск карточек').fill('посмотреть метки')
  await expect(page.getByRole('button', { name: example, exact: true })).toBeVisible()
  await expect(
    page.getByRole('button', { name: 'Посмотреть режимы доски', exact: true }),
  ).toHaveCount(0)
  await page.getByRole('button', { name: 'Список', exact: true }).click()
  await expect(page.getByLabel('Поиск карточек')).toHaveValue('посмотреть метки')
  await expect(page.getByRole('button', { name: example, exact: true })).toBeVisible()
  await page.getByRole('button', { name: 'Мои задачи', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Мои задачи' })).toBeVisible()
  await page.getByRole('button', { name: example, exact: true }).click()
  await expect(page.getByRole('dialog', { name: example })).toBeVisible()
})

test('сохраняет карточку как шаблон и создаёт по нему новую', async ({ page }) => {
  await register(page, 'Автор шаблона')
  await page.getByRole('button', { name: 'Демо-доска', exact: true }).first().click()
  const title = 'Подготовить демо проекта'
  await page.getByRole('button', { name: title, exact: true }).click()
  const source = page.getByRole('dialog', { name: title })
  await source.getByRole('button', { name: 'Сохранить как шаблон' }).click()
  await source.getByLabel('Название шаблона').fill('Подготовка проекта')
  await source.getByRole('button', { name: 'Сохранить шаблон' }).click()
  await expect(source.getByRole('status')).toContainText('Шаблон сохранён')
  await source.getByRole('button', { name: 'Закрыть карточку' }).click()

  const done = page.locator('.lane').filter({ has: page.getByRole('heading', { name: 'Готово' }) })
  await done.getByRole('button', { name: /Добавить карточку/ }).click()
  const create = page.getByRole('dialog')
  await create.getByLabel('Шаблон карточки').selectOption({ label: 'Подготовка проекта' })
  await create.getByRole('button', { name: 'Сохранить', exact: true }).click()
  await expect(create).toBeHidden()
  await expect(done.getByRole('button', { name: title, exact: true })).toBeVisible()
  await expect(done.getByText('Пример', { exact: true })).toBeVisible()
})

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

test('карточка хранит детали, работает во всех видах, а команда настраивает роли', async ({
  page,
}) => {
  const email = await register(page, 'Автор карточки')
  await createItem(page, '＋ Создать доску', 'Новый проект')
  await createItem(page, '＋ Колонка', 'В работе')
  await createItem(page, '＋ Добавить карточку', 'Подготовить отчёт')

  await page.getByRole('button', { name: 'Подготовить отчёт', exact: true }).click()
  const card = page.getByRole('dialog', { name: 'Подготовить отчёт' })
  const section = (label: string) => card.getByRole('button', { name: label, exact: true })
  await expect(card.getByLabel('Начало', { exact: true })).toBeHidden()
  await expect(card.getByLabel('Новая метка')).toBeHidden()
  await expect(card.getByLabel('Новый чеклист')).toBeHidden()
  await expect(card.getByLabel('Файл до 10 МБ')).toBeHidden()
  const comments = card.getByRole('region', { name: 'Комментарии' })
  await expect(comments).toBeVisible()
  const [mainBounds, commentsBounds] = await Promise.all([
    card.getByLabel('Описание', { exact: true }).boundingBox(),
    comments.boundingBox(),
  ])
  expect(commentsBounds!.x).toBeGreaterThan(mainBounds!.x + mainBounds!.width)
  await section('Даты').click()
  const today = new Date()
  const month = String(today.getMonth() + 1).padStart(2, '0')
  await card.getByLabel('Начало', { exact: true }).fill(today.getFullYear() + '-' + month + '-01')
  await card.getByLabel('Срок', { exact: true }).fill(today.getFullYear() + '-' + month + '-02')
  await section('Метки').click()
  await card.getByLabel('Новая метка').fill('Срочно')
  await card.getByRole('button', { name: 'Добавить метку' }).click()
  await section('Участники').click()
  await card.getByRole('checkbox', { name: 'Автор карточки' }).check()
  await section('Чеклисты').click()
  await card.getByLabel('Новый чеклист').fill('Проверка')
  await card.getByRole('button', { name: 'Добавить чеклист' }).click()
  await card.getByLabel('Новый пункт').fill('Собрать данные')
  await card.getByRole('button', { name: 'Добавить пункт' }).click()
  await card.getByRole('checkbox', { name: 'Выполнен', exact: true }).check()
  const saved = page.waitForResponse(
    (response) => response.request().method() === 'PATCH' && response.url().includes('/cards/'),
  )
  await card.getByRole('button', { name: 'Сохранить карточку' }).click()
  expect((await saved).ok()).toBe(true)
  await card.getByLabel('Комментарий', { exact: true }).fill('Данные собраны.')
  await card.getByRole('button', { name: 'Отправить' }).click()
  await expect(card.getByText('Данные собраны.')).toBeVisible()
  await section('Вложения').click()
  await card.getByLabel('Файл до 10 МБ').setInputFiles({
    name: 'notes.txt',
    mimeType: 'text/plain',
    buffer: Buffer.from('Ready'),
  })
  await card.getByRole('button', { name: 'Прикрепить' }).click()
  const attachment = card.getByRole('link', { name: 'notes.txt' })
  await expect(attachment).toBeVisible()
  const downloaded = await page.request.get((await attachment.getAttribute('href'))!)
  expect(await downloaded.text()).toBe('Ready')

  await card.getByRole('button', { name: 'Закрыть карточку' }).click()
  await page.getByRole('button', { name: 'Список', exact: true }).click()
  await expect(page.getByRole('table', { name: 'Список карточек' })).toContainText(
    'Подготовить отчёт',
  )
  await page.getByRole('button', { name: 'Дела', exact: true }).click()
  await expect(page.getByText('Исполнители: Автор карточки', { exact: true })).toBeVisible()
  await page.getByRole('checkbox', { name: 'Выполнение: Подготовить отчёт' }).click()
  await expect(page.getByRole('checkbox', { name: 'Выполнение: Подготовить отчёт' })).toBeChecked()
  await page.getByRole('button', { name: 'Календарь', exact: true }).click()
  const calendar = page.getByRole('region', { name: 'Календарь карточек' })
  await expect(calendar.getByRole('button', { name: /Подготовить отчёт/ })).toHaveCount(2)
  await calendar
    .getByRole('button', { name: /Подготовить отчёт/ })
    .first()
    .click()
  await section('Метки').click()
  await expect(card.getByRole('button', { name: 'Удалить метку Срочно' })).toBeVisible()
  await section('Участники').click()
  await expect(card.getByRole('checkbox', { name: 'Автор карточки' })).toBeChecked()
  await section('Чеклисты').click()
  await expect(card.getByLabel('Пункт', { exact: true })).toHaveValue('Собрать данные')
  await card.getByRole('button', { name: 'Закрыть карточку' }).click()

  await page.getByRole('button', { name: 'Команда', exact: true }).click()
  const team = page.getByRole('dialog', { name: 'Команда доски' })
  await expect(team.getByText(email, { exact: true })).toBeVisible()
  await team.getByRole('button', { name: 'Создать роль' }).click()
  await team.getByLabel('Название роли').fill('Комментатор')
  await team.getByRole('checkbox', { name: 'Добавлять комментарии' }).check()
  await team.getByRole('button', { name: 'Сохранить роль' }).click()
  await expect(team.getByRole('button', { name: 'Настроить роль Комментатор' })).toBeVisible()
  await expect(
    team.getByLabel('Роль нового участника').getByRole('option', { name: 'Комментатор' }),
  ).toBeAttached()
})
