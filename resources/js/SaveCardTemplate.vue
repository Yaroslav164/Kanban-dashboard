<script setup lang="ts">
import { ref } from 'vue'
import { request } from './api'
import { cardTemplatesPath } from './card-templates'

const props = defineProps<{ boardId: number; cardId: number; title: string; dirty?: boolean }>()
const editing = ref(false)
const busy = ref(false)
const name = ref('')
const error = ref('')
const status = ref('')

function open() {
  name.value = props.title.slice(0, 255)
  error.value = ''
  status.value = ''
  editing.value = true
}

async function save() {
  if (busy.value || props.dirty || !name.value.trim()) return
  busy.value = true
  error.value = ''
  try {
    await request(cardTemplatesPath(props.boardId), 'POST', {
      name: name.value.trim(),
      card_id: props.cardId,
    })
    editing.value = false
    status.value = 'Шаблон сохранён. Его можно выбрать при создании карточки.'
  } catch (failure) {
    error.value = failure instanceof Error ? failure.message : 'Не удалось сохранить шаблон.'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="save-card-template">
    <form v-if="editing" @submit.prevent="save">
      <label
        >Название шаблона<input v-model="name" required maxlength="255" :disabled="busy"
      /></label>
      <p v-if="error" class="error" role="alert">{{ error }}</p>
      <div class="template-actions">
        <button class="primary" :disabled="busy || dirty || !name.trim()">Сохранить шаблон</button>
        <button type="button" class="secondary" :disabled="busy" @click="editing = false">
          Отмена
        </button>
      </div>
    </form>
    <button v-else type="button" class="secondary" :disabled="dirty" @click="open">
      Сохранить как шаблон
    </button>
    <small v-if="dirty">Сначала сохраните изменения карточки.</small>
    <p v-if="status" role="status">{{ status }}</p>
  </div>
</template>

<style scoped>
.save-card-template {
  margin-bottom: 0.75rem;
}
.template-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
</style>
