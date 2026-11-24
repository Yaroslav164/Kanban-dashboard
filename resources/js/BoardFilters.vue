<script setup lang="ts">
import type { BoardMember } from './board'
import { emptyCardFilters, type CardFilters } from './board-views'

defineProps<{
  userId: number
  members: BoardMember[]
  labels: string[]
  active: boolean
}>()
const filters = defineModel<CardFilters>({ required: true })
</script>

<template>
  <fieldset class="card-filters">
    <legend>Фильтры карточек</legend>
    <div class="filters-fields">
      <label>
        Поиск карточек
        <input v-model="filters.query" type="search" placeholder="Название или описание" />
      </label>
      <label>
        Исполнитель
        <select v-model="filters.assignee">
          <option value="">Любой</option>
          <option value="unassigned">Без исполнителя</option>
          <option v-for="member in members" :key="member.id" :value="member.id">
            {{ member.name }}{{ member.id === userId ? ' (вы)' : '' }}
          </option>
        </select>
      </label>
      <label>
        Метка
        <select v-model="filters.label">
          <option value="">Любая</option>
          <option v-for="label in labels" :key="label" :value="label">{{ label }}</option>
        </select>
      </label>
      <label>
        Срок
        <select v-model="filters.deadline">
          <option value="">Любой</option>
          <option value="overdue">Просрочено</option>
          <option value="today">Сегодня</option>
          <option value="dated">Со сроком</option>
          <option value="undated">Без срока</option>
        </select>
      </label>
      <label>
        Статус
        <select v-model="filters.status">
          <option value="">Любой</option>
          <option value="open">Открыто</option>
          <option value="complete">Выполнено</option>
        </select>
      </label>
    </div>
    <div class="filter-shortcuts" role="group" aria-label="Быстрые фильтры">
      <button
        class="secondary"
        :aria-pressed="filters.assignee === userId"
        @click="filters.assignee = filters.assignee === userId ? '' : userId"
      >
        Мои
      </button>
      <button
        class="secondary"
        :aria-pressed="filters.deadline === 'overdue'"
        @click="filters.deadline = filters.deadline === 'overdue' ? '' : 'overdue'"
      >
        Просроченные
      </button>
      <button
        class="secondary"
        :aria-pressed="filters.assignee === 'unassigned'"
        @click="filters.assignee = filters.assignee === 'unassigned' ? '' : 'unassigned'"
      >
        Без исполнителя
      </button>
      <button v-if="active" class="text" @click="filters = emptyCardFilters()">Сбросить</button>
    </div>
  </fieldset>
</template>

<style scoped>
.card-filters {
  min-width: 0;
  margin: 0 0 20px;
  padding: 12px;
  border: 1px solid #d4dbd2;
  border-radius: 10px;
}

legend {
  padding: 0 5px;
  font-weight: 650;
}

.filters-fields {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 148px), 1fr));
  gap: 10px;
}

.filters-fields label {
  min-width: 0;
  margin: 0;
}

.filters-fields :is(input, select),
.filter-shortcuts button {
  min-height: 44px;
}

.filters-fields select {
  width: 100%;
}

.filter-shortcuts {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.filter-shortcuts [aria-pressed='true'] {
  color: #fff;
  background: #163a32;
}
</style>
