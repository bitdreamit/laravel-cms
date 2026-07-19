<template>
  <div class="blueprint-builder">
    <div class="builder-header">
      <input v-model="blueprint.title" class="bp-title" placeholder="Blueprint Title" />
      <input v-model="blueprint.handle" class="bp-handle" placeholder="handle" />
      <button @click="save" class="save-btn" :disabled="saving">{{ saving ? 'Saving...' : 'Save Blueprint' }}</button>
    </div>

    <div class="builder-body">
      <div class="palette">
        <h4>Field Types</h4>
        <div class="palette-fields">
          <div v-for="ft in fieldTypes" :key="ft.handle" class="palette-item" draggable="true" @dragstart="dragStart($event, ft)" @dblclick="addField(ft)">
            <span class="ft-icon">{{ ft.icon || '◻' }}</span>
            <span>{{ ft.label }}</span>
          </div>
        </div>
      </div>

      <div class="canvas">
        <div v-for="(tab, tabName) in tabs" :key="tabName" class="tab">
          <div class="tab-header">
            <input v-model="tabs[tabName].label" class="tab-name" @change="renameTab(tabName)" />
            <button @click="removeTab(tabName)" class="remove-tab">×</button>
          </div>
          <div class="tab-fields" @drop="drop($event, tabName)" @dragover.prevent="dragOver($event)">
            <div v-for="(field, index) in tab.fields" :key="field.id" class="field-item" :class="{ selected: selectedFieldId === field.id }" @click="selectField(field)">
              <span class="drag-handle">⋮⋮</span>
              <span class="field-type-badge">{{ field.fieldtype }}</span>
              <span class="field-label-text">{{ field.display_label }}</span>
              <span class="field-handle-text">{{ field.handle }}</span>
              <div class="field-actions">
                <button @click.stop="moveField(tabName, index, -1)">↑</button>
                <button @click.stop="moveField(tabName, index, 1)">↓</button>
                <button @click.stop="removeField(tabName, index)" class="danger">×</button>
              </div>
            </div>
            <div v-if="tab.fields.length === 0" class="empty-tab">Drag field types here or double-click to add</div>
          </div>
        </div>
        <button @click="addTab" class="add-tab">+ Add Tab</button>
      </div>

      <div class="config-panel">
        <h4 v-if="selectedField">Field Config</h4>
        <div v-if="selectedField" class="config-form">
          <div class="form-group">
            <label>Display Label</label>
            <input v-model="selectedField.display_label" type="text" />
          </div>
          <div class="form-group">
            <label>Handle</label>
            <input v-model="selectedField.handle" type="text" />
          </div>
          <div class="form-group">
            <label>Instructions</label>
            <textarea v-model="selectedField.instructions" rows="2"></textarea>
          </div>
          <div class="form-group">
            <label>Validation Rules</label>
            <input v-model="selectedField.validation_rules" type="text" placeholder="required|max:255" />
          </div>
          <div class="form-group">
            <label>
              <input type="checkbox" v-model="selectedField.is_listable" /> Show in listing
            </label>
          </div>
          <div class="form-group">
            <label>
              <input type="checkbox" v-model="selectedField.is_sortable" /> Sortable
            </label>
          </div>
          <div class="form-group">
            <label>
              <input type="checkbox" v-model="selectedField.is_localizable" /> Localizable
            </label>
          </div>
          <div class="form-group">
            <label>Field Config (JSON)</label>
            <textarea v-model="configJson" rows="8" @blur="updateConfig"></textarea>
          </div>
        </div>
        <div v-else class="no-selection">
          <p>Select a field to configure its options</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';

const props = defineProps({
  modelValue: { type: Object, required: true },
});
const emit = defineEmits(['update:modelValue', 'save']);

const blueprint = reactive({ ...props.modelValue });
const saving = ref(false);
const selectedFieldId = ref(null);
const draggingField = ref(null);

const tabs = reactive(blueprint.tabs || { 'Main': { label: 'Main', fields: [] } });

const fieldTypes = [
  { handle: 'text', label: 'Text', icon: 'Aa' },
  { handle: 'textarea', label: 'Textarea', icon: '¶' },
  { handle: 'markdown', label: 'Markdown', icon: 'M↓' },
  { handle: 'bard', label: 'Bard', icon: '✎' },
  { handle: 'select', label: 'Select', icon: '▾' },
  { handle: 'toggle', label: 'Toggle', icon: '⊙' },
  { handle: 'date', label: 'Date', icon: '📅' },
  { handle: 'slug', label: 'Slug', icon: '/' },
  { handle: 'assets', label: 'Assets', icon: '📁' },
  { handle: 'code', label: 'Code', icon: '</>' },
  { handle: 'color', label: 'Color', icon: '🎨' },
  { handle: 'replicator', label: 'Replicator', icon: '☰' },
  { handle: 'grid', label: 'Grid', icon: '▦' },
  { handle: 'relationship', label: 'Relationship', icon: '🔗' },
  { handle: 'entries', label: 'Entries', icon: '📄' },
  { handle: 'terms', label: 'Terms', icon: '🏷' },
  { handle: 'integer', label: 'Integer', icon: '#' },
  { handle: 'range', label: 'Range', icon: '⟷' },
  { handle: 'seo_pro', label: 'SEO', icon: '🔍' },
  { handle: 'ai_generate', label: 'AI Generate', icon: '🤖' },
];

const selectedField = computed(() => {
  for (const tab of Object.values(tabs)) {
    const field = tab.fields.find(f => f.id === selectedFieldId.value);
    if (field) return field;
  }
  return null;
});

const configJson = computed({
  get: () => JSON.stringify(selectedField.value?.config || {}, null, 2),
  set: () => {},
});

const updateConfig = (e) => {
  if (!selectedField.value) return;
  try {
    selectedField.value.config = JSON.parse(e.target.value);
  } catch (err) {
    // Invalid JSON — keep old config
  }
};

const dragStart = (e, ft) => {
  draggingField.value = ft;
  e.dataTransfer.effectAllowed = 'copy';
};

const dragOver = (e) => {
  e.preventDefault();
  e.dataTransfer.dropEffect = 'copy';
};

const drop = (e, tabName) => {
  e.preventDefault();
  if (draggingField.value) {
    addFieldToTab(draggingField.value, tabName);
    draggingField.value = null;
  }
};

const addField = (ft) => {
  addFieldToTab(ft, Object.keys(tabs)[0]);
};

const addFieldToTab = (ft, tabName) => {
  const field = {
    id: crypto.randomUUID(),
    fieldtype: ft.handle,
    handle: ft.handle + '_' + Date.now(),
    display_label: ft.label,
    instructions: '',
    config: {},
    validation_rules: '',
    is_localizable: false,
    is_listable: ft.handle === 'text',
    is_sortable: false,
    conditional_logic: null,
    sort_order: tabs[tabName].fields.length,
  };
  tabs[tabName].fields.push(field);
};

const selectField = (field) => {
  selectedFieldId.value = field.id;
};

const moveField = (tabName, index, direction) => {
  const newIndex = index + direction;
  const fields = tabs[tabName].fields;
  if (newIndex < 0 || newIndex >= fields.length) return;
  [fields[index], fields[newIndex]] = [fields[newIndex], fields[index]];
};

const removeField = (tabName, index) => {
  tabs[tabName].fields.splice(index, 1);
  if (selectedFieldId.value === tabs[tabName].fields[index]?.id) {
    selectedFieldId.value = null;
  }
};

const addTab = () => {
  const name = 'Tab ' + (Object.keys(tabs).length + 1);
  tabs[name] = { label: name, fields: [] };
};

const removeTab = (name) => {
  if (Object.keys(tabs).length <= 1) return;
  delete tabs[name];
};

const renameTab = (oldName) => {
  // Tab name is the key — renaming requires re-keying
  // For simplicity, just update the label
};

const save = async () => {
  saving.value = true;
  blueprint.tabs = tabs;
  emit('save', blueprint);
  saving.value = false;
};
</script>

<style scoped>
.blueprint-builder { display: flex; flex-direction: column; height: 100%; }
.builder-header { display: flex; gap: 0.5rem; padding: 1rem; background: white; border-bottom: 1px solid #e5e7eb; }
.bp-title { font-size: 1.25rem; font-weight: 600; padding: 0.25rem 0.5rem; border: none; flex: 1; }
.bp-handle { padding: 0.25rem 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; width: 150px; }
.save-btn { padding: 0.5rem 1rem; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; }
.save-btn:disabled { opacity: 0.5; }
.builder-body { display: flex; flex: 1; overflow: hidden; }
.palette { width: 200px; padding: 1rem; background: #f9fafb; border-right: 1px solid #e5e7eb; overflow-y: auto; }
.palette h4 { font-size: 0.8rem; text-transform: uppercase; color: #6b7280; margin-bottom: 0.5rem; }
.palette-fields { display: flex; flex-direction: column; gap: 0.25rem; }
.palette-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; cursor: grab; font-size: 0.85rem; background: white; }
.palette-item:hover { border-color: #2563eb; background: #eff6ff; }
.palette-item:active { cursor: grabbing; }
.ft-icon { width: 20px; text-align: center; font-size: 0.8rem; }
.canvas { flex: 1; padding: 1rem; overflow-y: auto; }
.tab { margin-bottom: 1rem; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
.tab-header { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: #f3f4f6; }
.tab-name { font-weight: 600; border: none; background: transparent; }
.remove-tab { margin-left: auto; width: 24px; height: 24px; border: none; background: transparent; cursor: pointer; }
.tab-fields { min-height: 60px; padding: 0.5rem; }
.field-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 4px; margin-bottom: 0.25rem; cursor: pointer; background: white; }
.field-item.selected { border-color: #2563eb; background: #eff6ff; }
.drag-handle { cursor: grab; color: #9ca3af; }
.field-type-badge { font-size: 0.7rem; padding: 0.1rem 0.4rem; background: #dbeafe; color: #2563eb; border-radius: 3px; }
.field-label-text { font-weight: 500; font-size: 0.9rem; }
.field-handle-text { font-size: 0.8rem; color: #9ca3af; }
.field-actions { margin-left: auto; display: flex; gap: 0.15rem; }
.field-actions button { width: 22px; height: 22px; border: 1px solid #e5e7eb; background: white; border-radius: 3px; cursor: pointer; font-size: 0.8rem; }
.field-actions button.danger { color: #dc2626; }
.empty-tab { text-align: center; padding: 1.5rem; color: #9ca3af; font-size: 0.85rem; border: 2px dashed #e5e7eb; border-radius: 6px; }
.add-tab { padding: 0.5rem 1rem; border: 1px dashed #d1d5db; background: transparent; border-radius: 6px; cursor: pointer; }
.config-panel { width: 300px; padding: 1rem; background: #f9fafb; border-left: 1px solid #e5e7eb; overflow-y: auto; }
.config-panel h4 { font-size: 0.9rem; margin-bottom: 0.75rem; }
.form-group { margin-bottom: 0.75rem; }
.form-group label { display: block; font-size: 0.8rem; font-weight: 500; margin-bottom: 0.2rem; }
.form-group input, .form-group textarea { width: 100%; padding: 0.35rem 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; font-size: 0.85rem; }
.form-group input[type=checkbox] { width: auto; }
.no-selection { text-align: center; color: #9ca3af; padding: 2rem 0; }
</style>
