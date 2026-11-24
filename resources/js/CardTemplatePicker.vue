<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { request } from './api'
import { cardTemplatesPath, type CardTemplate } from './card-templates'

const props = defineProps<{ boardId: number }>()
const emit = defineEmits<{ select: [template: CardTemplate | null] }>()
const templates = ref<CardTemplate[]>([])
const selected = ref<number | ''>('')
const unavailable = ref(false)

async function load() {
  unavailable.value = false
  try {
    templates.value = await request<CardTemplate[]>(cardTemplatesPath(props.boardId))
  } catch {
    unavailable.value = true
  }
}
onMounted(load)
</script>

<template>
  <label v-if="templates.length">
    Шаблон карточки
    <select
      v-model="selected"
      @change="emit('select', templates.find(({ id }) => id === selected) ?? null)"
    >
      <option value="">Без шаблона</option>
      <option v-for="template in templates" :key="template.id" :value="template.id">
        {{ template.name }}
      </option>
    </select>
  </label>
  <p v-else-if="unavailable" class="error" role="status">
    Не удалось загрузить шаблоны.
    <button type="button" class="secondary" @click="load">Повторить</button>
  </p>
</template>
