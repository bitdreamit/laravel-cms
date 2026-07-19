<template>
  <div class="entries-index">
    <div class="header">
      <h1>Entries</h1>
      <a href="/admin/entries/create" class="btn primary">+ New Entry</a>
    </div>

    <div class="filters">
      <input v-model="search" placeholder="Search entries..." @input="debouncedSearch" class="search-input" />
      <select v-model="statusFilter" @change="loadEntries">
        <option value="">All Status</option>
        <option value="draft">Draft</option>
        <option value="published">Published</option>
        <option value="scheduled">Scheduled</option>
      </select>
    </div>

    <table class="entries-table">
      <thead>
        <tr><th>Title</th><th>Collection</th><th>Status</th><th>Published</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <tr v-for="entry in entries" :key="entry.id">
          <td>{{ entry.title }}</td>
          <td>{{ entry.collection?.name || '—' }}</td>
          <td><span :class="['badge', entry.status]">{{ entry.status }}</span></td>
          <td>{{ entry.published_at ? formatDate(entry.published_at) : '—' }}</td>
          <td>
            <a :href="`/admin/entries/${entry.id}/edit`" class="btn sm">Edit</a>
            <button @click="deleteEntry(entry)" class="btn sm danger">Delete</button>
          </td>
        </tr>
        <tr v-if="entries.length === 0"><td colspan="5" class="empty">No entries found</td></tr>
      </tbody>
    </table>

    <div class="pagination" v-if="totalPages > 1">
      <button @click="prevPage" :disabled="page === 1">‹</button>
      <span>Page {{ page }} of {{ totalPages }}</span>
      <button @click="nextPage" :disabled="page === totalPages">›</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const entries = ref([]);
const search = ref('');
const statusFilter = ref('');
const page = ref(1);
const totalPages = ref(1);
let debounceTimer = null;

const loadEntries = async () => {
  const params = new URLSearchParams({ page: page.value });
  if (search.value) params.set('search', search.value);
  if (statusFilter.value) params.set('status', statusFilter.value);
  const res = await fetch(`/admin/entries?${params}`, { headers: { Accept: 'application/json' } });
  const data = await res.json();
  entries.value = data.data || [];
  totalPages.value = Math.ceil((data.total || 0) / (data.per_page || 15));
};

const debouncedSearch = () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => { page.value = 1; loadEntries(); }, 300); };
const prevPage = () => { if (page.value > 1) { page.value--; loadEntries(); } };
const nextPage = () => { if (page.value < totalPages.value) { page.value++; loadEntries(); } };
const deleteEntry = async (entry) => { if (!confirm(`Delete "${entry.title}"?`)) return; await fetch(`/admin/entries/${entry.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content } }); loadEntries(); };
const formatDate = (d) => new Date(d).toLocaleDateString();

onMounted(loadEntries);
</script>
