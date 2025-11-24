<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Staff Panel — Paxton Carnegie Library</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #f8fafc;
  --bg-card: #ffffff;
  --text: #1e293b;
  --text-muted: #64748b;
  --accent: #16a34a;
  --accent-light: #dcfce7;
  --border: #e2e8f0;
  --warning: #f59e0b;
  --warning-light: #fef3c7;
  --danger: #ef4444;
  --danger-light: #fee2e2;
  --shadow: 0 1px 3px rgba(0,0,0,0.1);
  --shadow-lg: 0 10px 40px rgba(0,0,0,0.1);
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  background: var(--bg);
  color: var(--text);
  line-height: 1.5;
  min-height: 100vh;
}

/* Header */
.header {
  background: var(--bg-card);
  border-bottom: 1px solid var(--border);
  padding: 16px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-title {
  font-size: 20px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-title .icon {
  font-size: 24px;
}

.header-stats {
  display: flex;
  gap: 24px;
}

.stat {
  text-align: center;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: var(--accent);
}

.stat-label {
  font-size: 12px;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.btn {
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid var(--border);
  background: var(--bg-card);
  color: var(--text);
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn:hover {
  background: var(--bg);
}

.btn-primary {
  background: var(--accent);
  border-color: var(--accent);
  color: white;
}

.btn-primary:hover {
  background: #15803d;
}

.btn-danger {
  background: var(--danger);
  border-color: var(--danger);
  color: white;
}

.btn-danger:hover {
  background: #dc2626;
}

/* Main content */
.main {
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px;
}

/* Tabs */
.tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  background: var(--bg-card);
  padding: 6px;
  border-radius: 12px;
  border: 1px solid var(--border);
}

.tab {
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  background: transparent;
  color: var(--text-muted);
  position: relative;
}

.tab.active {
  background: var(--accent);
  color: white;
}

.tab .badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: var(--danger);
  color: white;
  font-size: 11px;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 999px;
  min-width: 20px;
  text-align: center;
}

/* Request cards */
.request-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.request-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 20px;
  display: grid;
  grid-template-columns: 80px 1fr auto;
  gap: 20px;
  align-items: center;
  transition: all 0.2s;
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.request-card:hover {
  box-shadow: var(--shadow-lg);
  border-color: var(--accent);
}

.request-card.completed {
  opacity: 0.6;
  background: var(--bg);
}

.request-poster {
  width: 80px;
  height: 120px;
  object-fit: cover;
  border-radius: 8px;
  background: var(--bg);
}

.request-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.request-title {
  font-size: 18px;
  font-weight: 600;
}

.request-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  color: var(--text-muted);
  font-size: 14px;
}

.request-meta span {
  display: flex;
  align-items: center;
  gap: 6px;
}

.request-patron {
  background: var(--accent-light);
  color: var(--accent);
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.request-time {
  color: var(--text-muted);
  font-size: 13px;
}

.request-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.btn-complete {
  background: var(--accent-light);
  border-color: var(--accent);
  color: var(--accent);
}

.btn-complete:hover {
  background: var(--accent);
  color: white;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 80px 40px;
  color: var(--text-muted);
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-title {
  font-size: 20px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 8px;
}

/* Status badges */
.status-badge {
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-pending {
  background: var(--warning-light);
  color: var(--warning);
}

.status-completed {
  background: var(--accent-light);
  color: var(--accent);
}

/* Audio notification */
.notification-sound {
  display: none;
}

/* Responsive */
@media (max-width: 768px) {
  .request-card {
    grid-template-columns: 60px 1fr;
  }
  
  .request-actions {
    grid-column: span 2;
    flex-direction: row;
  }
  
  .header-stats {
    display: none;
  }
}

/* Pulse animation for new requests */
@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.4); }
  70% { box-shadow: 0 0 0 10px rgba(22, 163, 74, 0); }
  100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
}

.request-card.new {
  animation: pulse 2s infinite, slideIn 0.3s ease-out;
  border-color: var(--accent);
}

/* Settings panel */
.settings-panel {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 24px;
}

.settings-title {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 16px;
}

.setting-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid var(--border);
}

.setting-row:last-child {
  border-bottom: none;
}

.setting-label {
  font-size: 14px;
}

.setting-desc {
  font-size: 12px;
  color: var(--text-muted);
}

/* Toggle switch */
.toggle {
  position: relative;
  width: 48px;
  height: 28px;
}

.toggle input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  inset: 0;
  background: var(--border);
  border-radius: 999px;
  transition: 0.2s;
}

.toggle-slider:before {
  content: "";
  position: absolute;
  width: 22px;
  height: 22px;
  left: 3px;
  bottom: 3px;
  background: white;
  border-radius: 50%;
  transition: 0.2s;
}

.toggle input:checked + .toggle-slider {
  background: var(--accent);
}

.toggle input:checked + .toggle-slider:before {
  transform: translateX(20px);
}
</style>
</head>
<body>

<header class="header">
  <h1 class="header-title">
    <span class="icon">📚</span>
    Staff Panel
  </h1>
  
  <div class="header-stats">
    <div class="stat">
      <div class="stat-value" id="pendingCount">0</div>
      <div class="stat-label">Pending</div>
    </div>
    <div class="stat">
      <div class="stat-value" id="todayCount">0</div>
      <div class="stat-label">Today</div>
    </div>
  </div>
  
  <div class="header-actions">
    <button class="btn" id="btnRefresh">
      🔄 Refresh
    </button>
    <button class="btn" id="btnSettings">
      ⚙️ Settings
    </button>
    <button class="btn btn-danger" id="btnClearAll" style="display: none;">
      🗑️ Clear All
    </button>
  </div>
</header>

<main class="main">
  <!-- Settings panel (hidden by default) -->
  <div class="settings-panel" id="settingsPanel" style="display: none;">
    <h2 class="settings-title">⚙️ Settings</h2>
    
    <div class="setting-row">
      <div>
        <div class="setting-label">Sound Notifications</div>
        <div class="setting-desc">Play a sound when new requests come in</div>
      </div>
      <label class="toggle">
        <input type="checkbox" id="settingSound" checked>
        <span class="toggle-slider"></span>
      </label>
    </div>
    
    <div class="setting-row">
      <div>
        <div class="setting-label">Auto-refresh</div>
        <div class="setting-desc">Automatically check for new requests every 10 seconds</div>
      </div>
      <label class="toggle">
        <input type="checkbox" id="settingAutoRefresh" checked>
        <span class="toggle-slider"></span>
      </label>
    </div>
    
    <div class="setting-row">
      <div>
        <div class="setting-label">Show Completed</div>
        <div class="setting-desc">Display completed requests in the list</div>
      </div>
      <label class="toggle">
        <input type="checkbox" id="settingShowCompleted">
        <span class="toggle-slider"></span>
      </label>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab active" data-filter="pending">
      📋 Pending
      <span class="badge" id="pendingBadge" style="display: none;">0</span>
    </button>
    <button class="tab" data-filter="completed">✅ Completed</button>
    <button class="tab" data-filter="all">📁 All Requests</button>
  </div>

  <!-- Request list -->
  <div class="request-list" id="requestList">
    <div class="empty-state">
      <div class="empty-icon">📭</div>
      <div class="empty-title">No requests yet</div>
      <p>Movie requests from the kiosk will appear here</p>
    </div>
  </div>
</main>

<!-- Notification sound -->
<audio id="notificationSound" preload="auto">
  <source src="data:audio/mp3;base64,SUQzBAAAAAAAI1RTU0UAAAAPAAADTGF2ZjU4Ljc2LjEwMAAAAAAAAAAAAAAA//tQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWGluZwAAAA8AAAACAAABhgC7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7u7//////////////////////////////////////////////////////////////////8AAAAATGF2YzU4LjEzAAAAAAAAAAAAAAAAJAAAAAAAAAAAAYYlOVGOAAAAAAD/+9DEAAAGAAGn9AAAQAAA0gAAABBMZW4QAAIIwoyBAMAMAA0AwLGcIwFjcLBjTGTBgtJjIwmC4WCwXBQWCwX/y4Fg5////+g=" type="audio/mp3">
</audio>

<script>
let requests = [];
let filter = 'pending';
let autoRefreshInterval = null;
let lastRequestCount = 0;

const settings = {
  sound: true,
  autoRefresh: true,
  showCompleted: false
};

// Load requests from server
async function loadRequests() {
  try {
    const res = await fetch('api/requests.php');
    const data = await res.json();
    
    if (data.ok) {
      const newRequests = data.requests || [];
      
      // Check for new requests
      if (newRequests.length > lastRequestCount && lastRequestCount > 0) {
        if (settings.sound) {
          playNotificationSound();
        }
      }
      
      requests = newRequests;
      lastRequestCount = requests.length;
      renderRequests();
      updateStats();
    }
  } catch (err) {
    console.error('Failed to load requests:', err);
  }
}

// Render request list
function renderRequests() {
  const list = document.getElementById('requestList');
  
  let filtered = requests;
  
  if (filter === 'pending') {
    filtered = requests.filter(r => !r.completed);
  } else if (filter === 'completed') {
    filtered = requests.filter(r => r.completed);
  }
  
  if (filtered.length === 0) {
    list.innerHTML = `
      <div class="empty-state">
        <div class="empty-icon">${filter === 'pending' ? '✨' : '📭'}</div>
        <div class="empty-title">${filter === 'pending' ? 'All caught up!' : 'No requests'}</div>
        <p>${filter === 'pending' ? 'No pending requests right now' : 'Movie requests will appear here'}</p>
      </div>
    `;
    return;
  }
  
  list.innerHTML = filtered.map(req => {
    const time = new Date(req.timestamp);
    const timeStr = time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const isNew = (Date.now() - time.getTime()) < 60000; // Less than 1 minute old
    
    return `
      <div class="request-card ${req.completed ? 'completed' : ''} ${isNew && !req.completed ? 'new' : ''}" data-id="${req.id}">
        <img 
          class="request-poster" 
          src="${esc(req.movie.cover || '/img/no-cover.svg')}" 
          alt="${esc(req.movie.title)}"
          onerror="this.src='/img/no-cover.svg'"
        >
        
        <div class="request-info">
          <div class="request-title">${esc(req.movie.title)}</div>
          <div class="request-meta">
            <span>📍 ${esc(req.movie.callNumber || 'DVD Section')}</span>
            <span>🏷️ ${esc(req.movie.barcode)}</span>
          </div>
          <div class="request-patron">
            👤 ${esc(req.patron.name)} (${esc(req.patron.barcode)})
          </div>
          <div class="request-time">
            ${req.completed ? '✅ Completed' : '⏱️ Requested'} at ${timeStr}
          </div>
        </div>
        
        <div class="request-actions">
          ${!req.completed ? `
            <button class="btn btn-complete" onclick="completeRequest('${req.id}')">
              ✅ Mark Complete
            </button>
          ` : ''}
          <button class="btn" onclick="deleteRequest('${req.id}')">
            🗑️ Remove
          </button>
        </div>
      </div>
    `;
  }).join('');
}

// Update stats
function updateStats() {
  const pending = requests.filter(r => !r.completed).length;
  const today = requests.filter(r => {
    const d = new Date(r.timestamp);
    const now = new Date();
    return d.toDateString() === now.toDateString();
  }).length;
  
  document.getElementById('pendingCount').textContent = pending;
  document.getElementById('todayCount').textContent = today;
  
  // Update badge
  const badge = document.getElementById('pendingBadge');
  if (pending > 0) {
    badge.textContent = pending;
    badge.style.display = 'block';
  } else {
    badge.style.display = 'none';
  }
  
  // Update page title
  document.title = pending > 0 
    ? `(${pending}) Staff Panel — Paxton Carnegie Library`
    : 'Staff Panel — Paxton Carnegie Library';
}

// Complete a request
async function completeRequest(id) {
  try {
    const res = await fetch('api/requests.php', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, completed: true })
    });
    
    if (res.ok) {
      loadRequests();
    }
  } catch (err) {
    console.error('Failed to complete request:', err);
  }
}

// Delete a request
async function deleteRequest(id) {
  if (!confirm('Remove this request?')) return;
  
  try {
    const res = await fetch(`api/requests.php?id=${id}`, {
      method: 'DELETE'
    });
    
    if (res.ok) {
      loadRequests();
    }
  } catch (err) {
    console.error('Failed to delete request:', err);
  }
}

// Play notification sound
function playNotificationSound() {
  const audio = document.getElementById('notificationSound');
  audio.currentTime = 0;
  audio.play().catch(() => {});
}

// Setup auto-refresh
function setupAutoRefresh() {
  if (autoRefreshInterval) {
    clearInterval(autoRefreshInterval);
  }
  
  if (settings.autoRefresh) {
    autoRefreshInterval = setInterval(loadRequests, 10000);
  }
}

// Event listeners
document.querySelectorAll('.tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    filter = tab.dataset.filter;
    renderRequests();
  });
});

document.getElementById('btnRefresh').addEventListener('click', loadRequests);

document.getElementById('btnSettings').addEventListener('click', () => {
  const panel = document.getElementById('settingsPanel');
  panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
});

document.getElementById('settingSound').addEventListener('change', (e) => {
  settings.sound = e.target.checked;
});

document.getElementById('settingAutoRefresh').addEventListener('change', (e) => {
  settings.autoRefresh = e.target.checked;
  setupAutoRefresh();
});

document.getElementById('settingShowCompleted').addEventListener('change', (e) => {
  settings.showCompleted = e.target.checked;
  renderRequests();
});

document.getElementById('btnClearAll').addEventListener('click', async () => {
  if (!confirm('Clear all completed requests?')) return;
  
  try {
    await fetch('api/requests.php?clearCompleted=true', { method: 'DELETE' });
    loadRequests();
  } catch (err) {
    console.error('Failed to clear requests:', err);
  }
});

// Utility
function esc(str) {
  const div = document.createElement('div');
  div.textContent = str || '';
  return div.innerHTML;
}

// Initialize
loadRequests();
setupAutoRefresh();
</script>
</body>
</html>
