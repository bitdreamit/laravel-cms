<template>
  <div class="field-toggle">
    <label class="toggle-label" :class="{ disabled }">
      <input type="checkbox" :checked="modelValue" :disabled="disabled" @change="$emit('update:modelValue', $event.target.checked)" class="toggle-input" />
      <span class="toggle-switch"></span>
      <span class="toggle-text" v-if="label">{{ label }}</span>
    </label>
    <p v-if="config.instructions" class="field-instructions">{{ config.instructions }}</p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  label: { type: String, default: '' },
  config: { type: Object, default: () => ({}) },
  disabled: { type: Boolean, default: false },
});
defineEmits(['update:modelValue']);
</script>

<style scoped>
.field-toggle { margin-bottom: 1rem; }
.toggle-label { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; user-select: none; }
.toggle-label.disabled { opacity: 0.5; cursor: not-allowed; }
.toggle-input { display: none; }
.toggle-switch { width: 40px; height: 22px; background: #d1d5db; border-radius: 11px; position: relative; transition: background 0.2s; }
.toggle-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; background: white; border-radius: 50%; transition: transform 0.2s; }
.toggle-input:checked + .toggle-switch { background: #2563eb; }
.toggle-input:checked + .toggle-switch::after { transform: translateX(18px); }
.toggle-text { font-size: 0.9rem; font-weight: 500; }
.field-instructions { font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem; }
</style>
