export class ApiError extends Error {
  constructor(message, status, errors = {}) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

async function request(path, options = {}) {
  const init = {
    ...options,
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...(options.headers || {}),
    },
  }

  let res
  try {
    res = await fetch(path, init)
  } catch {
    throw new ApiError('Ошибка сети. Сервер недоступен.', 0)
  }

  let data = null
  try {
    data = await res.json()
  } catch {
    // Response had no JSON body.
  }

  if (!res.ok) {
    const errors = (data && data.errors) || {}
    const message =
      (data && (data.message || Object.values(errors).flat()[0])) ||
      'Что-то пошло не так.'
    throw new ApiError(message, res.status, errors)
  }

  return data
}

const json = (payload) => ({ method: 'POST', body: JSON.stringify(payload) })
const patch = (payload) => ({ method: 'PATCH', body: JSON.stringify(payload) })

export const api = {
  register: (payload) => request('/api/auth/register', json(payload)),
  login: (payload) => request('/api/auth/login', json(payload)),
  logout: () => request('/api/auth/logout', { method: 'POST' }),
  me: () => request('/api/auth/me'),

  tasks: (filters = {}) => {
    const params = new URLSearchParams()
    for (const [key, value] of Object.entries(filters)) {
      if (value !== null && value !== undefined && value !== '') {
        params.set(key, value)
      }
    }
    const qs = params.toString()
    return request(`/api/tasks${qs ? `?${qs}` : ''}`)
  },
  createTask: (payload) => request('/api/tasks', json(payload)),
  updateTask: (id, payload) => request(`/api/tasks/${id}`, patch(payload)),
  toggleTask: (id) => request(`/api/tasks/${id}/toggle`, { method: 'PATCH' }),
  deleteTask: (id) => request(`/api/tasks/${id}`, { method: 'DELETE' }),
}
