<template>
  <div class="command-palette" v-if="isOpen" @keydown.esc="close">
    <div class="palette-overlay" @click="close"></div>
    <div class="palette-modal">
      <div class="palette-header">
        <input
          ref="input"
          v-model="query"
          @input="search"
          @keydown.down="moveDown"
          @keydown.up="moveUp"
          @keydown.enter="selectCurrent"
          placeholder="Type a command or search..."
          class="palette-input"
        />
        <kbd class="palette-kbd">ESC</kbd>
      </div>

      <div class="palette-results">
        <div v-for="(group, groupName) in groupedResults" :key="groupName" class="result-group">
          <div class="group-label">{{ groupName }}</div>
          <button
            v-for="(item, idx) in group"
            :key="item.id"
            @click="execute(item)"
            @mouseover="selectedIndex = flatIndex(groupName, idx)"
            :class="['result-item', { selected: flatIndex(groupName, idx) === selectedIndex }]"
          >
            <span class="item-icon">{{ item.icon || '◻' }}</span>
            <span class="item-label">{{ item.label }}</span>
            <span v-if="item.shortcut" class="item-shortcut">{{ item.shortcut }}</span>
          </button>
        </div>
        <div v-if="Object.keys(groupedResults).length === 0" class="no-results">
          No results for "{{ query }}"
        </div>
      </div>

      <div class="palette-footer">
        <span><kbd>↑</kbd><kbd>↓</kbd> Navigate</span>
        <span><kbd>↵</kbd> Select</span>
        <span><kbd>ESC</kbd> Close</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const isOpen = ref(false);
const query = ref('');
const selectedIndex = ref(0);
const input = ref(null);

const allCommands = ref([
  // Navigation
  { id: 'nav-dashboard', group: 'Navigation', label: 'Dashboard', icon: '📊', url: '/admin' },
  { id: 'nav-entries', group: 'Navigation', label: 'Entries', icon: '📄', url: '/admin/entries' },
  { id: 'nav-collections', group: 'Navigation', label: 'Collections', icon: '📁', url: '/admin/collections' },
  { id: 'nav-blueprints', group: 'Navigation', label: 'Blueprints', icon: '🧩', url: '/admin/blueprints' },
  { id: 'nav-taxonomies', group: 'Navigation', label: 'Taxonomies', icon: '🏷', url: '/admin/taxonomies' },
  { id: 'nav-globals', group: 'Navigation', label: 'Globals', icon: '🌐', url: '/admin/globals' },
  { id: 'nav-navigation', group: 'Navigation', label: 'Navigation', icon: '🧭', url: '/admin/navigations' },
  { id: 'nav-forms', group: 'Navigation', label: 'Forms', icon: '📝', url: '/admin/forms' },
  { id: 'nav-assets', group: 'Navigation', label: 'Assets', icon: '🖼', url: '/admin/assets' },

  // V4 Features
  { id: 'nav-domains', group: 'V4 Features', label: 'Domains', icon: '🌍', url: '/admin/domains' },
  { id: 'nav-themes', group: 'V4 Features', label: 'Themes', icon: '🎨', url: '/admin/themes' },
  { id: 'nav-workflows', group: 'V4 Features', label: 'Workflows', icon: '⚡', url: '/admin/workflows' },
  { id: 'nav-experiments', group: 'V4 Features', label: 'A/B Experiments', icon: '🧪', url: '/admin/experiments' },
  { id: 'nav-rag', group: 'V4 Features', label: 'RAG Playground', icon: '🤖', url: '/admin/rag/playground' },
  { id: 'nav-segments', group: 'V4 Features', label: 'Segments', icon: '👥', url: '/admin/segments' },
  { id: 'nav-connectors', group: 'V4 Features', label: 'Connectors', icon: '🔗', url: '/admin/connectors' },
  { id: 'nav-audit', group: 'V4 Features', label: 'Audit Streams', icon: '📡', url: '/admin/audit-streams' },

  // Settings
  { id: 'nav-users', group: 'Settings', label: 'Users', icon: '👤', url: '/admin/users' },
  { id: 'nav-roles', group: 'Settings', label: 'Roles', icon: '👥', url: '/admin/roles' },
  { id: 'nav-billing', group: 'Settings', label: 'Billing', icon: '💳', url: '/admin/billing' },
  { id: 'nav-feature-flags', group: 'Settings', label: 'Feature Flags', icon: '🚩', url: '/admin/feature-flags' },
  { id: 'nav-utilities', group: 'Settings', label: 'Utilities', icon: '🔧', url: '/admin/utilities' },

  // Actions
  { id: 'action-new-entry', group: 'Actions', label: 'New Entry', icon: '➕', shortcut: 'N', action: () => window.location.href = '/admin/entries/create' },
  { id: 'action-new-collection', group: 'Actions', label: 'New Collection', icon: '➕', action: () => window.location.href = '/admin/collections/create' },
  { id: 'action-search', group: 'Actions', label: 'Search Entries', icon: '🔍', shortcut: '/', action: () => window.location.href = '/admin/entries?search=' },
  { id: 'action-clear-cache', group: 'Actions', label: 'Clear Cache', icon: '🗑', action: () => fetch('/admin/utilities/clear-cache', { method: 'POST' }).then(() => alert('Cache cleared!')) },
]);

const results = ref([]);

const search = () => {
  if (! query.value.trim()) {
    results.value = allCommands.value;
    return;
  }

  const q = query.value.toLowerCase();
  results.value = allCommands.value.filter(c =>
    c.label.toLowerCase().includes(q) ||
    c.group.toLowerCase().includes(q)
  );

  // Also search entries via API
  if (q.length > 2) {
    fetch(`/admin/entries?search=${encodeURIComponent(q)}&per_page=5`, { headers: { Accept: 'application/json' } })
      .then(r => r.json())
      .then(data => {
        const entryResults = (data.data || []).map(e => ({
          id: `entry-${e.id}`,
          group: 'Entries',
          label: e.title,
          icon: '📄',
          url: `/admin/entries/${e.id}/edit`,
        }));
        results.value = [...results.value.filter(r => r.group !== 'Entries'), ...entryResults];
      });
  }
};

const groupedResults = computed(() => {
  const groups = {};
  results.value.forEach(r => {
    if (! groups[r.group]) groups[r.group] = [];
    groups[r.group].push(r);
  });
  return groups;
});

const flatIndex = (groupName, idx) => {
  let count = 0;
  for (const [g, items] of Object.entries(groupedResults.value)) {
    if (g === groupName) return count + idx;
    count += items.length;
  }
  return 0;
};

const moveDown = () => {
  selectedIndex.value = Math.min(selectedIndex.value + 1, results.value.length - 1);
};

const moveUp = () => {
  selectedIndex.value = Math.max(selectedIndex.value - 1, 0);
};

const selectCurrent = () => {
  if (results.value[selectedIndex.value]) {
    execute(results.value[selectedIndex.value]);
  }
};

const execute = (item) => {
  if (item.url) {
    window.location.href = item.url;
  } else if (item.action) {
    item.action();
  }
  close();
};

const open = () => {
  isOpen.value = true;
  selectedIndex.value = 0;
  query.value = '';
  setTimeout(() => input.value?.focus(), 50);
};

const close = () => {
  isOpen.value = false;
  query.value = '';
};

const handleKeydown = (e) => {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault();
    isOpen.value ? close() : open();
  }
};

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
  search();
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<style scoped>
.command-palette { position: fixed; inset: 0; z-index: 99999; }
.palette-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.5); }
.palette-modal { position: absolute; top: 20%; left: 50%; transform: translateX(-50%); width: 90%; max-width: 600px; background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; }
.palette-header { display: flex; align-items: center; padding: 1rem; border-bottom: 1px solid #e5e7eb; }
.palette-input { flex: 1; border: none; outline: none; font-size: 1.1rem; padding: 0.5rem; }
.palette-kbd { font-size: 0.75rem; padding: 0.2rem 0.5rem; background: #f3f4f6; border-radius: 4px; color: #6b7280; }
.palette-results { max-height: 400px; overflow-y: auto; padding: 0.5rem; }
.result-group { margin-bottom: 0.5rem; }
.group-label { font-size: 0.7rem; text-transform: uppercase; color: #9ca3af; padding: 0.5rem 0.75rem 0.25rem; font-weight: 600; }
.result-item { display: flex; align-items: center; gap: 0.75rem; width: 100%; padding: 0.6rem 0.75rem; border: none; background: transparent; text-align: left; cursor: pointer; border-radius: 6px; font-size: 0.9rem; }
.result-item.selected { background: #eff6ff; color: #2563eb; }
.item-icon { font-size: 1.1rem; }
.item-label { flex: 1; }
.item-shortcut { font-size: 0.75rem; padding: 0.1rem 0.4rem; background: #e5e7eb; border-radius: 3px; color: #6b7280; }
.no-results { padding: 2rem; text-align: center; color: #9ca3af; }
.palette-footer { display: flex; gap: 1.5rem; padding: 0.75rem 1rem; border-top: 1px solid #e5e7eb; background: #f9fafb; font-size: 0.75rem; color: #6b7280; }
kbd { padding: 0.1rem 0.4rem; background: white; border: 1px solid #d1d5db; border-radius: 3px; font-size: 0.7rem; margin-right: 0.2rem; }
</style>
