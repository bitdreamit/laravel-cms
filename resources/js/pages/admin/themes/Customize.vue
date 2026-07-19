<template>
  <div class="theme-customizer">
    <div class="customizer-left">
      <div class="customizer-tabs">
        <button v-for="(section, key) in sections" :key="key" @click="activeSection = key" :class="{ active: activeSection === key }">
          {{ section.title || key }}
        </button>
      </div>
      <div class="customizer-controls">
        <div v-for="(setting, sKey) in activeSectionSettings" :key="sKey" class="control-group">
          <label>{{ setting.label }}</label>
          <div class="control-input">
            <component
              :is="getControlComponent(setting.type)"
              v-model="settings[activeSection + '.' + sKey]"
              :config="setting"
            />
          </div>
        </div>
      </div>
      <div class="customizer-bottom">
        <button @click="saveDraft" :disabled="saving">Save Draft</button>
        <button @click="publish" class="primary" :disabled="saving">Publish</button>
        <button @click="discard" class="danger">Discard</button>
        <button @click="resetDefaults" class="danger">Reset to Defaults</button>
      </div>
    </div>
    <div class="customizer-right">
      <div class="preview-toolbar">
        <button @click="device = 'desktop'" :class="{ active: device === 'desktop' }">🖥</button>
        <button @click="device = 'tablet'" :class="{ active: device === 'tablet' }">📱</button>
        <button @click="device = 'mobile'" :class="{ active: device === 'mobile' }">📲</button>
      </div>
      <iframe :src="previewUrl" :class="['preview-frame', device]" referrerpolicy="no-referrer"></iframe>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import ColorField from '../../components/field-types/ThemeColorField.vue';
import TextField from '../../components/field-types/TextField.vue';
import ToggleField from '../../components/field-types/ToggleField.vue';
import SelectField from '../../components/field-types/SelectField.vue';

const props = defineProps({
  themeId: { type: String, required: true },
  schema: { type: Object, default: () => ({}) },
  initialSettings: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['save', 'publish', 'discard', 'reset']);

const settings = ref({ ...props.initialSettings });
const activeSection = ref(Object.keys(props.schema)[0] || 'branding');
const device = ref('desktop');
const saving = ref(false);
const dirty = ref(false);

const sections = computed(() => props.schema);
const activeSectionSettings = computed(() => props.schema[activeSection.value]?.settings || {});

const previewUrl = computed(() => `/admin/themes/${props.themeId}/preview-settings`);

watch(settings, () => { dirty.value = true; }, { deep: true });

const getControlComponent = (type) => {
  const map = {
    color: ColorField,
    text: TextField,
    toggle: ToggleField,
    select: SelectField,
    range: 'RangeField',
    font_picker: 'FontPickerField',
    image: 'AssetField',
    code: 'CodeField',
    background: 'BackgroundField',
  };
  return map[type] || TextField;
};

const saveDraft = async () => {
  saving.value = true;
  emit('save', { settings: settings.value, publish: false });
  saving.value = false;
  dirty.value = false;
};

const publish = async () => {
  saving.value = true;
  emit('publish', { settings: settings.value, publish: true });
  saving.value = false;
  dirty.value = false;
};

const discard = () => {
  settings.value = { ...props.initialSettings };
  dirty.value = false;
  emit('discard');
};

const resetDefaults = () => {
  if (confirm('Reset all customizations to theme defaults?')) {
    emit('reset');
  }
};
</script>

<style scoped>
.theme-customizer { display: flex; height: 100vh; }
.customizer-left { width: 400px; display: flex; flex-direction: column; border-right: 1px solid #e5e7eb; background: #f9fafb; }
.customizer-tabs { display: flex; flex-wrap: wrap; padding: 0.5rem; gap: 0.25rem; }
.customizer-tabs button { padding: 0.4rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 4px; background: white; font-size: 0.8rem; cursor: pointer; }
.customizer-tabs button.active { background: #2563eb; color: white; border-color: #2563eb; }
.customizer-controls { flex: 1; overflow-y: auto; padding: 1rem; }
.control-group { margin-bottom: 1rem; }
.control-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.3rem; }
.control-input input, .control-input select { width: 100%; padding: 0.4rem; border: 1px solid #d1d5db; border-radius: 4px; }
.customizer-bottom { padding: 0.75rem; border-top: 1px solid #e5e7eb; display: flex; flex-direction: column; gap: 0.3rem; }
.customizer-bottom button { padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; cursor: pointer; }
.customizer-bottom button.primary { background: #2563eb; color: white; }
.customizer-bottom button.danger { color: #dc2626; }
.customizer-right { flex: 1; display: flex; flex-direction: column; }
.preview-toolbar { padding: 0.5rem; display: flex; gap: 0.25rem; background: #1e293b; }
.preview-toolbar button { width: 36px; height: 36px; border: none; background: transparent; font-size: 1.2rem; cursor: pointer; border-radius: 4px; }
.preview-toolbar button.active { background: rgba(255,255,255,0.2); }
.preview-frame { flex: 1; border: none; background: white; }
.preview-frame.desktop { width: 100%; }
.preview-frame.tablet { width: 768px; margin: 0 auto; }
.preview-frame.mobile { width: 375px; margin: 0 auto; }
</style>
