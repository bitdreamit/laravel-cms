<template>
  <div class="field-asset">
    <label v-if="label" class="field-label">{{ label }}</label>
    <div class="asset-picker" @click="openPicker">
      <div v-if="!hasValue" class="asset-empty">
        <span class="upload-icon">📁</span>
        <span>Click to select or upload assets</span>
      </div>
      <div v-else class="asset-preview">
        <div v-for="asset in selectedAssets" :key="asset.id || asset" class="asset-thumb">
          <img v-if="isImage(asset)" :src="getAssetUrl(asset)" :alt="asset.alt_text || ''" />
          <span v-else class="file-icon">📄 {{ getFilename(asset) }}</span>
          <button type="button" @click.stop="removeAsset(asset)" class="remove-btn">×</button>
        </div>
      </div>
    </div>
    <input type="file" ref="fileInput" multiple @change="handleUpload" style="display:none" />
    <p v-if="config.instructions" class="field-instructions">{{ config.instructions }}</p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  label: { type: String, default: '' },
  config: { type: Object, default: () => ({}) },
  disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const fileInput = ref(null);

const selectedAssets = computed(() => {
  if (!Array.isArray(props.modelValue)) return [];
  return props.modelValue;
});

const hasValue = computed(() => selectedAssets.value.length > 0);

const openPicker = () => {
  if (props.disabled) return;
  fileInput.value?.click();
};

const handleUpload = async (e) => {
  const files = Array.from(e.target.files);
  const uploaded = [];
  for (const file of files) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('container_id', props.config.container_id || '');
    const res = await fetch('/admin/assets', { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content } });
    if (res.ok) {
      const data = await res.json();
      uploaded.push(data);
    }
  }
  emit('update:modelValue', [...selectedAssets.value, ...uploaded]);
  fileInput.value.value = '';
};

const removeAsset = (asset) => {
  emit('update:modelValue', selectedAssets.value.filter(a => a !== asset && a.id !== asset.id));
};

const isImage = (asset) => {
  const mime = typeof asset === 'object' ? asset.mime_type : '';
  return mime?.startsWith('image/');
};

const getAssetUrl = (asset) => typeof asset === 'object' ? (asset.url || `/storage/${asset.path}`) : asset;
const getFilename = (asset) => typeof asset === 'object' ? asset.filename : asset;
</script>

<style scoped>
.field-asset { margin-bottom: 1rem; }
.field-label { display: block; font-weight: 600; margin-bottom: 0.25rem; font-size: 0.9rem; }
.asset-picker { border: 2px dashed #d1d5db; border-radius: 8px; padding: 1rem; cursor: pointer; transition: border-color 0.2s; min-height: 80px; display: flex; align-items: center; justify-content: center; }
.asset-picker:hover { border-color: #2563eb; }
.asset-empty { text-align: center; color: #6b7280; }
.upload-icon { font-size: 1.5rem; display: block; margin-bottom: 0.25rem; }
.asset-preview { display: flex; flex-wrap: wrap; gap: 0.5rem; width: 100%; }
.asset-thumb { position: relative; width: 80px; height: 80px; border-radius: 6px; overflow: hidden; border: 1px solid #e5e7eb; }
.asset-thumb img { width: 100%; height: 100%; object-fit: cover; }
.file-icon { display: flex; align-items: center; justify-content: center; height: 100%; font-size: 0.7rem; padding: 0.25rem; text-align: center; }
.remove-btn { position: absolute; top: 2px; right: 2px; width: 18px; height: 18px; border-radius: 50%; background: rgba(0,0,0,0.5); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.field-instructions { font-size: 0.8rem; color: #6b7280; margin-top: 0.25rem; }
</style>
