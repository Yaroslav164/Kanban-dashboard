<script setup>
import { computed } from 'vue'

const props = defineProps({
  task: { type: Object, required: true },
})
const emit = defineEmits(['toggle', 'delete', 'edit'])

const priorityLabel = {
  low: 'Низкий',
  medium: 'Средний',
  high: 'Высокий',
}

const dueText = computed(() => {
  if (!props.task.due_date) return null
  const date = new Date(String(props.task.due_date).slice(0, 10) + 'T00:00:00')
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const diff = Math.round((date - today) / 86_400_000)
  const formatted = date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
  if (diff < 0) return { text: formatted, tone: 'overdue' }
  if (diff === 0) return { text: 'сегодня', tone: 'today' }
  if (diff === 1) return { text: 'завтра', tone: 'soon' }
  return { text: formatted, tone: 'later' }
})
</script>

<template>
  <article class="task" :class="{ done: task.completed, ['p-' + task.priority]: true }">
    <label class="task-check" title="Выполнено">
      <input
        type="checkbox"
        :checked="task.completed"
        @change="emit('toggle')"
      />
      <span class="check"></span>
    </label>

    <div class="task-body">
      <p class="task-title">{{ task.title }}</p>
      <p v-if="task.description" class="task-desc">{{ task.description }}</p>
      <div class="task-meta">
        <span class="badge" :class="'badge-' + task.priority">
          {{ priorityLabel[task.priority] || task.priority }}
        </span>
        <span v-if="dueText" class="badge" :class="'due-' + dueText.tone">
          {{ dueText.text }}
        </span>
      </div>
    </div>

    <div class="task-actions">
      <button class="icon-btn" type="button" title="Редактировать" @click="emit('edit')">
        ✎
      </button>
      <button class="icon-btn danger" type="button" title="Удалить" @click="emit('delete')">
        ✕
      </button>
    </div>
  </article>
</template>

<style scoped>
.task {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-left: 4px solid var(--priority-color, var(--border));
  border-radius: 12px;
  padding: 14px 16px;
  transition: opacity 0.15s ease, transform 0.1s ease;
}

.task.p-low {
  --priority-color: #94a3b8;
}

.task.p-medium {
  --priority-color: #f59e0b;
}

.task.p-high {
  --priority-color: #ef4444;
}

.task.done {
  opacity: 0.65;
}

.task-check {
  position: relative;
  cursor: pointer;
  margin-top: 2px;
}

.task-check input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.check {
  display: block;
  width: 22px;
  height: 22px;
  border: 2px solid var(--border);
  border-radius: 50%;
  transition: all 0.15s ease;
}

.task-check:hover .check {
  border-color: var(--accent);
}

.task-check input:checked + .check {
  background: var(--success);
  border-color: var(--success);
  box-shadow: inset 0 0 0 3px var(--surface);
}

.task-body {
  flex: 1;
  min-width: 0;
}

.task-title {
  margin: 0;
  font-weight: 600;
  overflow-wrap: anywhere;
}

.task.done .task-title {
  text-decoration: line-through;
}

.task-desc {
  margin: 4px 0 0;
  font-size: 0.88rem;
  color: var(--muted);
  white-space: pre-line;
  overflow-wrap: anywhere;
}

.task-meta {
  display: flex;
  gap: 6px;
  margin-top: 8px;
  flex-wrap: wrap;
}

.badge {
  font-size: 0.72rem;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 999px;
  background: var(--bg-soft);
  border: 1px solid var(--border);
}

.badge-low {
  color: #64748b;
}

.badge-medium {
  color: #b45309;
  border-color: rgba(245, 158, 11, 0.4);
}

.badge-high {
  color: #b91c1c;
  border-color: rgba(239, 68, 68, 0.4);
}

.due-overdue {
  color: #b91c1c;
  border-color: rgba(239, 68, 68, 0.4);
}

.due-today {
  color: #b45309;
  border-color: rgba(245, 158, 11, 0.4);
}

.due-soon,
.due-later {
  color: var(--muted);
}

.task-actions {
  display: flex;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.12s ease;
}

.task:hover .task-actions {
  opacity: 1;
}

.icon-btn {
  border: 1px solid var(--border);
  background: var(--surface);
  border-radius: 8px;
  width: 30px;
  height: 30px;
  cursor: pointer;
  color: var(--muted);
  font-size: 0.85rem;
  line-height: 1;
}

.icon-btn:hover {
  color: var(--accent);
  border-color: var(--accent);
}

.icon-btn.danger:hover {
  color: var(--danger);
  border-color: var(--danger);
}
</style>
