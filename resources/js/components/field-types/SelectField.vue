<template>
  <div class="field-select">
    <label v-if="label" class="field-label">{{ label }}</label>
    <select
      :value="modelValue"
      :multiple="config.multiple"
      :disabled="disabled"
      @change="handleChange"
      class="select-input"
    >
      <option v-if="!config.multiple && !required" value="">— Select —</option>
      <option v-for="opt in options" :key="opt.value ?? opt" :value="opt.value ?? opt">
        {{ opt.label ?? opt }}
      </option>
    </select>
    <p v-if="config.instructions" class="field-instructions">{{ config.instructions }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number, Array], default: '' },
  label: { type: String, default: '' },
  config: { type: Object, default: () => ({}) },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const options = computed(() => {
  const opts = props.config.options || [];
  return opts.map(o => typeof o === 'string' ? { value: o, label: o } : o);
});

const handleChange = (e) => {
  if (props.config.multiple) {
    const selected = Array.from(e.target.selectedOptions).map(o => o.value);
    emit('update:modelValue', selected);
  } else {
    emit('update:modelValue', e.target.value);
  }
};
</script>

<style scoped>
.field-select { margin-bottom: 1rem; }
.field-label { display: block; font-weight: 600; margin-bottom: 0.25rem; font-size: 0.9rem; }
.select-input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; background: white; }
.select-input:focus { outline: none; border-color: #2563eb; }
.field-instructions { font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem; }
</style>
