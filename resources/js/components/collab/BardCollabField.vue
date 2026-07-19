<template>
  <div class="bard-collab">
    <div class="collab-presence-bar" v-if="activeUsers.length > 0">
      <div v-for="user in activeUsers" :key="user.user_id" class="presence-avatar" :style="{ backgroundColor: user.color }" :title="user.name">
        {{ (user.name || '?').charAt(0).toUpperCase() }}
      </div>
      <span class="presence-count">{{ activeUsers.length }} editing</span>
      <button v-if="canForceLock" @click="forceLock" class="force-lock-btn">Take Over</button>
    </div>
    <div ref="editorRef" class="collab-editor" contenteditable="true" @input="onInput"></div>
    <div v-if="lockedBy" class="lock-banner">
      Session force-locked by {{ lockedBy }}. You can no longer edit.
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  sessionId: { type: String, required: true },
  canForceLock: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'forceLock']);

const editorRef = ref(null);
const activeUsers = ref([]);
const lockedBy = ref(null);
let ws = null;
let heartbeatInterval = null;

onMounted(() => {
  connectWebSocket();
  startHeartbeat();
});

onUnmounted(() => {
  if (ws) ws.close();
  if (heartbeatInterval) clearInterval(heartbeatInterval);
});

const connectWebSocket = () => {
  const proto = window.location.protocol === 'https:' ? 'wss' : 'ws';
  const wsUrl = `${proto}://${window.location.host}/app/collab/${props.sessionId}`;
  try {
    ws = new WebSocket(wsUrl);
    ws.onopen = () => { ws.send(JSON.stringify({ type: 'sync-step1' })); };
    ws.onmessage = (event) => { try { handleMessage(JSON.parse(event.data)); } catch(e) {} };
    ws.onclose = () => { setTimeout(connectWebSocket, 3000); };
  } catch(e) { setTimeout(connectWebSocket, 3000); }
};

const handleMessage = (msg) => {
  switch (msg.type) {
    case 'sync-step2':
      if (editorRef.value && msg.document_state) {
        try { editorRef.value.innerHTML = atob(msg.document_state); } catch(e) {}
      }
      break;
    case 'update':
      applyRemoteUpdate(msg.update);
      break;
    case 'awareness-update':
      updatePresence(msg);
      break;
    case 'force-lock':
      lockedBy.value = msg.locked_by;
      if (editorRef.value) editorRef.value.contentEditable = 'false';
      break;
  }
};

const onInput = () => {
  if (!ws || ws.readyState !== WebSocket.OPEN) return;
  const content = editorRef.value?.innerHTML || '';
  try { ws.send(JSON.stringify({ type: 'update', update: btoa(content) })); } catch(e) {}
  emit('update:modelValue', content);
};

const applyRemoteUpdate = (update) => {
  if (!update) return;
  try {
    const decoded = atob(update);
    if (editorRef.value && editorRef.value.innerHTML !== decoded) {
      const sel = window.getSelection();
      const range = sel?.rangeCount > 0 ? sel.getRangeAt(0) : null;
      editorRef.value.innerHTML = decoded;
      if (range) { sel?.removeAllRanges(); sel?.addRange(range); }
    }
  } catch(e) {}
};

const updatePresence = (msg) => {
  const idx = activeUsers.value.findIndex(u => u.user_id === msg.user_id);
  const user = { user_id: msg.user_id, name: msg.name || 'User', color: msg.color || '#3b82f6', cursor: msg.cursor };
  if (idx >= 0) activeUsers.value[idx] = user; else activeUsers.value.push(user);
};

const startHeartbeat = () => {
  heartbeatInterval = setInterval(() => {
    if (ws && ws.readyState === WebSocket.OPEN) ws.send(JSON.stringify({ type: 'heartbeat' }));
  }, 15000);
};

const forceLock = () => {
  fetch(`/admin/api/collab/${props.sessionId}/force-lock`, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' } });
};
</script>

<style scoped>
.bard-collab { position: relative; }
.collab-presence-bar { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; background: #f0f9ff; border-radius: 6px 6px 0 0; border: 1px solid #bae6fd; border-bottom: none; }
.presence-avatar { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; font-weight: 600; }
.presence-count { font-size: 0.8rem; color: #0369a1; margin-left: auto; }
.force-lock-btn { padding: 0.25rem 0.5rem; background: #f59e0b; color: white; border: none; border-radius: 4px; font-size: 0.75rem; cursor: pointer; }
.collab-editor { min-height: 200px; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0 0 6px 6px; font-size: 0.9rem; line-height: 1.6; }
.collab-editor:focus { outline: none; border-color: #2563eb; }
.lock-banner { padding: 0.5rem; background: #fef3c7; color: #92400e; border-radius: 0 0 6px 6px; font-size: 0.85rem; text-align: center; }
</style>
