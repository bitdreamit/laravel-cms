<template>
  <div class="field-bard">
    <label v-if="label" class="field-label">{{ label }}</label>
    <div class="bard-toolbar" v-if="!disabled">
      <button v-for="btn in toolbar" :key="btn" type="button" @click="execCommand(btn)" class="toolbar-btn" :title="btn">
        <span v-html="getIcon(btn)"></span>
      </button>
    </div>
    <div
      ref="editor"
      class="bard-editor"
      :contenteditable="!disabled"
      @input="handleInput"
      v-html="htmlContent"
    ></div>
    <div class="bard-footer" v-if="config.word_count">
      <span class="word-count">{{ wordCount }} words</span>
      <button v-if="config.fullscreen" type="button" @click="toggleFullscreen" class="fullscreen-btn">⛶</button>
    </div>
    <p v-if="config.instructions" class="field-instructions">{{ config.instructions }}</p>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Array], default: '' },
  label: { type: String, default: '' },
  config: { type: Object, default: () => ({}) },
  disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const editor = ref(null);
const toolbar = computed(() => props.config.toolbar_buttons || ['bold', 'italic', 'link', 'heading', 'bulletList']);

const htmlContent = computed(() => {
  if (typeof props.modelValue === 'string') return props.modelValue;
  if (Array.isArray(props.modelValue)) {
    return props.modelValue.map(n => n.text || '').join('');
  }
  return '';
});

const wordCount = computed(() => {
  const text = editor.value?.textContent || '';
  return text.trim().split(/\s+/).filter(Boolean).length;
});

const handleInput = () => {
  const html = editor.value?.innerHTML || '';
  if (props.config.save_html) {
    emit('update:modelValue', html);
  } else {
    emit('update:modelValue', [{ type: 'paragraph', content: [{ type: 'text', text: editor.value?.textContent || '' }] }]);
  }
};

const execCommand = (cmd) => {
  editor.value?.focus();
  const commands = {
    bold: () => document.execCommand('bold'),
    italic: () => document.execCommand('italic'),
    underline: () => document.execCommand('underline'),
    link: () => {
      const url = prompt('Enter URL:');
      if (url) document.execCommand('createLink', false, url);
    },
    heading: () => document.execCommand('formatBlock', false, '<h2>'),
    bulletList: () => document.execCommand('insertUnorderedList'),
    orderedList: () => document.execCommand('insertOrderedList'),
    blockquote: () => document.execCommand('formatBlock', false, '<blockquote>'),
    codeBlock: () => document.execCommand('formatBlock', false, '<pre>'),
    image: () => {
      const url = prompt('Enter image URL:');
      if (url) document.execCommand('insertImage', false, url);
    },
  };
  commands[cmd]?.();
  handleInput();
};

const getIcon = (btn) => {
  const icons = { bold: '<b>B</b>', italic: '<i>I</i>', underline: '<u>U</u>', link: '🔗', heading: 'H', bulletList: '•', orderedList: '1.', blockquote: '"', codeBlock: '</>', image: '🖼' };
  return icons[btn] || btn;
};

const toggleFullscreen = () => {
  editor.value?.parentElement?.classList.toggle('fullscreen');
};

watch(() => props.modelValue, (newVal) => {
  if (editor.value && editor.value.innerHTML !== htmlContent.value) {
    editor.value.innerHTML = htmlContent.value;
  }
});
</script>

<style scoped>
.field-bard { margin-bottom: 1rem; }
.field-label { display: block; font-weight: 600; margin-bottom: 0.25rem; font-size: 0.9rem; }
.bard-toolbar { display: flex; gap: 0.25rem; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px 6px 0 0; background: #f9fafb; }
.toolbar-btn { padding: 0.25rem 0.5rem; background: white; border: 1px solid #e5e7eb; border-radius: 4px; cursor: pointer; font-size: 0.9rem; }
.toolbar-btn:hover { background: #f3f4f6; }
.bard-editor { min-height: 200px; padding: 0.75rem; border: 1px solid #d1d5db; border-top: none; border-radius: 0 0 6px 6px; font-size: 0.9rem; line-height: 1.6; }
.bard-editor:focus { outline: none; border-color: #2563eb; }
.bard-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 0.25rem; }
.word-count { font-size: 0.75rem; color: #9ca3af; }
.fullscreen-btn { background: none; border: none; cursor: pointer; font-size: 1rem; }
.field-instructions { font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem; }
.fullscreen { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999; background: white; padding: 2rem; }
</style>
