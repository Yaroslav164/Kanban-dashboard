<script setup>
import { reactive, ref } from 'vue'
import { api, ApiError } from '../api'

const emit = defineEmits(['authenticated'])

const mode = ref('login') // 'login' | 'register'
const busy = ref(false)
const formError = ref('')
const fieldErrors = reactive({})

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

function setMode(next) {
  mode.value = next
  formError.value = ''
  for (const key of Object.keys(fieldErrors)) delete fieldErrors[key]
  form.password = ''
  form.password_confirmation = ''
}

async function submit() {
  busy.value = true
  formError.value = ''
  for (const key of Object.keys(fieldErrors)) delete fieldErrors[key]

  try {
    const payload =
      mode.value === 'login'
        ? { email: form.email, password: form.password }
        : {
            name: form.name,
            email: form.email,
            password: form.password,
            password_confirmation: form.password_confirmation,
          }

    const data =
      mode.value === 'login' ? await api.login(payload) : await api.register(payload)
    emit('authenticated', data.data)
  } catch (err) {
    if (err instanceof ApiError) {
      formError.value = err.message
      for (const [field, messages] of Object.entries(err.errors || {})) {
        fieldErrors[field] = messages[0]
      }
    } else {
      formError.value = err.message
    }
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="auth">
    <div class="auth-card">
      <div class="auth-brand">
        <span class="auth-logo">✓</span>
        <h1>Task Manager</h1>
        <p class="auth-subtitle">Ваш личный список задач</p>
      </div>

      <div class="auth-tabs" role="tablist">
        <button
          class="auth-tab"
          :class="{ active: mode === 'login' }"
          type="button"
          @click="setMode('login')"
        >
          Вход
        </button>
        <button
          class="auth-tab"
          :class="{ active: mode === 'register' }"
          type="button"
          @click="setMode('register')"
        >
          Регистрация
        </button>
      </div>

      <form class="auth-form" @submit.prevent="submit">
        <label v-if="mode === 'register'" class="field">
          <span>Имя</span>
          <input v-model.trim="form.name" type="text" autocomplete="name" required />
          <small v-if="fieldErrors.name" class="error">{{ fieldErrors.name }}</small>
        </label>

        <label class="field">
          <span>Email</span>
          <input
            v-model.trim="form.email"
            type="email"
            autocomplete="email"
            required
          />
          <small v-if="fieldErrors.email" class="error">{{ fieldErrors.email }}</small>
        </label>

        <label class="field">
          <span>Пароль</span>
          <input
            v-model="form.password"
            type="password"
            :autocomplete="mode === 'login' ? 'current-password' : 'new-password'"
            required
          />
          <small v-if="fieldErrors.password" class="error">
            {{ fieldErrors.password }}
          </small>
        </label>

        <label v-if="mode === 'register'" class="field">
          <span>Повторите пароль</span>
          <input
            v-model="form.password_confirmation"
            type="password"
            autocomplete="new-password"
            required
          />
        </label>

        <p v-if="formError" class="error banner">{{ formError }}</p>

        <button class="btn btn-primary" type="submit" :disabled="busy">
          {{ busy ? 'Подождите…' : mode === 'login' ? 'Войти' : 'Создать аккаунт' }}
        </button>
      </form>

      <p v-if="mode === 'login'" class="auth-hint">
        Демо-доступ: <code>demo@example.com</code> / <code>password</code>
      </p>
    </div>
  </div>
</template>

<style scoped>
.auth {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
  background:
    radial-gradient(600px 300px at 20% 0%, rgba(59, 130, 246, 0.18), transparent 60%),
    radial-gradient(500px 300px at 90% 100%, rgba(16, 185, 129, 0.15), transparent 60%),
    var(--bg);
}

.auth-card {
  width: 100%;
  max-width: 420px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 32px 28px;
  box-shadow: var(--shadow);
}

.auth-brand {
  text-align: center;
  margin-bottom: 20px;
}

.auth-logo {
  display: inline-grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--accent), #0ea5e9);
  color: #fff;
  font-size: 1.4rem;
  margin-bottom: 12px;
}

.auth-brand h1 {
  margin: 0;
  font-size: 1.5rem;
}

.auth-subtitle {
  margin: 4px 0 0;
  color: var(--muted);
  font-size: 0.95rem;
}

.auth-tabs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  background: var(--bg-soft);
  border-radius: 10px;
  padding: 4px;
  margin-bottom: 20px;
}

.auth-tab {
  border: none;
  background: transparent;
  padding: 8px 0;
  border-radius: 8px;
  font-weight: 600;
  color: var(--muted);
  cursor: pointer;
}

.auth-tab.active {
  background: var(--surface);
  color: var(--text);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 0.9rem;
  font-weight: 600;
}

.field input {
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: 10px;
  font-size: 0.95rem;
  background: var(--bg-soft);
}

.field input:focus {
  outline: 2px solid var(--accent-soft);
  border-color: var(--accent);
}

.error {
  color: var(--danger);
  font-weight: 500;
}

.error.banner {
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 10px;
  padding: 10px 12px;
}

.auth-hint {
  margin: 18px 0 0;
  text-align: center;
  font-size: 0.82rem;
  color: var(--muted);
}

.auth-hint code {
  background: var(--bg-soft);
  border-radius: 6px;
  padding: 1px 6px;
}
</style>
