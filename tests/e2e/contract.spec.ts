import { expect, test } from '@playwright/test'
import { randomUUID } from 'node:crypto'
import { readFileSync } from 'node:fs'
import Ajv from 'ajv'
import addFormats from 'ajv-formats'

const api = JSON.parse(readFileSync('storage/api-docs/api-docs.json', 'utf8'))
const validator = new Ajv({ strict: false })
addFormats(validator)
validator.addSchema(api, 'urn:kanban:spec')

test('HTTP-ответы CRUD, ошибок и сессии соответствуют OpenAPI', async ({ request, baseURL }) => {
  const vars: Record<string, number> = {}
  const email = randomUUID() + '@example.test',
    memberEmail = randomUUID() + '@example.test',
    password = 'Kanban-test-123'
  async function check(method: string, path: string, status: number, data?: object) {
    const cookie = (await request.storageState()).cookies.find((c) => c.name === 'XSRF-TOKEN')
    const url = path.replace(/\{(\w+)\}/g, (_, key) => String(vars[key]))
    const response = await request.fetch(url, {
      method,
      data,
      headers: {
        Accept: 'application/json',
        Origin: baseURL!,
        'X-XSRF-TOKEN': decodeURIComponent(cookie?.value ?? ''),
      },
    })
    const text = await response.text()
    expect(response.status(), method + ' ' + url + ' ' + text).toBe(status)
    let spec = api.paths[path][method.toLowerCase()].responses[status]
    if (spec.$ref) spec = api.components.responses[spec.$ref.split('/').pop()]
    if (!spec.content) {
      expect(text).toBe('')
      return
    }
    const schema = spec.content['application/json'].schema
    const validate = validator.compile({
      $id: 'urn:kanban:response:' + randomUUID(),
      ...JSON.parse(
        JSON.stringify(schema).replaceAll('"#/components/', '"urn:kanban:spec#/components/'),
      ),
    })
    const body = JSON.parse(text)
    expect(validate(body), JSON.stringify(validate.errors)).toBe(true)
    return body.data
  }
  const boards = '/api/boards',
    board = boards + '/{board}',
    cols = board + '/columns',
    col = cols + '/{column}',
    cards = '/api/columns/{column}/cards',
    card = cards + '/{card}',
    members = board + '/members',
    member = members + '/{member}'
  await check('GET', '/sanctum/csrf-cookie', 204)
  await check('GET', boards, 401)
  vars.member = (
    await check('POST', '/api/auth/register', 201, {
      name: 'Participant',
      email: memberEmail,
      password,
      password_confirmation: password,
    })
  ).id
  await check('POST', '/api/auth/logout', 204)
  await check('GET', '/sanctum/csrf-cookie', 204)
  await check('POST', '/api/auth/register', 201, {
    name: 'Contract',
    email,
    password,
    password_confirmation: password,
  })
  await check('GET', '/api/auth/me', 200)
  vars.board = (await check('POST', boards, 201, { title: 'QA' })).id
  await check('GET', members, 200)
  await check('POST', members, 201, { email: memberEmail, role: 'editor' })
  await check('POST', members, 422, { email: memberEmail, role: 'editor' })
  await check('PATCH', member, 200, { role: 'viewer' })
  await check('DELETE', member, 204)
  await check('GET', boards, 200)
  await check('PATCH', board, 200, { title: 'Release' })
  await check('PATCH', board, 422, { position: 12 })
  vars.column = (await check('POST', cols, 201, { title: 'Todo' })).id
  await check('GET', cols, 200)
  await check('GET', col, 200)
  await check('PATCH', col, 200, { title: 'Doing' })
  vars.card = (await check('POST', cards, 201, { title: 'Review' })).id
  await check('GET', cards, 200)
  await check('GET', card, 200)
  await check('PATCH', card, 200, { description: 'Check fields' })
  await check('POST', '/api/cards/{card}/move', 200, {
    column_id: vars.column,
    previous_card_id: null,
    next_card_id: null,
  })
  await check('GET', board, 200)
  await check('DELETE', card, 204)
  await check('GET', card, 404)
  await check('DELETE', col, 204)
  await check('DELETE', board, 204)
  await check('POST', '/api/auth/logout', 204)
  await check('GET', '/sanctum/csrf-cookie', 204)
  await check('POST', '/api/auth/login', 200, { email, password })
})
