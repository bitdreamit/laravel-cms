<template>
  <div class="entry-edit">
    <div class="header">
      <h1>{{ entry.id ? 'Edit' : 'Create' }} Entry</h1>
      <div class="actions">
        <button @click="saveDraft" class="btn">Save Draft</button>
        <button @click="publish" class="btn primary">Publish</button>
      </div>
    </div>

    <div class="edit-grid">
      <div class="main-content">
        <div v-for="field in fields" :key="field.handle" class="field-wrapper">
          <component :is="getComponent(field.fieldtype)" v-model="entry.data[field.handle]" :label="field.display_label" :config="field.config" />
        </div>
      </div>

      <div class="sidebar">
        <div class="card">
          <h3>Status</h3>
          <select v-model="entry.status" class="status-select">
            <option value="draft">Draft</option>
            <option value="published">Published</option>
            <option value="scheduled">Scheduled</option>
          </select>
        </div>
        <div class="card">
          <h3>Collection</h3>
          <select v-model="entry.collection_id" class="collection-select">
            <option v-for="col in collections" :key="col.id" :value="col.id">{{ col.name }}</option>
          </select>
        </div>
        <div class="card">
          <h3>Slug</h3>
          <input v-model="entry.slug" class="slug-input" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import TextField from '../../components/field-types/TextField.vue';
import TextareaField from '../../components/field-types/TextareaField.vue';
import BardField from '../../components/field-types/BardField.vue';
import SelectField from '../../components/field-types/SelectField.vue';
import ToggleField from '../../components/field-types/ToggleField.vue';
import AssetField from '../../components/field-types/AssetField.vue';

const props = defineProps({ entryId: String });
const entry = ref({ id: null, title: '', slug: '', status: 'draft', data: {}, collection_id: null });
const fields = ref([]);
const collections = ref([]);

const getComponent = (fieldtype) => {
  const map = { text: TextField, textarea: TextareaField, bard: BardField, select: SelectField, toggle: ToggleField, assets: AssetField };
  return map[fieldtype] || TextField;
};

const saveDraft = async () => {
  entry.value.status = 'draft';
  await save();
};

const publish = async () => {
  entry.value.status = 'published';
  await save();
  await fetch(`/admin/entries/${entry.value.id}/publish`, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content } });
};

const save = async () => {
  const method = entry.value.id ? 'PUT' : 'POST';
  const url = entry.value.id ? `/admin/entries/${entry.value.id}` : '/admin/entries';
  const res = await fetch(url, { method, body: JSON.stringify(entry.value), headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content } });
  if (res.ok) { const data = await res.json(); if (!entry.value.id) entry.value.id = data.id; }
};

onMounted(async () => {
  const res = await fetch('/admin/collections', { headers: { Accept: 'application/json' } });
  collections.value = (await res.json()).data || [];
});
</script>
