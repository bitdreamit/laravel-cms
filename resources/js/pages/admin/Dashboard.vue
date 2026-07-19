<template>
  <div class="dashboard">
    <h1>Dashboard</h1>
    <div class="stats-grid">
      <div v-for="stat in stats" :key="stat.label" class="stat-card">
        <div class="stat-value">{{ stat.value }}</div>
        <div class="stat-label">{{ stat.label }}</div>
      </div>
    </div>

    <div class="dashboard-grid">
      <div class="card">
        <h2>Recent Entries</h2>
        <table>
          <thead><tr><th>Title</th><th>Status</th><th>Updated</th></tr></thead>
          <tbody>
            <tr v-for="entry in recentEntries" :key="entry.id">
              <td>{{ entry.title }}</td>
              <td><span :class="['badge', entry.status]">{{ entry.status }}</span></td>
              <td>{{ formatDate(entry.updated_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="card">
        <h2>Quick Actions</h2>
        <div class="actions">
          <a href="/admin/entries/create" class="btn">New Entry</a>
          <a href="/admin/collections/create" class="btn">New Collection</a>
          <a href="/admin/blueprints" class="btn">Manage Blueprints</a>
          <a href="/admin/themes" class="btn">Themes</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const stats = ref([]);
const recentEntries = ref([]);

onMounted(async () => {
  const res = await fetch('/admin/api/dashboard');
  const data = await res.json();
  stats.value = [
    { label: 'Total Entries', value: data.stats.entries },
    { label: 'Published', value: data.stats.published_entries },
    { label: 'Drafts', value: data.stats.draft_entries },
    { label: 'Collections', value: data.stats.collections },
    { label: 'Users', value: data.stats.users },
    { label: 'Forms', value: data.stats.forms },
    { label: 'Assets', value: data.stats.assets },
    { label: 'Domains', value: data.stats.domains },
  ];
  recentEntries.value = data.recent_entries || [];
});

const formatDate = (d) => new Date(d).toLocaleDateString();
</script>
