import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { csrfToken } from './api'

export function subscribe(userId: number, refresh: () => void) {
  const secure =
    (import.meta.env.VITE_REVERB_SCHEME ?? location.protocol.replace(':', '')) === 'https'
  const echo = new Echo({
    broadcaster: 'reverb',
    Pusher,
    key: import.meta.env.VITE_REVERB_APP_KEY || 'kanban-local',
    wsHost: import.meta.env.VITE_REVERB_HOST || location.hostname,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT || 443),
    forceTLS: secure,
    enabledTransports: ['ws', 'wss'],
    authorizer: (channel) => ({
      authorize: async (socketId, callback) => {
        try {
          const response = await fetch('/api/broadcasting/auth', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/json',
              Accept: 'application/json',
              'X-XSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ socket_id: socketId, channel_name: channel.name }),
          })
          if (!response.ok) throw new Error('Подписка недоступна')
          callback(null, await response.json())
        } catch (error) {
          callback(error instanceof Error ? error : new Error(String(error)), null)
        }
      },
    }),
  })
  echo
    .private('users.' + userId)
    .listen('.board.changed', refresh)
    .subscribed(refresh)
  return () => echo.disconnect()
}
