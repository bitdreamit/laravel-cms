<template>
  <div class="field-replicator">
    <label v-if="label" class="field-label">{{ label }}</label>
    <div class="replicator-sets">
      <div v-for="(set, index) in sets" :key="index" class="replicator-set">
        <div class="set-header" @click="toggleCollapse(index)">
          <span class="set-type">{{ set.type || 'Set ' + (index + 1) }}</span>
          <div class="set-actions">
            <button type="button" @click.stop="moveSet(index, -1)" :disabled="index === 0">↑</button>
            <button type="button" @click.stop="moveSet(index, 1)" :disabled="index === sets.length - 1">↓</button>
            <button type="button" @click.stop="removeSet(index)" class="danger">×</button>
          </div>
        </div>
        <div v-show="!collapsed[index]" class="set-content">
          <div v-for="field in getSetFields(set.type)" :key="field.handle" class="set-field">
            <component :is="getComponent(field.fieldtype)" v-model="set.values[field.handle]" :label="field.display_label" :config="field.config" />
          </div>
        </div>
      </div>
    </div>
    <div class="add-set" v-if="availableSets.length > 0">
      <button type="button" @click="addSet" class="add-set-btn">+ Add Set</button>
    </div>
    <p v-if="config.instructions" class="field-instructions">{{ config.instructions }}</p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import TextField from './TextField.vue';
import TextareaField from './TextareaField.vue';
import BardField from './BardField.vue';
import SelectField from './SelectField.vue';
import ToggleField from './ToggleField.vue';
import AssetField from './AssetField.vue';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  label: { type: String, default: '' },
  config: { type: Object, default: () => ({}) },
  disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const collapsed = ref([]);

const sets = computed({
  get: () => props.modelValue || [],
  set: (val) => emit('update:modelValue', val),
});

const availableSets = computed(() => props.config.sets || []);

const addSet = () => {
  const setType = availableSets.value.length === 1 ? availableSets.value[0].handle : null;
  if (!setType) return;
  const newSet = { type: setType, values: {} };
  const newSets = [...sets.value, newSet];
  emit('update:modelValue', newSets);
  collapsed.value[newSets.length - 1] = false;
};

const removeSet = (index) => {
  const newSets = sets.value.filter((_, i) => i !== index);
  emit('update:modelValue', newSets);
};

const moveSet = (index, direction) => {
  const newIndex = index + direction;
  if (newIndex < 0 || newIndex >= sets.value.length) return;
  const newSets = [...sets.value];
  [newSets[index], newSets[newIndex]] = [newSets[newIndex], newSets[index]];
  emit('update:modelValue', newSets);
};

const toggleCollapse = (index) => {
  collapsed.value[index] = !collapsed.value[index];
};

const getSetFields = (setType) => {
  const setConfig = availableSets.value.find(s => s.handle === setType);
  return setConfig?.fields || [];
};

const getComponent = (fieldtype) => {
  const map = { text: TextField, textarea: TextareaField, bard: BardField, select: SelectField, toggle: ToggleField, assets: AssetField };
  return map[fieldtype] || TextField;
};
</script>

<style scoped>
.field-replicator { margin-bottom: 1rem; }
.field-label { display: block; font-weight: 600; margin-bottom: 0.25rem; font-size: 0.9rem; }
.replicator-sets { display: flex; flex-direction: column; gap: 0.5rem; }
.replicator-set { border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden; }
.set-header { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; background: #f9fafb; cursor: pointer; }
.set-type { font-weight: 600; font-size: 0.85rem; }
.set-actions { display: flex; gap: 0.25rem; }
.set-actions button { width: 24px; height: 24px; border: 1px solid #e5e7eb; background: white; border-radius: 4px; cursor: pointer; }
.set-actions button.danger { color: #dc2626; }
.set-content { padding: 0.75rem; }
.add-set { margin-top: 0.5rem; }
.add-set-btn { padding: 0.5rem 1rem; border: 1px dashed #d1d5db; background: transparent; border-radius: 6px; cursor: pointer; }
.add-set-btn:hover { border-color: #2563eb; color: #2563eb; }
.field-instructions { font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem; }
</style>
