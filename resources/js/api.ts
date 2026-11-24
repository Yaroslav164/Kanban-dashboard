export class HttpError extends Error {
  constructor(
    public status: number,
    message: string,
  ) {
    super(message)
  }
}
export function csrfToken() {
  return decodeURIComponent(
    document.cookie
      .split('; ')
      .find((v) => v.startsWith('XSRF-TOKEN='))
      ?.slice(11) ?? '',
  )
}
export async function request<T>(path: string, method = 'GET', data?: object): Promise<T> {
  const multipart = data instanceof FormData
  const response = await fetch(path, {
    method,
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      ...(!multipart && { 'Content-Type': 'application/json' }),
      'X-XSRF-TOKEN': csrfToken(),
    },
    body: multipart ? data : data ? JSON.stringify(data) : undefined,
  })
  if (response.status === 204) return undefined as T
  const body = await response.json().catch(() => ({}))
  if (!response.ok) {
    const messages = Object.values(body.errors ?? {}).flat()
    throw new HttpError(response.status, messages.join(' ') || body.message || 'Ошибка запроса')
  }
  return body.data
}
