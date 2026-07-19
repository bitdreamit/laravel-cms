<template>
  <div class="field-text">
    <label v-if="label" class="field-label">{{ label }}</label>
    <div class="input-wrapper" :class="{ 'has-prepend': config.prepend, 'has-append': config.append }">
      <span v-if="config.prepend" class="prepend">{{ config.prepend }}</span>
      <input
        :type="config.input_type || 'text'"
        :value="modelValue"
        :placeholder="config.placeholder || ''"
        :maxlength="config.character_limit || null"
        :disabled="disabled"
        @input="$emit('update:modelValue', $event.target.value)"
        class="text-input"
      />
      <span v-if="config.append" class="append">{{ config.append }}</span>
    </div>
    <p v-if="config.instructions" class="field-instructions">{{ config.instructions }}</p>
    <p v-if="config.character_limit" class="char-count">{{ (modelValue || '').length }} / {{ config.character_limit }}</p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  config: { type: Object, default: () => ({}) },
  disabled: { type: Boolean, default: false },
});
defineEmits(['update:modelValue']);
</script>

<style scoped>
.field-text { margin-bottom: 1rem; }
.field-label { display: block; font-weight: 600; margin-bottom: 0.25rem; font-size: 0.9rem; }
.input-wrapper { display: flex; align-items: center; }
.text-input { flex: 1; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; }
.text-input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
.prepend, .append { padding: 0.5rem 0.75rem; background: #f3f4f6; border: 1px solid #d1d5db; font-size: 0.9rem; color: #6b7280; }
.prepend { border-right: none; border-radius: 6px 0 0 6px; }
.append { border-left: none; border-radius: 0 6px 6px 0; }
.has-prepend .text-input { border-radius: 0 6px 6px 0; }
.has-append .text-input { border-radius: 6px 0 0 6px; }
.field-instructions { font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem; }
.char-count { font-size: 0.75rem; color: #9ca3af; text-align: right; margin-top: 0.25rem; }
</style>
