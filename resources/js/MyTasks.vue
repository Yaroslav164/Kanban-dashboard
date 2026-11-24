<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { request } from './api'
import { groupAssignedTasks, type AssignedTask } from './board-views'

const props = defineProps<{ revision: number }>()
const emit = defineEmits<{ open: [task: AssignedTask] }>()
const entries = ref<AssignedTask[]>([])
const loading = ref(true)
const error = ref('')
const retry = ref(0)

watch(
  () => [props.revision, retry.value],
  async (_, __, onCleanup) => {
    let current = true
    onCleanup(() => {
      current = false
    })
    loading.value = true
    error.value = ''
    try {
      const tasks = await request<AssignedTask[]>('/api/me/tasks')
      if (current) entries.value = tasks
    } catch (issue) {
      if (current)
        error.value = issue instanceof Error ? issue.message : 'Не удалось загрузить задачи.'
    } finally {
      if (current) loading.value = false
    }
  },
  { immediate: true },
)

const groups = computed(() =>
  groupAssignedTasks(entries.value, new Date()).filter(({ tasks }) => tasks.length),
)
</script>

<template>
  <div class="page-head"><h1>Мои задачи</h1></div>
  <p v-if="error" class="error" role="alert">
    {{ error }} <button class="secondary" @click="retry++">Повторить</button>
  </p>
  <p v-if="loading" role="status">Загрузка задач…</p>
  <div v-if="groups.length" class="tasks" :aria-busy="loading">
    <section v-for="group in groups" :key="group.title" class="task-group">
      <h2>
        {{ group.title }} <small>{{ group.tasks.length }}</small>
      </h2>
      <ul>
        <li v-for="task in group.tasks" :key="task.id">
          <div>
            <button class="text row-title task-link" @click="emit('open', task)">
              {{ task.title }}
            </button>
            <small>{{ task.board_title }} · {{ task.column_title }}</small>
          </div>
          <time v-if="task.due_date" :datetime="task.due_date">{{
            task.due_date.split('-').reverse().join('.')
          }}</time>
        </li>
      </ul>
    </section>
  </div>
  <div v-else-if="!loading && !error" class="empty"><h2>Нет назначенных открытых задач</h2></div>
</template>

<style scoped>
.tasks {
  display: grid;
  gap: 14px;
}
.task-group li {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}
.task-group li div {
  min-width: 0;
  overflow-wrap: anywhere;
}
.task-link {
  min-height: 44px;
  text-align: left;
}
</style>
