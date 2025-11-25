<?php
require_once __DIR__ . '/../config.php';

// Load settings
$settingsFile = __DIR__ . '/../data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Staff Dashboard — Paxton Carnegie Library</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Inter', -apple-system, sans-serif;
  background: #f0f2f5;
  color: #333;
  line-height: 1.5;
}

/* Sidebar */
.sidebar {
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  width: 240px;
  background: #1b5e20;
  color: white;
  padding: 20px 0;
  overflow-y: auto;
}

.sidebar-logo {
  padding: 0 20px 20px;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  margin-bottom: 20px;
}

.sidebar-logo h1 {
  font-size: 18px;
  font-weight: 700;
}

.sidebar-logo p {
  font-size: 12px;
  opacity: 0.7;
}

.nav-item {
  display: block;
  padding: 12px 20px;
  color: rgba(255,255,255,0.8);
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
}

.nav-item:hover {
  background: rgba(255,255,255,0.1);
  color: white;
}

.nav-item.active {
  background: rgba(255,255,255,0.15);
  color: white;
  border-left: 3px solid white;
}

.nav-item .badge {
  float: right;
  background: #ff5722;
  color: white;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 700;
}

.nav-section {
  padding: 15px 20px 8px;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: rgba(255,255,255,0.5);
}

/* Main content */
.main {
  margin-left: 240px;
  min-height: 100vh;
}

.header {
  background: white;
  padding: 20px 30px;
  border-bottom: 1px solid #e0e0e0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header h2 {
  font-size: 22px;
  font-weight: 700;
  color: #1b5e20;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.content {
  padding: 25px 30px;
}

/* Page sections */
.page { display: none; }
.page.active { display: block; }

/* Cards */
.card {
  background: white;
  border-radius: 10px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  margin-bottom: 20px;
}

.card-header {
  padding: 18px 20px;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  font-size: 16px;
  font-weight: 600;
}

.card-body {
  padding: 20px;
}

/* Stats row */
.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 25px;
}

.stat-card {
  background: white;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-value {
  font-size: 32px;
  font-weight: 700;
  color: #1b5e20;
}

.stat-label {
  font-size: 13px;
  color: #666;
  margin-top: 4px;
}

/* Buttons */
.btn {
  padding: 10px 18px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-primary {
  background: #1b5e20;
  color: white;
}

.btn-primary:hover {
  background: #145218;
}

.btn-secondary {
  background: #f5f5f5;
  color: #333;
  border: 1px solid #ddd;
}

.btn-secondary:hover {
  background: #eee;
}

.btn-danger {
  background: #d32f2f;
  color: white;
}

.btn-danger:hover {
  background: #b71c1c;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
}

/* Tables */
.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.table th {
  font-weight: 600;
  color: #666;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.table tbody tr:hover {
  background: #f9f9f9;
}

/* Request cards */
.request-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.request-card {
  background: white;
  border-radius: 10px;
  padding: 18px;
  display: flex;
  gap: 15px;
  align-items: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border-left: 4px solid #ff9800;
}

.request-card.completed {
  border-left-color: #4caf50;
  opacity: 0.7;
}

.request-card.new {
  border-left-color: #f44336;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
  50% { box-shadow: 0 0 15px rgba(244, 67, 54, 0.3); }
}

.request-poster {
  width: 60px;
  height: 90px;
  object-fit: cover;
  border-radius: 4px;
  background: #eee;
}

.request-info {
  flex: 1;
}

.request-title {
  font-weight: 600;
  font-size: 16px;
  margin-bottom: 4px;
}

.request-meta {
  font-size: 13px;
  color: #666;
}

.request-meta span {
  margin-right: 15px;
}

.request-patron {
  background: #e8f5e9;
  color: #1b5e20;
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 13px;
  font-weight: 600;
  display: inline-block;
  margin-top: 6px;
}

.request-type {
  font-size: 12px;
  padding: 3px 8px;
  border-radius: 4px;
  font-weight: 600;
  margin-left: 8px;
}

.request-type.now {
  background: #fff3e0;
  color: #e65100;
}

.request-type.hold {
  background: #e3f2fd;
  color: #1565c0;
}

.request-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.request-time {
  font-size: 12px;
  color: #999;
  text-align: right;
}

/* Forms */
.form-group {
  margin-bottom: 18px;
}

.form-label {
  display: block;
  font-weight: 600;
  margin-bottom: 6px;
  font-size: 14px;
}

.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: 10px 14px;
  font-size: 14px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-family: inherit;
  outline: none;
  transition: border-color 0.2s;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  border-color: #1b5e20;
}

.form-textarea {
  min-height: 100px;
  resize: vertical;
}

.form-hint {
  font-size: 12px;
  color: #666;
  margin-top: 4px;
}

/* Movie picker */
.movie-picker {
  border: 1px solid #ddd;
  border-radius: 8px;
  max-height: 400px;
  overflow-y: auto;
}

.movie-picker-search {
  padding: 12px;
  border-bottom: 1px solid #eee;
  position: sticky;
  top: 0;
  background: white;
}

.movie-picker-search input {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.movie-picker-list {
  padding: 8px;
}

.movie-picker-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.15s;
}

.movie-picker-item:hover {
  background: #f5f5f5;
}

.movie-picker-item.selected {
  background: #e8f5e9;
  border: 1px solid #4caf50;
}

.movie-picker-item img {
  width: 40px;
  height: 60px;
  object-fit: cover;
  border-radius: 4px;
  background: #eee;
}

.movie-picker-item .title {
  font-weight: 500;
  font-size: 14px;
}

.movie-picker-item .barcode {
  font-size: 12px;
  color: #666;
}

/* Selected movies list */
.selected-movies {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 15px;
}

.selected-movie {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #e8f5e9;
  padding: 6px 10px 6px 6px;
  border-radius: 6px;
  font-size: 13px;
}

.selected-movie img {
  width: 30px;
  height: 45px;
  object-fit: cover;
  border-radius: 3px;
}

.selected-movie .remove {
  cursor: pointer;
  color: #666;
  font-size: 16px;
  margin-left: 4px;
}

.selected-movie .remove:hover {
  color: #d32f2f;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 50px;
  color: #666;
}

.empty-state .icon {
  font-size: 50px;
  margin-bottom: 12px;
}

.empty-state h4 {
  font-size: 18px;
  color: #333;
  margin-bottom: 5px;
}

/* Toast */
.toast {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background: #333;
  color: white;
  padding: 14px 24px;
  border-radius: 8px;
  font-weight: 500;
  z-index: 1000;
  transform: translateY(100px);
  opacity: 0;
  transition: all 0.3s;
}

.toast.visible {
  transform: translateY(0);
  opacity: 1;
}

.toast.success { background: #2e7d32; }
.toast.error { background: #d32f2f; }

/* Tabs */
.tabs {
  display: flex;
  gap: 5px;
  border-bottom: 1px solid #ddd;
  margin-bottom: 20px;
}

.tab {
  padding: 12px 20px;
  background: none;
  border: none;
  font-size: 14px;
  font-weight: 500;
  color: #666;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
}

.tab:hover {
  color: #1b5e20;
}

.tab.active {
  color: #1b5e20;
  border-bottom-color: #1b5e20;
}

/* Settings sections */
.settings-section {
  margin-bottom: 30px;
}

.settings-section h4 {
  font-size: 15px;
  font-weight: 600;
  margin-bottom: 15px;
  padding-bottom: 8px;
  border-bottom: 1px solid #eee;
}

/* Toggle */
.toggle-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}

.toggle-row:last-child {
  border-bottom: none;
}

.toggle-label {
  font-size: 14px;
}

.toggle-desc {
  font-size: 12px;
  color: #666;
}

.toggle {
  position: relative;
  width: 44px;
  height: 24px;
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
  background: #ccc;
  border-radius: 24px;
  transition: 0.2s;
}

.toggle-slider:before {
  content: "";
  position: absolute;
  width: 18px;
  height: 18px;
  left: 3px;
  bottom: 3px;
  background: white;
  border-radius: 50%;
  transition: 0.2s;
}

.toggle input:checked + .toggle-slider {
  background: #4caf50;
}

.toggle input:checked + .toggle-slider:before {
  transform: translateX(20px);
}
</style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
  <div class="sidebar-logo">
    <h1>📚 Paxton Carnegie</h1>
    <p>Staff Dashboard</p>
  </div>
  
  <div class="nav-section">Requests</div>
  <button class="nav-item active" data-page="requests">
    📋 Movie Requests
    <span class="badge" id="pendingBadge" style="display:none">0</span>
  </button>
  
  <div class="nav-section">Content</div>
  <button class="nav-item" data-page="featured">⭐ Staff Picks</button>
  <button class="nav-item" data-page="arrivals">🆕 New Arrivals</button>
  <button class="nav-item" data-page="movies">🎬 All Movies</button>
  
  <div class="nav-section">System</div>
  <button class="nav-item" data-page="settings">⚙️ Settings</button>
</nav>

<!-- Main -->
<div class="main">
  <header class="header">
    <h2 id="pageTitle">Movie Requests</h2>
    <div class="header-actions">
      <button class="btn btn-secondary" id="btnRefresh">🔄 Refresh</button>
    </div>
  </header>
  
  <div class="content">
    <!-- Requests Page -->
    <div class="page active" id="pageRequests">
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-value" id="statPending">0</div>
          <div class="stat-label">Pending Now</div>
        </div>
        <div class="stat-card">
          <div class="stat-value" id="statHolds">0</div>
          <div class="stat-label">Holds</div>
        </div>
        <div class="stat-card">
          <div class="stat-value" id="statToday">0</div>
          <div class="stat-label">Today</div>
        </div>
        <div class="stat-card">
          <div class="stat-value" id="statTotal">0</div>
          <div class="stat-label">All Time</div>
        </div>
      </div>
      
      <div class="card">
        <div class="card-header">
          <h3>Pending Requests</h3>
          <div>
            <button class="btn btn-sm btn-secondary" id="btnClearCompleted">Clear Completed</button>
          </div>
        </div>
        <div class="card-body">
          <div class="request-list" id="requestList">
            <div class="empty-state">
              <div class="icon">📭</div>
              <h4>No pending requests</h4>
              <p>Requests from the kiosk will appear here</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Featured/Staff Picks Page -->
    <div class="page" id="pageFeatured">
      <div class="card">
        <div class="card-header">
          <h3>Staff Picks</h3>
          <button class="btn btn-primary" id="btnSaveFeatured">💾 Save Changes</button>
        </div>
        <div class="card-body">
          <p style="margin-bottom:15px;color:#666;">Select movies to feature on the kiosk home screen. These appear in the "Staff Picks" section.</p>
          
          <div class="selected-movies" id="featuredSelected"></div>
          
          <div class="movie-picker" style="margin-top:20px;">
            <div class="movie-picker-search">
              <input type="text" placeholder="Search movies to add..." id="featuredSearch">
            </div>
            <div class="movie-picker-list" id="featuredPickerList"></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- New Arrivals Page -->
    <div class="page" id="pageArrivals">
      <div class="card">
        <div class="card-header">
          <h3>New Arrivals</h3>
          <button class="btn btn-primary" id="btnSaveArrivals">💾 Save Changes</button>
        </div>
        <div class="card-body">
          <p style="margin-bottom:15px;color:#666;">Select movies to show in the "New Arrivals" section on the kiosk.</p>
          
          <div class="selected-movies" id="arrivalsSelected"></div>
          
          <div class="movie-picker" style="margin-top:20px;">
            <div class="movie-picker-search">
              <input type="text" placeholder="Search movies to add..." id="arrivalsSearch">
            </div>
            <div class="movie-picker-list" id="arrivalsPickerList"></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- All Movies Page -->
    <div class="page" id="pageMovies">
      <div class="card">
        <div class="card-header">
          <h3>Movie Inventory</h3>
          <div>
            <input type="text" placeholder="Search..." id="movieSearch" style="padding:8px 12px;border:1px solid #ddd;border-radius:6px;margin-right:10px;">
            <span id="movieCount">0 movies</span>
          </div>
        </div>
        <div class="card-body" style="padding:0;max-height:600px;overflow-y:auto;">
          <table class="table" id="movieTable">
            <thead>
              <tr>
                <th>Cover</th>
                <th>Title</th>
                <th>Barcode</th>
                <th>Rating</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="movieTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Settings Page -->
    <div class="page" id="pageSettings">
      <div class="card">
        <div class="card-header">
          <h3>Kiosk Settings</h3>
          <button class="btn btn-primary" id="btnSaveSettings">💾 Save Settings</button>
        </div>
        <div class="card-body">
          <div class="settings-section">
            <h4>Display</h4>
            <div class="toggle-row">
              <div>
                <div class="toggle-label">Show Staff Picks</div>
                <div class="toggle-desc">Display featured movies on home screen</div>
              </div>
              <label class="toggle">
                <input type="checkbox" id="settingShowFeatured" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="toggle-row">
              <div>
                <div class="toggle-label">Show New Arrivals</div>
                <div class="toggle-desc">Display new arrivals section</div>
              </div>
              <label class="toggle">
                <input type="checkbox" id="settingShowArrivals" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
          
          <div class="settings-section">
            <h4>Session</h4>
            <div class="form-group">
              <label class="form-label">Inactivity Timeout (seconds)</label>
              <input type="number" class="form-input" id="settingTimeout" value="60" min="30" max="300" style="width:150px;">
              <div class="form-hint">How long before showing "Are you still there?"</div>
            </div>
            <div class="form-group">
              <label class="form-label">Warning Duration (seconds)</label>
              <input type="number" class="form-input" id="settingWarning" value="15" min="5" max="60" style="width:150px;">
              <div class="form-hint">How long to show warning before auto-logout</div>
            </div>
          </div>
          
          <div class="settings-section">
            <h4>Notifications</h4>
            <div class="toggle-row">
              <div>
                <div class="toggle-label">Sound Alerts</div>
                <div class="toggle-desc">Play sound for new requests</div>
              </div>
              <label class="toggle">
                <input type="checkbox" id="settingSound" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
            <div class="toggle-row">
              <div>
                <div class="toggle-label">Auto-refresh</div>
                <div class="toggle-desc">Automatically check for new requests</div>
              </div>
              <label class="toggle">
                <input type="checkbox" id="settingAutoRefresh" checked>
                <span class="toggle-slider"></span>
              </label>
            </div>
          </div>
          
          <div class="settings-section">
            <h4>Library Info</h4>
            <div class="form-group">
              <label class="form-label">Library Name</label>
              <input type="text" class="form-input" id="settingLibraryName" value="Paxton Carnegie Library">
            </div>
            <div class="form-group">
              <label class="form-label">Kiosk Message</label>
              <textarea class="form-textarea" id="settingMessage" placeholder="Optional message to display on kiosk"></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<audio id="notificationSound" preload="auto">
  <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdH2Onp6XjHlwaHB8i5aZlol5bGVufI2dn5eKd2pkcH2OoJ+Wi3VoYW1+kqKgloZwZGFsf5Wkn5KAa2JgbIGYpp2NdmVgaH2Uo52TgW1iYGqCmaaej3RiX2V6kaCdlIJuYV5ofJSinpKBbGFeaH2WpaCQfGphXWd7k6GdlYRvY11meZOgnJWFcWVdZXiRn5yWhXNmXmR2j52ak4VzaF5jdY2bmJKGdWpeYnOLmZeRh3ZsX2Fxipear4t1Yl1gcIibm5SJeW1hX26Gk5ORiHltYV9uhpOSkYh6bWFfbYWSkZGIe21hX22EkZCQiHxtYl9thJGQkIh8bWFfbISQj4+IfG1iYGyDj46OiH1tYmBrgo6Njoh9bWJga4KOjY6IfW1iYGuCjo2NiH1tYmBrgo6NjYl9bWJga4GOjI2JfW1iYWuBjYyMiX1tYmFrgI2MjIl+bWJha4CNjIyJfm1iYWqAjIuLin5tYmJqgIyLi4p+bWJiaoSQj4+LgG9kY2yGkpGRjIJwZWRtiJSTkoyDcWZlbo2ZmJWPhnRoZnCRnJuYkolyamd0laCdmpSMdWtpe5mjoJuWj3ltbH2cpaKdmJF7b26BoKeinpl+cXCEoqagm5l/c3GGo6ehn5qAc3KHpKiioJuBdHKIpKiioZyBdXOIpKiioZyBdXOIpKiioZyBdXOIpKiioZyCdXOIpKiioZyCdXSIpKiioZyCdXSIo6ehoJuBdHSHo6ehoJuBdHOHoqagoJuBc3OGoqagn5qAc3KGoaWfnpmAcnGFoaWfnpmAcXCFoKSenpiBcXCEn6SdnZiBcG+Dn6OdnZiBcG+DnqKcnJeBb2+CnqKcnJeBb2+CnaGbm5aBbm6BnaGbm5aBbm6BnKCam5aBbm2AnKCam5WBbW2Am5+ZmpWAbW1/m5+ZmpWAbWx/mp6YmZSAbGx+mp6YmZSAbGx+mZ2XmJOAbGt9mZ2XmJOAbGt9mJyWl5KAa2t8mJyWl5KAa2p8l5uVlpGAamp7l5uVlpF/aml7lpqUlZB/aWl6lpqUlZB/aWh5lZmTlI9/aGh5lZmTlI9/aGh4lJiSk45/Z2d3lJiSk45/Z2d3k5eRko5+Zmd2k5eRko5+ZmZ2kpaQkY1+ZmZ1kpaQkY1+ZWV0kZWPkIx9ZWV0kZWPkIx9ZGRzkJSOj4t9ZGRzkJSOj4t9Y2NykJSOj4t9Y2NxkJONjop8Y2NxkJONjop8YmJwj5KMjYl8YmJwj5KMjYl8YWFvjpGLjIl7YWFvjpGLjIl7YWFujZCKi4h7YGBujZCKi4h7YGBtjY+JioZ6X19tjY+JioZ6X19sjI6IiYZ6X15sjI6IiYZ6X15ri42Hh4V5Xl1ri42Hh4V5XVxqi4yGhoR4XVxqioqFhYN4XFtpiomEhIN3W1tpiYiDg4J3W1poiIeCgoF2Wlpoh4aBgYB2WlpnhoWAgH91WVlnhoWAgH91WFhmhYR/f351WFhmhIN+fn10V1dlhIN+fn10V1dlg4J9fXxzVlZkg4J9fXxzVlVkgoF8fHtyVVVjgYB7e3pyVVRjgH97e3lyVFRif396enlyU1Nif395eXhxU1JhfX55eHdwUlJhfX14eHdwUVFge3x3d3ZvUFFge3x3d3ZvT1Bfen12dnVuT09fen12dnVuT05eeXt1dXRtTk5eeXt1dXRtTU1dd3pzdHNsTE1dd3pzdHNsTExcdnlycnJrS0xcdnlycnJrS0tbdXhxcXFqSkpbdXhxcXFqSUpadHdwcHBpSUpadHdwcG9pSEhZc3ZvbnBoSEhZc3ZvbnBoRkdYcnVubW9nRkdYcnVubW9nRUZXcXRtbG1mRUZXcXRtbG1mRERWcHNsbGxlRERWcHNsbGxlQ0NVb3JramtkQ0NVb3JramtkQkJUbnFqaWljQkJUbnFqaWljQUFTbXBpaGhiQUFTbXBpaGhiQEBSbG9oZ2dhQEBSbG9oZ2dhPz9Ra25nZmZgPz9Ra25nZmZgPj5QamxmZWVfPj5QamxmZWVfPT1PaWtlZGRePT1PaWtlZGRePTxOaGpkY2NdPDxOaGpkY2NdOzs=" type="audio/wav">
</audio>

<script>
const $ = s => document.querySelector(s);
const $$ = s => Array.from(document.querySelectorAll(s));

let movies = [];
let movieIndex = {};
let requests = [];
let featuredList = <?php echo json_encode($settings['featured'] ?? []); ?>;
let arrivalsList = <?php echo json_encode($settings['newArrivals'] ?? []); ?>;
let autoRefreshInterval = null;

async function init() {
  await loadMovies();
  await loadRequests();
  renderMoviePickers();
  renderMovieTable();
  setupEvents();
  startAutoRefresh();
}

async function loadMovies() {
  try {
    const res = await fetch('../api/list.php');
    const data = await res.json();
    if (data.ok) {
      movies = data.items || [];
      movieIndex = Object.fromEntries(movies.map(m => [m.barcode, m]));
      $('#movieCount').textContent = `${movies.length} movies`;
    }
  } catch (e) {}
}

async function loadRequests() {
  try {
    const res = await fetch('../api/requests.php');
    const data = await res.json();
    if (data.ok) {
      const newCount = (data.requests || []).filter(r => !r.completed && r.type === 'now').length;
      const oldCount = requests.filter(r => !r.completed && r.type === 'now').length;
      
      if (newCount > oldCount && requests.length > 0) {
        playSound();
      }
      
      requests = data.requests || [];
      renderRequests();
      updateStats();
    }
  } catch (e) {}
}

function renderRequests() {
  const pending = requests.filter(r => !r.completed);
  const list = $('#requestList');
  
  if (pending.length === 0) {
    list.innerHTML = `<div class="empty-state"><div class="icon">✨</div><h4>All caught up!</h4><p>No pending requests</p></div>`;
    return;
  }
  
  list.innerHTML = pending.map(r => {
    const time = new Date(r.timestamp);
    const isNew = (Date.now() - time.getTime()) < 60000;
    
    return `
      <div class="request-card ${isNew ? 'new' : ''}" data-id="${r.id}">
        <img class="request-poster" src="${r.movie.cover || '../img/no-cover.svg'}" onerror="this.src='../img/no-cover.svg'">
        <div class="request-info">
          <div class="request-title">
            ${esc(r.movie.title)}
            <span class="request-type ${r.type || 'now'}">${r.type === 'hold' ? 'HOLD' : 'NOW'}</span>
          </div>
          <div class="request-meta">
            <span>📍 ${esc(r.movie.callNumber || 'DVD')}</span>
            <span>🏷️ ${esc(r.movie.barcode)}</span>
          </div>
          <div class="request-patron">👤 Card: ${esc(r.patron.barcode)}</div>
        </div>
        <div class="request-actions">
          <button class="btn btn-sm btn-primary" onclick="completeRequest('${r.id}')">✓ Complete</button>
          <button class="btn btn-sm btn-secondary" onclick="deleteRequest('${r.id}')">✕ Remove</button>
          <div class="request-time">${time.toLocaleTimeString()}</div>
        </div>
      </div>
    `;
  }).join('');
}

function updateStats() {
  const pending = requests.filter(r => !r.completed && r.type === 'now').length;
  const holds = requests.filter(r => !r.completed && r.type === 'hold').length;
  const today = requests.filter(r => new Date(r.timestamp).toDateString() === new Date().toDateString()).length;
  
  $('#statPending').textContent = pending;
  $('#statHolds').textContent = holds;
  $('#statToday').textContent = today;
  $('#statTotal').textContent = requests.length;
  
  const badge = $('#pendingBadge');
  if (pending > 0) {
    badge.textContent = pending;
    badge.style.display = 'inline';
  } else {
    badge.style.display = 'none';
  }
  
  document.title = pending > 0 ? `(${pending}) Staff Dashboard` : 'Staff Dashboard';
}

async function completeRequest(id) {
  await fetch('../api/requests.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, completed: true })
  });
  showToast('Request completed', 'success');
  loadRequests();
}

async function deleteRequest(id) {
  if (!confirm('Remove this request?')) return;
  await fetch(`../api/requests.php?id=${id}`, { method: 'DELETE' });
  showToast('Request removed');
  loadRequests();
}

function renderMoviePickers() {
  renderPicker('featured', featuredList, '#featuredSelected', '#featuredPickerList', '#featuredSearch');
  renderPicker('arrivals', arrivalsList, '#arrivalsSelected', '#arrivalsPickerList', '#arrivalsSearch');
}

function renderPicker(type, selectedList, selectedEl, listEl, searchEl) {
  // Render selected
  $(selectedEl).innerHTML = selectedList.map(bc => {
    const m = movieIndex[bc];
    if (!m) return '';
    return `
      <div class="selected-movie" data-barcode="${bc}">
        <img src="${m.cover || '../img/no-cover.svg'}" onerror="this.src='../img/no-cover.svg'">
        <span>${esc(m.title)}</span>
        <span class="remove" onclick="removeFrom('${type}', '${bc}')">×</span>
      </div>
    `;
  }).join('') || '<span style="color:#666">No movies selected</span>';
  
  // Render picker list
  const query = ($(searchEl).value || '').toLowerCase();
  const filtered = movies.filter(m => {
    if (selectedList.includes(m.barcode)) return false;
    if (query && !(m.title || '').toLowerCase().includes(query)) return false;
    return true;
  }).slice(0, 50);
  
  $(listEl).innerHTML = filtered.map(m => `
    <div class="movie-picker-item" onclick="addTo('${type}', '${m.barcode}')">
      <img src="${m.cover || '../img/no-cover.svg'}" onerror="this.src='../img/no-cover.svg'">
      <div>
        <div class="title">${esc(m.title)}</div>
        <div class="barcode">${m.barcode}</div>
      </div>
    </div>
  `).join('');
}

function addTo(type, barcode) {
  if (type === 'featured') {
    if (!featuredList.includes(barcode)) featuredList.push(barcode);
  } else {
    if (!arrivalsList.includes(barcode)) arrivalsList.push(barcode);
  }
  renderMoviePickers();
}

function removeFrom(type, barcode) {
  if (type === 'featured') {
    featuredList = featuredList.filter(bc => bc !== barcode);
  } else {
    arrivalsList = arrivalsList.filter(bc => bc !== barcode);
  }
  renderMoviePickers();
}

async function saveList(type) {
  const list = type === 'featured' ? featuredList : arrivalsList;
  const key = type === 'featured' ? 'featured' : 'newArrivals';
  
  await fetch('../api/settings.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ [key]: list })
  });
  showToast('Saved!', 'success');
}

function renderMovieTable() {
  const query = ($('#movieSearch').value || '').toLowerCase();
  const filtered = movies.filter(m => {
    if (query && !(m.title || '').toLowerCase().includes(query) && !m.barcode.includes(query)) return false;
    return true;
  });
  
  $('#movieTableBody').innerHTML = filtered.map(m => `
    <tr>
      <td><img src="${m.cover || '../img/no-cover.svg'}" style="width:40px;height:60px;object-fit:cover;border-radius:4px;" onerror="this.src='../img/no-cover.svg'"></td>
      <td><strong>${esc(m.title)}</strong></td>
      <td>${m.barcode}</td>
      <td>${m.rating || '—'}</td>
      <td>${m.status || '—'}</td>
    </tr>
  `).join('');
}

function setupEvents() {
  // Navigation
  $$('.nav-item').forEach(n => n.onclick = () => {
    $$('.nav-item').forEach(x => x.classList.remove('active'));
    n.classList.add('active');
    $$('.page').forEach(p => p.classList.remove('active'));
    $(`#page${capitalize(n.dataset.page)}`).classList.add('active');
    $('#pageTitle').textContent = n.textContent.replace(/[0-9]/g, '').trim();
  });
  
  // Refresh
  $('#btnRefresh').onclick = loadRequests;
  $('#btnClearCompleted').onclick = async () => {
    await fetch('../api/requests.php?clearCompleted=true', { method: 'DELETE' });
    loadRequests();
  };
  
  // Pickers
  $('#featuredSearch').oninput = () => renderPicker('featured', featuredList, '#featuredSelected', '#featuredPickerList', '#featuredSearch');
  $('#arrivalsSearch').oninput = () => renderPicker('arrivals', arrivalsList, '#arrivalsSelected', '#arrivalsPickerList', '#arrivalsSearch');
  $('#btnSaveFeatured').onclick = () => saveList('featured');
  $('#btnSaveArrivals').onclick = () => saveList('arrivals');
  
  // Movie table search
  $('#movieSearch').oninput = renderMovieTable;
  
  // Settings
  $('#btnSaveSettings').onclick = saveSettings;
}

async function saveSettings() {
  const settings = {
    showFeatured: $('#settingShowFeatured').checked,
    showArrivals: $('#settingShowArrivals').checked,
    timeout: parseInt($('#settingTimeout').value) || 60,
    warning: parseInt($('#settingWarning').value) || 15,
    sound: $('#settingSound').checked,
    autoRefresh: $('#settingAutoRefresh').checked,
    libraryName: $('#settingLibraryName').value,
    message: $('#settingMessage').value
  };
  
  await fetch('../api/settings.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(settings)
  });
  
  showToast('Settings saved!', 'success');
}

function startAutoRefresh() {
  if (autoRefreshInterval) clearInterval(autoRefreshInterval);
  autoRefreshInterval = setInterval(loadRequests, 10000);
}

function playSound() {
  if ($('#settingSound').checked) {
    $('#notificationSound').currentTime = 0;
    $('#notificationSound').play().catch(() => {});
  }
}

function showToast(msg, type = '') {
  const t = $('#toast');
  t.textContent = msg;
  t.className = 'toast visible ' + type;
  setTimeout(() => t.classList.remove('visible'), 3000);
}

function esc(s) { 
  const d = document.createElement('div'); 
  d.textContent = s || ''; 
  return d.innerHTML; 
}

function capitalize(s) {
  return s.charAt(0).toUpperCase() + s.slice(1);
}

init();
</script>
</body>
</html>
