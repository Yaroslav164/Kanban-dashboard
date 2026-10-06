<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  initial: { type: Object, default: null },
})
const emit = defineEmits(['save', 'cancel'])

const form = reactive({
  title: '',
  description: '',
  priority: 'medium',
  due_date: '',
})

watch(
  () => props.initial,
  (task) => {
    form.title = task?.title ?? ''
    form.description = task?.description ?? ''
    form.priority = task?.priority ?? 'medium'
    form.due_date = task?.due_date ? String(task.due_date).slice(0, 10) : ''
  },
  { immediate: true },
)

function submit() {
  if (!form.title.trim()) return
  emit('save', {
    title: form.title.trim(),
    description: form.description.trim() || null,
    priority: form.priority,
    due_date: form.due_date || null,
  })
  if (!props.initial) {
    form.title = ''
    form.description = ''
    form.priority = 'medium'
    form.due_date = ''
  }
}
</script>

<template>
  <form class="task-form" @submit.prevent="submit">
    <div class="task-form-row">
      <input
        v-model.trim="form.title"
        class="input input-title"
        type="text"
        placeholder="Что нужно сделать?"
        required
      />
      <select v-model="form.priority" class="input input-priority" title="Приоритет">
        <option value="low">Низкий</option>
        <option value="medium">Средний</option>
        <option value="high">Высокий</option>
      </select>
      <input v-model="form.due_date" class="input input-date" type="date" />
      <button class="btn btn-primary" type="submit" :disabled="!form.title.trim()">
        {{ initial ? 'Сохранить' : 'Добавить' }}
      </button>
      <button
        v-if="initial"
        class="btn btn-ghost"
        type="button"
        @click="emit('cancel')"
      >
        Отмена
      </button>
    </div>
    <textarea
      v-model="form.description"
      class="input input-desc"
      rows="2"
      placeholder="Описание (необязательно)"
    ></textarea>
  </form>
</template>

<style scoped>
.task-form {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.task-form-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.input {
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: 10px;
  font-size: 0.92rem;
  background: var(--bg-soft);
  font-family: inherit;
}

.input:focus {
  outline: 2px solid var(--accent-soft);
  border-color: var(--accent);
}

.input-title {
  flex: 2;
  min-width: 180px;
}

.input-priority {
  flex: 0 0 auto;
}

.input-date {
  flex: 0 0 auto;
}

.input-desc {
  resize: vertical;
  min-height: 0;
}
</style>
