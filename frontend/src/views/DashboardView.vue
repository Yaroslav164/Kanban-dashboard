<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { api, ApiError } from '../api'
import TaskForm from '../components/TaskForm.vue'
import TaskItem from '../components/TaskItem.vue'

const props = defineProps({
  user: { type: Object, required: true },
})
const emit = defineEmits(['logout'])

const tasks = ref([])
const loading = ref(true)
const error = ref('')
const busy = ref(false)

// Filters
const statusFilter = ref('all') // all | active | done
const query = ref('')

// Editing
const editingTask = ref(null)

const stats = computed(() => ({
  total: tasks.value.length,
  done: tasks.value.filter((t) => t.completed).length,
  active: tasks.value.filter((t) => !t.completed).length,
}))

async function loadTasks() {
  loading.value = true
  error.value = ''
  try {
    const data = await api.tasks({
      completed:
        statusFilter.value === 'all' ? null : statusFilter.value === 'done',
      q: query.value,
    })
    tasks.value = data.data
  } catch (err) {
    error.value = err instanceof ApiError ? err.message : 'Не удалось загрузить задачи.'
  } finally {
    loading.value = false
  }
}

function handleCreate(payload) {
  busy.value = true
  api
    .createTask(payload)
    .then(() => loadTasks())
    .catch((err) => (error.value = err.message))
    .finally(() => (busy.value = false))
}

function handleUpdate(id, payload) {
  busy.value = true
  api
    .updateTask(id, payload)
    .then(() => {
      editingTask.value = null
      return loadTasks()
    })
    .catch((err) => (error.value = err.message))
    .finally(() => (busy.value = false))
}

function handleCancelEdit() {
  editingTask.value = null
}

async function handleToggle(task) {
  const prev = task.completed
  task.completed = !prev // optimistic update
  try {
    await api.toggleTask(task.id)
  } catch {
    task.completed = prev
    error.value = 'Не удалось изменить статус задачи.'
  }
}

async function handleDelete(task) {
  if (!confirm(`Удалить задачу «${task.title}»?`)) return
  try {
    await api.deleteTask(task.id)
    tasks.value = tasks.value.filter((t) => t.id !== task.id)
  } catch (err) {
    error.value = err instanceof ApiError ? err.message : 'Не удалось удалить задачу.'
  }
}

// Debounce the search box.
const searchTimer = ref(null)
watch(query, () => {
  clearTimeout(searchTimer.value)
  searchTimer.value = setTimeout(loadTasks, 300)
})

watch(statusFilter, loadTasks)

onMounted(loadTasks)
</script>

<template>
  <div class="dash">
    <header class="dash-header">
      <div class="dash-brand">
        <span class="dash-logo">✓</span>
        <div>
          <h1>Task Manager</h1>
          <p>{{ user.name }} · {{ user.email }}</p>
        </div>
      </div>
      <button class="btn btn-ghost" type="button" @click="emit('logout')">
        Выйти
      </button>
    </header>

    <main class="dash-main">
      <section class="dash-stats">
        <div class="stat">
          <span class="stat-num">{{ stats.total }}</span>
          <span class="stat-label">всего</span>
        </div>
        <div class="stat">
          <span class="stat-num stat-active">{{ stats.active }}</span>
          <span class="stat-label">активные</span>
        </div>
        <div class="stat">
          <span class="stat-num stat-done">{{ stats.done }}</span>
          <span class="stat-label">выполнено</span>
        </div>
      </section>

      <section class="dash-form">
        <TaskForm
          :initial="editingTask"
          @save="editingTask ? handleUpdate(editingTask.id, $event) : handleCreate($event)"
          @cancel="handleCancelEdit"
        />
      </section>

      <section class="dash-filters">
        <div class="filter-tabs">
          <button
            class="filter-tab"
            :class="{ active: statusFilter === 'all' }"
            type="button"
            @click="statusFilter = 'all'"
          >
            Все
          </button>
          <button
            class="filter-tab"
            :class="{ active: statusFilter === 'active' }"
            type="button"
            @click="statusFilter = 'active'"
          >
            Активные
          </button>
          <button
            class="filter-tab"
            :class="{ active: statusFilter === 'done' }"
            type="button"
            @click="statusFilter = 'done'"
          >
            Выполненные
          </button>
        </div>
        <input
          v-model="query"
          class="filter-search"
          type="search"
          placeholder="Поиск по задачам…"
        />
      </section>

      <p v-if="error" class="error banner">{{ error }}</p>

      <section class="dash-list">
        <p v-if="loading" class="list-hint">Загрузка задач…</p>
        <p v-else-if="tasks.length === 0" class="list-hint">
          {{ statusFilter === 'done' ? 'Пока нет выполненных задач.' : 'Задач нет — добавьте первую!' }}
        </p>
        <TaskItem
          v-for="task in tasks"
          :key="task.id"
          :task="task"
          @toggle="handleToggle(task)"
          @delete="handleDelete(task)"
          @edit="editingTask = task"
        />
      </section>
    </main>
  </div>
</template>

<style scoped>
.dash {
  max-width: 760px;
  margin: 0 auto;
  padding: 24px 20px 64px;
}

.dash-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 8px 0 24px;
}

.dash-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.dash-logo {
  display: inline-grid;
  place-items: center;
  width: 40px;
  height: 40px;
  border-radius: 11px;
  background: linear-gradient(135deg, var(--accent), #0ea5e9);
  color: #fff;
  font-size: 1.2rem;
}

.dash-brand h1 {
  margin: 0;
  font-size: 1.25rem;
}

.dash-brand p {
  margin: 2px 0 0;
  font-size: 0.85rem;
  color: var(--muted);
}

.dash-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}

.stat {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 12px 16px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.stat-num {
  font-size: 1.5rem;
  font-weight: 700;
}

.stat-active {
  color: var(--accent);
}

.stat-done {
  color: var(--success);
}

.stat-label {
  font-size: 0.8rem;
  color: var(--muted);
}

.dash-form {
  margin-bottom: 20px;
}

.dash-filters {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.filter-tabs {
  display: inline-flex;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 3px;
}

.filter-tab {
  border: none;
  background: transparent;
  padding: 7px 14px;
  font-size: 0.88rem;
  font-weight: 600;
  color: var(--muted);
  border-radius: 8px;
  cursor: pointer;
}

.filter-tab.active {
  background: var(--accent);
  color: #fff;
}

.filter-search {
  flex: 1;
  min-width: 200px;
  padding: 9px 12px;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: var(--surface);
  font-size: 0.9rem;
}

.filter-search:focus {
  outline: 2px solid var(--accent-soft);
  border-color: var(--accent);
}

.dash-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.list-hint {
  text-align: center;
  color: var(--muted);
  padding: 28px 0;
  background: var(--surface);
  border: 1px dashed var(--border);
  border-radius: 12px;
}

.error.banner {
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 10px;
  padding: 10px 12px;
  margin: 0 0 14px;
  font-size: 0.9rem;
}
</style>
