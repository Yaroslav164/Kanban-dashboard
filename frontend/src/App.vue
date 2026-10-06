<script setup>
import { onMounted, ref } from 'vue'
import { api } from './api'
import AuthView from './views/AuthView.vue'
import DashboardView from './views/DashboardView.vue'

const user = ref(null)
const checking = ref(true)

onMounted(async () => {
  try {
    const data = await api.me()
    user.value = data.data
  } catch {
    user.value = null
  } finally {
    checking.value = false
  }
})

async function handleLogout() {
  try {
    await api.logout()
  } catch {
    // Session is gone either way.
  }
  user.value = null
}
</script>

<template>
  <div v-if="checking" class="app-loading">Загрузка…</div>
  <AuthView v-else-if="!user" @authenticated="user = $event" />
  <DashboardView v-else :user="user" @logout="handleLogout" />
</template>

<style scoped>
.app-loading {
  min-height: 100vh;
  display: grid;
  place-items: center;
  color: var(--muted);
  font-size: 1rem;
}
</style>
