<?php
require_once __DIR__ . '/../config.php';
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
body { font-family: 'Inter', sans-serif; background: #f0f2f5; color: #333; display: flex; min-height: 100vh; }

/* Sidebar */
.sidebar {
  width: 220px;
  background: #1b5e20;
  color: white;
  padding: 20px 0;
  flex-shrink: 0;
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  overflow-y: auto;
}
.sidebar-logo { padding: 0 15px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 15px; }
.sidebar-logo h1 { font-size: 16px; }
.sidebar-logo p { font-size: 11px; opacity: 0.7; }
.nav-section { padding: 12px 15px 6px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.5); }
.nav-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 15px;
  color: rgba(255,255,255,0.85);
  text-decoration: none;
  font-size: 13px;
  cursor: pointer;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
}
.nav-item:hover { background: rgba(255,255,255,0.1); }
.nav-item.active { background: rgba(255,255,255,0.15); color: white; border-left: 3px solid white; }
.nav-badge { margin-left: auto; background: #f44336; color: white; padding: 2px 7px; border-radius: 10px; font-size: 10px; font-weight: 700; }

/* Main */
.main { margin-left: 220px; flex: 1; min-height: 100vh; }
.header {
  background: white;
  padding: 16px 25px;
  border-bottom: 1px solid #e0e0e0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 100;
}
.header h2 { font-size: 20px; color: #1b5e20; }
.header-btns { display: flex; gap: 8px; }
.content { padding: 20px 25px; }

/* Page */
.page { display: none; }
.page.active { display: block; }

/* Cards */
.card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
.card-head { padding: 15px 18px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
.card-head h3 { font-size: 15px; font-weight: 600; }
.card-body { padding: 18px; }

/* Stats */
.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
.stat { background: white; border-radius: 8px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.stat-val { font-size: 28px; font-weight: 700; color: #1b5e20; }
.stat-lbl { font-size: 12px; color: #666; margin-top: 3px; }

/* Buttons */
.btn {
  padding: 8px 14px;
  border-radius: 5px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}
.btn-primary { background: #1b5e20; color: white; }
.btn-primary:hover { background: #145218; }
.btn-secondary { background: #f5f5f5; color: #333; border: 1px solid #ddd; }
.btn-secondary:hover { background: #eee; }
.btn-danger { background: #d32f2f; color: white; }
.btn-sm { padding: 5px 10px; font-size: 12px; }

/* Tables */
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
.table th { font-size: 11px; text-transform: uppercase; color: #666; font-weight: 600; }
.table tbody tr:hover { background: #f9f9f9; }
.table img { width: 40px; height: 60px; object-fit: cover; border-radius: 4px; }

/* Requests */
.req-list { display: flex; flex-direction: column; gap: 12px; }
.req-card {
  background: white;
  border-radius: 8px;
  padding: 14px;
  display: flex;
  gap: 12px;
  align-items: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border-left: 4px solid #ff9800;
}
.req-card.done { border-left-color: #4caf50; opacity: 0.6; }
.req-card.new { border-left-color: #f44336; animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{box-shadow:0 1px 3px rgba(0,0,0,0.1)} 50%{box-shadow:0 0 12px rgba(244,67,54,0.3)} }
.req-poster { width: 50px; height: 75px; object-fit: cover; border-radius: 4px; background: #eee; }
.req-info { flex: 1; }
.req-title { font-weight: 600; font-size: 14px; margin-bottom: 3px; }
.req-meta { font-size: 12px; color: #666; }
.req-patron { display: inline-block; background: #e8f5e9; color: #1b5e20; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-top: 5px; }
.req-type { font-size: 11px; padding: 2px 6px; border-radius: 3px; font-weight: 700; margin-left: 6px; }
.req-type.now { background: #fff3e0; color: #e65100; }
.req-type.hold { background: #e3f2fd; color: #1565c0; }
.req-actions { display: flex; flex-direction: column; gap: 5px; }
.req-time { font-size: 11px; color: #999; text-align: right; }

/* Forms */
.form-group { margin-bottom: 15px; }
.form-label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 13px; }
.form-input, .form-select, .form-textarea {
  width: 100%;
  padding: 9px 12px;
  font-size: 13px;
  border: 1px solid #ddd;
  border-radius: 5px;
  font-family: inherit;
}
.form-input:focus, .form-select:focus, .form-textarea:focus { border-color: #1b5e20; outline: none; }
.form-hint { font-size: 11px; color: #888; margin-top: 3px; }

/* Movie picker */
.picker { border: 1px solid #ddd; border-radius: 6px; max-height: 350px; overflow-y: auto; }
.picker-search { padding: 10px; border-bottom: 1px solid #eee; position: sticky; top: 0; background: white; }
.picker-search input { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
.picker-list { padding: 5px; }
.picker-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px;
  border-radius: 5px;
  cursor: pointer;
}
.picker-item:hover { background: #f5f5f5; }
.picker-item.selected { background: #e8f5e9; border: 1px solid #4caf50; }
.picker-item img { width: 35px; height: 52px; object-fit: cover; border-radius: 3px; background: #eee; }
.picker-item .title { font-size: 13px; font-weight: 500; }
.picker-item .bc { font-size: 11px; color: #666; }

/* Selected tags */
.selected-list { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.selected-tag {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #e8f5e9;
  padding: 4px 8px 4px 4px;
  border-radius: 5px;
  font-size: 12px;
}
.selected-tag img { width: 25px; height: 37px; object-fit: cover; border-radius: 2px; }
.selected-tag .rm { cursor: pointer; color: #888; font-size: 14px; }
.selected-tag .rm:hover { color: #d32f2f; }

/* Toggle */
.toggle-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
.toggle-row:last-child { border-bottom: none; }
.toggle-label { font-size: 13px; }
.toggle-desc { font-size: 11px; color: #888; }
.toggle { position: relative; width: 40px; height: 22px; }
.toggle input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
  position: absolute;
  cursor: pointer;
  inset: 0;
  background: #ccc;
  border-radius: 22px;
  transition: 0.2s;
}
.toggle-slider:before {
  content: "";
  position: absolute;
  width: 16px;
  height: 16px;
  left: 3px;
  bottom: 3px;
  background: white;
  border-radius: 50%;
  transition: 0.2s;
}
.toggle input:checked + .toggle-slider { background: #4caf50; }
.toggle input:checked + .toggle-slider:before { transform: translateX(18px); }

/* Tabs */
.tabs { display: flex; gap: 4px; border-bottom: 1px solid #ddd; margin-bottom: 15px; }
.tab {
  padding: 10px 16px;
  background: none;
  border: none;
  font-size: 13px;
  font-weight: 500;
  color: #666;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
}
.tab:hover { color: #1b5e20; }
.tab.active { color: #1b5e20; border-bottom-color: #1b5e20; }

/* Edit modal */
.modal-bg {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  opacity: 0;
  visibility: hidden;
  transition: all 0.2s;
}
.modal-bg.visible { opacity: 1; visibility: visible; }
.modal {
  background: white;
  border-radius: 10px;
  width: 100%;
  max-width: 550px;
  max-height: 90vh;
  overflow-y: auto;
}
.modal-head { padding: 15px 18px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
.modal-head h3 { font-size: 16px; }
.modal-head .close { background: none; border: none; font-size: 22px; cursor: pointer; color: #666; }
.modal-body { padding: 18px; }
.modal-foot { padding: 12px 18px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 8px; }

/* Image upload */
.img-upload {
  border: 2px dashed #ddd;
  border-radius: 8px;
  padding: 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
}
.img-upload:hover { border-color: #1b5e20; background: #f9f9f9; }
.img-upload img { max-width: 120px; max-height: 180px; border-radius: 4px; margin-bottom: 10px; }
.img-upload input { display: none; }

/* Toast */
.toast {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background: #333;
  color: white;
  padding: 12px 20px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  z-index: 2000;
  transform: translateY(100px);
  opacity: 0;
  transition: all 0.3s;
}
.toast.visible { transform: translateY(0); opacity: 1; }
.toast.success { background: #2e7d32; }
.toast.error { background: #d32f2f; }

/* Empty state */
.empty { text-align: center; padding: 40px; color: #888; }
.empty-icon { font-size: 40px; margin-bottom: 10px; }
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
  <button class="nav-item active" data-page="requests">📋 Movie Requests <span class="nav-badge" id="reqBadge" style="display:none">0</span></button>
  
  <div class="nav-section">Content</div>
  <button class="nav-item" data-page="featured">⭐ Staff Picks</button>
  <button class="nav-item" data-page="arrivals">🆕 New Arrivals</button>
  <button class="nav-item" data-page="movies">🎬 All Movies</button>
  
  <div class="nav-section">System</div>
  <button class="nav-item" data-page="settings">⚙️ Settings</button>
  <button class="nav-item" data-page="cache">🔄 Cache / Sync</button>
</nav>

<!-- Main -->
<div class="main">
  <header class="header">
    <h2 id="pageTitle">Movie Requests</h2>
    <div class="header-btns">
      <button class="btn btn-secondary" id="btnRefresh">🔄 Refresh</button>
    </div>
  </header>
  
  <div class="content">
    <!-- ======== REQUESTS PAGE ======== -->
    <div class="page active" id="pageRequests">
      <div class="stats">
        <div class="stat"><div class="stat-val" id="statPending">0</div><div class="stat-lbl">Pending Now</div></div>
        <div class="stat"><div class="stat-val" id="statHolds">0</div><div class="stat-lbl">On Hold</div></div>
        <div class="stat"><div class="stat-val" id="statToday">0</div><div class="stat-lbl">Today</div></div>
        <div class="stat"><div class="stat-val" id="statTotal">0</div><div class="stat-lbl">All Time</div></div>
      </div>
      
      <div class="card">
        <div class="card-head">
          <h3>Request Queue</h3>
          <button class="btn btn-sm btn-secondary" id="btnClearDone">Clear Completed</button>
        </div>
        <div class="card-body">
          <div class="req-list" id="reqList">
            <div class="empty"><div class="empty-icon">📭</div>No requests yet</div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- ======== FEATURED PAGE ======== -->
    <div class="page" id="pageFeatured">
      <div class="card">
        <div class="card-head">
          <h3>Staff Picks</h3>
          <button class="btn btn-primary" id="btnSaveFeatured">💾 Save</button>
        </div>
        <div class="card-body">
          <p style="margin-bottom:12px;font-size:13px;color:#666;">Select movies to feature on the kiosk "Staff Picks" row.</p>
          <div class="selected-list" id="featuredTags"></div>
          <div class="picker" style="margin-top:15px;">
            <div class="picker-search"><input type="text" placeholder="Search movies..." id="featuredSearch"></div>
            <div class="picker-list" id="featuredList"></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- ======== NEW ARRIVALS PAGE ======== -->
    <div class="page" id="pageArrivals">
      <div class="card">
        <div class="card-head">
          <h3>New Arrivals</h3>
          <button class="btn btn-primary" id="btnSaveArrivals">💾 Save</button>
        </div>
        <div class="card-body">
          <p style="margin-bottom:12px;font-size:13px;color:#666;">Select movies for the "New Arrivals" row on the kiosk.</p>
          <div class="selected-list" id="arrivalsTags"></div>
          <div class="picker" style="margin-top:15px;">
            <div class="picker-search"><input type="text" placeholder="Search movies..." id="arrivalsSearch"></div>
            <div class="picker-list" id="arrivalsList"></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- ======== ALL MOVIES PAGE ======== -->
    <div class="page" id="pageMovies">
      <div class="card">
        <div class="card-head">
          <h3>Movie Inventory</h3>
          <div style="display:flex;gap:10px;align-items:center;">
            <input type="text" placeholder="Search..." id="movieSearch" style="padding:7px 10px;border:1px solid #ddd;border-radius:4px;width:200px;">
            <span id="movieCount" style="font-size:12px;color:#666;">0 movies</span>
          </div>
        </div>
        <div class="card-body" style="padding:0;max-height:500px;overflow-y:auto;">
          <table class="table" id="movieTable">
            <thead><tr><th>Cover</th><th>Title</th><th>Barcode</th><th>Rating</th><th>Call #</th><th>Actions</th></tr></thead>
            <tbody id="movieBody"></tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- ======== SETTINGS PAGE ======== -->
    <div class="page" id="pageSettings">
      <div class="card">
        <div class="card-head">
          <h3>Kiosk Settings</h3>
          <button class="btn btn-primary" id="btnSaveSettings">💾 Save Settings</button>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Library Name</label>
            <input type="text" class="form-input" id="setLibraryName" value="<?php echo htmlspecialchars($settings['libraryName'] ?? 'Paxton Carnegie Library'); ?>">
          </div>
          
          <div class="form-group">
            <label class="form-label">Inactivity Timeout (seconds)</label>
            <input type="number" class="form-input" id="setTimeout" value="<?php echo $settings['timeout'] ?? 60; ?>" min="30" max="300" style="width:120px;">
            <div class="form-hint">How long before showing "Are you still there?"</div>
          </div>
          
          <div class="form-group">
            <label class="form-label">Warning Duration (seconds)</label>
            <input type="number" class="form-input" id="setWarning" value="<?php echo $settings['warning'] ?? 15; ?>" min="5" max="60" style="width:120px;">
            <div class="form-hint">Countdown before auto-logout</div>
          </div>
          
          <div class="form-group">
            <label class="form-label">Display Options</label>
            <div class="toggle-row">
              <div><div class="toggle-label">Show Staff Picks</div><div class="toggle-desc">Display featured movies row</div></div>
              <label class="toggle"><input type="checkbox" id="setShowFeatured" <?php echo ($settings['showFeatured'] ?? true) ? 'checked' : ''; ?>><span class="toggle-slider"></span></label>
            </div>
            <div class="toggle-row">
              <div><div class="toggle-label">Show New Arrivals</div><div class="toggle-desc">Display new arrivals row</div></div>
              <label class="toggle"><input type="checkbox" id="setShowArrivals" <?php echo ($settings['showArrivals'] ?? true) ? 'checked' : ''; ?>><span class="toggle-slider"></span></label>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">Staff Dashboard</label>
            <div class="toggle-row">
              <div><div class="toggle-label">Sound Notifications</div><div class="toggle-desc">Play sound for new requests</div></div>
              <label class="toggle"><input type="checkbox" id="setSound" <?php echo ($settings['sound'] ?? true) ? 'checked' : ''; ?>><span class="toggle-slider"></span></label>
            </div>
            <div class="toggle-row">
              <div><div class="toggle-label">Auto-refresh</div><div class="toggle-desc">Check for new requests every 10s</div></div>
              <label class="toggle"><input type="checkbox" id="setAutoRefresh" <?php echo ($settings['autoRefresh'] ?? true) ? 'checked' : ''; ?>><span class="toggle-slider"></span></label>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- ======== CACHE PAGE ======== -->
    <div class="page" id="pageCache">
      <div class="card">
        <div class="card-head">
          <h3>Cache Management</h3>
        </div>
        <div class="card-body">
          <p style="margin-bottom:15px;font-size:13px;color:#666;">Movie data (covers, titles, call numbers) is cached locally. Availability is fetched fresh when a patron clicks on a movie.</p>
          
          <div class="form-group">
            <button class="btn btn-primary" id="btnRebuildCache">🔄 Rebuild Entire Cache</button>
            <div class="form-hint">Fetches fresh data from Polaris for all movies. This may take several minutes.</div>
          </div>
          
          <div id="cacheProgress" style="display:none;margin-top:15px;">
            <div style="height:8px;background:#eee;border-radius:4px;overflow:hidden;">
              <div id="cacheProgressBar" style="height:100%;background:#4caf50;width:0%;transition:width 0.3s;"></div>
            </div>
            <div id="cacheStatus" style="font-size:12px;color:#666;margin-top:5px;"></div>
          </div>
          
          <hr style="margin:20px 0;border:none;border-top:1px solid #eee;">
          
          <div class="form-group">
            <label class="form-label">Refresh Single Movie</label>
            <div style="display:flex;gap:8px;">
              <input type="text" class="form-input" id="refreshBarcode" placeholder="Enter barcode" style="width:200px;">
              <button class="btn btn-secondary" id="btnRefreshSingle">Refresh</button>
            </div>
          </div>
        </div>
      </div>
      
      <div class="card">
        <div class="card-head">
          <h3>Data Overrides</h3>
        </div>
        <div class="card-body">
          <p style="margin-bottom:15px;font-size:13px;color:#666;">Movie edits made in the staff dashboard are saved as overrides and take priority over API data.</p>
          <button class="btn btn-danger" id="btnClearOverrides">🗑️ Clear All Overrides</button>
          <div class="form-hint">This will reset all custom titles, covers, etc. back to API data.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Movie Modal -->
<div class="modal-bg" id="editModal">
  <div class="modal">
    <div class="modal-head">
      <h3>Edit Movie</h3>
      <button class="close" id="btnCloseEdit">&times;</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editBarcode">
      
      <div class="form-group">
        <label class="form-label">Cover Image</label>
        <div class="img-upload" id="imgUpload">
          <img id="editCoverPreview" src="/img/no-cover.svg" alt="Cover">
          <div>Click to upload new image</div>
          <input type="file" id="editCoverFile" accept="image/*">
        </div>
      </div>
      
      <div class="form-group">
        <label class="form-label">Title</label>
        <input type="text" class="form-input" id="editTitle">
      </div>
      
      <div class="form-group">
        <label class="form-label">Rating</label>
        <select class="form-select" id="editRating">
          <option value="">—</option>
          <option value="G">G</option>
          <option value="PG">PG</option>
          <option value="PG-13">PG-13</option>
          <option value="R">R</option>
          <option value="NC-17">NC-17</option>
          <option value="NR">NR</option>
        </select>
      </div>
      
      <div class="form-group">
        <label class="form-label">Call Number</label>
        <input type="text" class="form-input" id="editCallNumber">
      </div>
      
      <div class="form-group">
        <label class="form-label">Location</label>
        <input type="text" class="form-input" id="editLocation" placeholder="DVD Section">
      </div>
      
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="form-textarea" id="editDescription" rows="3"></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-secondary" id="btnCancelEdit">Cancel</button>
      <button class="btn btn-primary" id="btnSaveEdit">💾 Save Changes</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<audio id="notifySound" preload="auto">
  <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdH2OnqeXjHlwaHB8i5aZlol5bGVufI2dn5eKd2pkcH2OoJ+Wi3VoYW1+kqKgloZwZGFsf5Wkn5KAa2JgbIGYpp2NdmVgaH2Uo52TgW1iYGqCmaaej3RiX2V6kaCdlIJuYV5ofJSinpKBbGFeaH2WpaCQfGphXWd7k6GdlYRvY11meZOgnJWFcWVdZXiRn5yWhXNmXmR2j52ak4VzaF5jdY2bmJKGdWpeYnOLmZeRh3ZsX2Fxipear4t1Yl1gcIibm5SJeW1hX26Gk5ORiHltYV9uhpOSkYh6bWFfbYWSkZGIe21hX22EkZCQiHxtYl9thJGQkIh8bWFfbISQj4+IfG1iYGyDj46OiH1tYmBrgo6Njoh9bWJga4KOjY6IfW1iYGuCjo2NiH1tYmBrgo6NjYl9bWJga4GOjI2JfW1iYWuBjYyMiX1tYmFrgI2MjIl+bWJha4CNjIyJfm1iYWqAjIuLin5tYmJqgIyLi4p+bWJiaoSQj4+LgG9kY2yGkpGRjIJwZWRtiJSTkoyDcWZlbo2ZmJWPhnRoZnCRnJuYkolyamd0laCdmpSMdWtpe5mjoJuWj3ltbH2cpaKdmJF7b26BoKeinpl+cXCEoqagm5l/c3GGo6ehn5qAc3KHpKiioJuBdHKIpKiioZyBdXOIpKiioZyBdXOIpKiioZyBdXOIpKiioZyCdXOIpKiioZyCdXSIpKiioZyCdXSIo6ehoJuBdHSHo6ehoJuBdHOHoqagoJuBc3OGoqagn5qAc3KGoaWfnpmAcnGFoaWfnpmAcXCFoKSenpiBcXCEn6SdnZiBcG+Dn6OdnZiBcG+CnqKcnJeBb2+CnqKcnJeBb2+CnaGbm5aBbm6BnaGbm5aBbm6BnKCam5aBbm2AnKCam5WBbW2Am5+ZmpWAbW1/m5+ZmpWAbWx/mp6YmZSAbGx+mp6YmZSAbGx+mZ2XmJOAbGt9mZ2XmJOAbGt9mJyWl5KAamp8mJyWl5KAamp8l5uVlpGAamp7l5uVlpF/aml7lpqUlZB/aWl6lpqUlZB/aWh5lZmTlI9/aGh5lZmTlI9/aGh4lJiSk45/Z2d3lJiSk45/Z2d3k5eRko5+Zmd2k5eRko5+ZmZ2kpaQkY1+ZmZ1kpaQkY1+ZWV0kZWPkIx9ZWV0kZWPkIx9ZGRzkJSOj4t9ZGRzkJSOj4t9Y2NykJSOj4t9Y2NxkJONjop8Y2NxkJONjop8YmJwj5KMjYl8YmJwj5KMjYl8YWFvjpGLjIl7YWFvjpGLjIl7YWFujZCKi4h7YGBujZCKi4h7YGBtjY+JioZ6X19tjY+JioZ6X19sjI6IiYZ6X15sjI6IiYZ6X15ri42Hh4V5Xl1ri42Hh4V5XVxqi4yGhoR4XVxqioqFhYN4XFtpiomEhIN3W1tpiYiDg4J3W1poiIeCgoF2Wlpoh4aBgYB2WlpnhoWAgH91WVlnhoWAgH91WFhmhYR/f351WFhmhIN+fn10V1dlhIN+fn10V1dlg4J9fXxzVlZkg4J9fXxzVlVkgoF8fHtyVVVjgYB7e3pyVVRjgH97e3lyVFRif396enlyU1Nif395eXhxU1JhfX55eHdwUlJhfX14eHdwUVFge3x3d3ZvUFFge3x3d3ZvT1Bfen12dnVuT09fen12dnVuT05eeXt1dXRtTk5eeXt1dXRtTU1dd3pzdHNsTE1dd3pzdHNsTExcdnlycnJrS0xcdnlycnJrS0tbdXhxcXFqSkpbdXhxcXFqSUpadHdwcHBpSUpadHdwcG9pSEhZc3ZvbnBoSEhZc3ZvbnBoRkdYcnVubW9nRkdYcnVubW9nRUZXcXRtbG1mRUZXcXRtbG1mRERWcHNsbGxlRERWcHNsbGxlQ0NVb3JramtkQ0NVb3JramtkQkJUbnFqaWljQkJUbnFqaWljQUFTbXBpaGhiQUFTbXBpaGhiQEBSbG9oZ2dhQEBSbG9oZ2dhPz9Ra25nZmZgPz9Ra25nZmZgPj5QamxmZWVfPj5QamxmZWVfPT1PaWtlZGRePT1PaWtlZGRePTxOaGpkY2NdPDxOaGpkY2NdOzs=" type="audio/wav">
</audio>

<script>
const NO_COVER = '/img/no-cover.svg';
const $ = s => document.querySelector(s);
const $$ = s => [...document.querySelectorAll(s)];

let movies = [];
let movieMap = {};
let requests = [];
let featuredList = <?php echo json_encode($settings['featured'] ?? []); ?>;
let arrivalsList = <?php echo json_encode($settings['newArrivals'] ?? []); ?>;
let autoRefresh = null;
let lastReqCount = 0;

// ==================== INIT ====================
async function init() {
  await loadMovies();
  await loadRequests();
  renderPickers();
  renderMovieTable();
  setupEvents();
  startAutoRefresh();
}

// ==================== DATA ====================
async function loadMovies() {
  try {
    const res = await fetch('../api/movies.php');
    const data = await res.json();
    if (data.ok) {
      movies = data.items || [];
      movieMap = Object.fromEntries(movies.map(m => [m.barcode, m]));
      $('#movieCount').textContent = `${movies.length} movies`;
    }
  } catch (e) { console.error('loadMovies:', e); }
}

async function loadRequests() {
  try {
    const res = await fetch('../api/requests.php');
    const data = await res.json();
    if (data.ok) {
      const newCount = (data.stats?.pendingNow || 0);
      if (newCount > lastReqCount && requests.length > 0 && $('#setSound')?.checked) {
        $('#notifySound').play().catch(() => {});
      }
      lastReqCount = newCount;
      
      requests = data.requests || [];
      renderRequests();
      updateStats(data.stats);
    }
  } catch (e) { console.error('loadRequests:', e); }
}

// ==================== RENDER ====================
function renderRequests() {
  const pending = requests.filter(r => !r.completed);
  const list = $('#reqList');
  
  if (!pending.length) {
    list.innerHTML = '<div class="empty"><div class="empty-icon">✨</div>All caught up!</div>';
    return;
  }
  
  list.innerHTML = pending.map(r => {
    const isNew = (Date.now() - new Date(r.timestamp).getTime()) < 60000;
    return `
      <div class="req-card ${isNew ? 'new' : ''}" data-id="${r.id}">
        <img class="req-poster" src="${r.movie.cover || NO_COVER}" onerror="this.src='${NO_COVER}'">
        <div class="req-info">
          <div class="req-title">${esc(r.movie.title)}<span class="req-type ${r.type || 'now'}">${r.type === 'hold' ? 'HOLD' : 'NOW'}</span></div>
          <div class="req-meta">📍 ${esc(r.movie.callNumber || 'DVD')} · 🏷️ ${esc(r.movie.barcode)}</div>
          <div class="req-patron">👤 ${esc(r.patron.name || 'Guest')} · ${esc(r.patron.barcode || '—')}</div>
        </div>
        <div class="req-actions">
          <button class="btn btn-sm btn-primary" onclick="completeReq('${r.id}')">✓ Done</button>
          <button class="btn btn-sm btn-secondary" onclick="deleteReq('${r.id}')">✕</button>
          <div class="req-time">${new Date(r.timestamp).toLocaleTimeString()}</div>
        </div>
      </div>
    `;
  }).join('');
}

function updateStats(stats) {
  $('#statPending').textContent = stats?.pendingNow || 0;
  $('#statHolds').textContent = stats?.pendingHolds || 0;
  $('#statToday').textContent = stats?.today || 0;
  $('#statTotal').textContent = stats?.total || 0;
  
  const badge = $('#reqBadge');
  const pending = stats?.pendingNow || 0;
  badge.textContent = pending;
  badge.style.display = pending > 0 ? 'inline' : 'none';
  document.title = pending > 0 ? `(${pending}) Staff Dashboard` : 'Staff Dashboard';
}

function renderPickers() {
  renderPicker('featured', featuredList, '#featuredTags', '#featuredList', '#featuredSearch');
  renderPicker('arrivals', arrivalsList, '#arrivalsTags', '#arrivalsList', '#arrivalsSearch');
}

function renderPicker(type, list, tagsEl, listEl, searchEl) {
  // Selected tags
  $(tagsEl).innerHTML = list.map(bc => {
    const m = movieMap[bc];
    if (!m) return '';
    return `<div class="selected-tag" data-bc="${bc}"><img src="${m.cover || NO_COVER}" onerror="this.src='${NO_COVER}'"><span>${esc(m.title)}</span><span class="rm" onclick="removeFrom('${type}','${bc}')">&times;</span></div>`;
  }).join('') || '<span style="color:#888;font-size:12px;">None selected</span>';
  
  // List
  const q = ($(searchEl).value || '').toLowerCase();
  const filtered = movies.filter(m => !list.includes(m.barcode) && (!q || (m.title || '').toLowerCase().includes(q))).slice(0, 50);
  
  $(listEl).innerHTML = filtered.map(m => `
    <div class="picker-item" onclick="addTo('${type}','${m.barcode}')">
      <img src="${m.cover || NO_COVER}" onerror="this.src='${NO_COVER}'">
      <div><div class="title">${esc(m.title)}</div><div class="bc">${m.barcode}</div></div>
    </div>
  `).join('');
}

function renderMovieTable() {
  const q = ($('#movieSearch').value || '').toLowerCase();
  const filtered = movies.filter(m => !q || (m.title || '').toLowerCase().includes(q) || m.barcode.includes(q));
  
  $('#movieBody').innerHTML = filtered.map(m => `
    <tr>
      <td><img src="${m.cover || NO_COVER}" onerror="this.src='${NO_COVER}'"></td>
      <td><strong>${esc(m.title)}</strong></td>
      <td>${m.barcode}</td>
      <td>${m.rating || '—'}</td>
      <td>${m.callNumber || '—'}</td>
      <td><button class="btn btn-sm btn-secondary" onclick="editMovie('${m.barcode}')">✏️ Edit</button></td>
    </tr>
  `).join('');
}

// ==================== ACTIONS ====================
async function completeReq(id) {
  await fetch('../api/requests.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, completed: true })
  });
  toast('Request completed', 'success');
  loadRequests();
}

async function deleteReq(id) {
  if (!confirm('Remove this request?')) return;
  await fetch(`../api/requests.php?id=${id}`, { method: 'DELETE' });
  toast('Request removed');
  loadRequests();
}

function addTo(type, bc) {
  if (type === 'featured' && !featuredList.includes(bc)) featuredList.push(bc);
  if (type === 'arrivals' && !arrivalsList.includes(bc)) arrivalsList.push(bc);
  renderPickers();
}

function removeFrom(type, bc) {
  if (type === 'featured') featuredList = featuredList.filter(x => x !== bc);
  if (type === 'arrivals') arrivalsList = arrivalsList.filter(x => x !== bc);
  renderPickers();
}

async function savePicker(type) {
  const list = type === 'featured' ? featuredList : arrivalsList;
  const key = type === 'featured' ? 'featured' : 'newArrivals';
  
  await fetch('../api/settings.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ [key]: list })
  });
  toast('Saved!', 'success');
}

async function saveSettings() {
  const settings = {
    libraryName: $('#setLibraryName').value,
    timeout: parseInt($('#setTimeout').value) || 60,
    warning: parseInt($('#setWarning').value) || 15,
    showFeatured: $('#setShowFeatured').checked,
    showArrivals: $('#setShowArrivals').checked,
    sound: $('#setSound').checked,
    autoRefresh: $('#setAutoRefresh').checked
  };
  
  await fetch('../api/settings.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(settings)
  });
  toast('Settings saved!', 'success');
}

function editMovie(bc) {
  const m = movieMap[bc] || { barcode: bc };
  
  $('#editBarcode').value = bc;
  $('#editTitle').value = m.title || '';
  $('#editRating').value = m.rating || '';
  $('#editCallNumber').value = m.callNumber || '';
  $('#editLocation').value = m.location || '';
  $('#editDescription').value = m.description || '';
  $('#editCoverPreview').src = m.cover || NO_COVER;
  
  $('#editModal').classList.add('visible');
}

async function saveMovie() {
  const bc = $('#editBarcode').value;
  
  const data = {
    barcode: bc,
    title: $('#editTitle').value,
    rating: $('#editRating').value,
    callNumber: $('#editCallNumber').value,
    location: $('#editLocation').value,
    description: $('#editDescription').value
  };
  
  await fetch('../api/movies.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
  
  toast('Movie updated!', 'success');
  $('#editModal').classList.remove('visible');
  await loadMovies();
  renderMovieTable();
  renderPickers();
}

async function uploadCover(file) {
  const bc = $('#editBarcode').value;
  if (!bc) return;
  
  const formData = new FormData();
  formData.append('image', file);
  formData.append('barcode', bc);
  
  const res = await fetch('../api/upload.php', { method: 'POST', body: formData });
  const data = await res.json();
  
  if (data.ok) {
    $('#editCoverPreview').src = data.url;
    toast('Image uploaded!', 'success');
  } else {
    toast('Upload failed: ' + data.error, 'error');
  }
}

async function rebuildCache() {
  if (!confirm('This will fetch data from Polaris for all movies. This may take several minutes. Continue?')) return;
  
  $('#cacheProgress').style.display = 'block';
  $('#cacheProgressBar').style.width = '0%';
  $('#cacheStatus').textContent = 'Starting...';
  
  try {
    const res = await fetch('../api/movies.php?action=rebuild', { method: 'PUT' });
    const data = await res.json();
    
    if (data.ok) {
      $('#cacheProgressBar').style.width = '100%';
      $('#cacheStatus').textContent = `Done! Processed ${data.processed} movies.`;
      toast('Cache rebuilt!', 'success');
      await loadMovies();
      renderMovieTable();
    } else {
      $('#cacheStatus').textContent = 'Error: ' + (data.error || 'Unknown');
      toast('Failed', 'error');
    }
  } catch (e) {
    $('#cacheStatus').textContent = 'Error: ' + e.message;
    toast('Failed', 'error');
  }
}

async function refreshSingle() {
  const bc = $('#refreshBarcode').value.trim();
  if (!bc) { toast('Enter a barcode', 'error'); return; }
  
  const res = await fetch(`../api/movies.php?action=refresh&barcode=${encodeURIComponent(bc)}`, { method: 'PUT' });
  const data = await res.json();
  
  if (data.ok) {
    toast('Refreshed!', 'success');
    await loadMovies();
    renderMovieTable();
  } else {
    toast('Failed: ' + (data.error || 'Unknown'), 'error');
  }
}

async function clearOverrides() {
  if (!confirm('This will clear ALL custom movie edits. Are you sure?')) return;
  
  // Just delete the overrides file by posting empty
  await fetch('../api/settings.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ _clearOverrides: true })
  });
  
  toast('Overrides cleared', 'success');
  await loadMovies();
  renderMovieTable();
}

// ==================== EVENTS ====================
function setupEvents() {
  // Navigation
  $$('.nav-item').forEach(n => n.onclick = () => {
    $$('.nav-item').forEach(x => x.classList.remove('active'));
    n.classList.add('active');
    $$('.page').forEach(p => p.classList.remove('active'));
    const pageId = 'page' + n.dataset.page.charAt(0).toUpperCase() + n.dataset.page.slice(1);
    $(('#' + pageId))?.classList.add('active');
    $('#pageTitle').textContent = n.textContent.replace(/[0-9]/g, '').trim();
  });
  
  // Refresh
  $('#btnRefresh').onclick = loadRequests;
  $('#btnClearDone').onclick = async () => {
    await fetch('../api/requests.php?clearCompleted=true', { method: 'DELETE' });
    loadRequests();
  };
  
  // Pickers
  $('#featuredSearch').oninput = () => renderPicker('featured', featuredList, '#featuredTags', '#featuredList', '#featuredSearch');
  $('#arrivalsSearch').oninput = () => renderPicker('arrivals', arrivalsList, '#arrivalsTags', '#arrivalsList', '#arrivalsSearch');
  $('#btnSaveFeatured').onclick = () => savePicker('featured');
  $('#btnSaveArrivals').onclick = () => savePicker('arrivals');
  
  // Movie table
  $('#movieSearch').oninput = renderMovieTable;
  
  // Settings
  $('#btnSaveSettings').onclick = saveSettings;
  
  // Cache
  $('#btnRebuildCache').onclick = rebuildCache;
  $('#btnRefreshSingle').onclick = refreshSingle;
  $('#btnClearOverrides').onclick = clearOverrides;
  
  // Edit modal
  $('#btnCloseEdit').onclick = () => $('#editModal').classList.remove('visible');
  $('#btnCancelEdit').onclick = () => $('#editModal').classList.remove('visible');
  $('#btnSaveEdit').onclick = saveMovie;
  $('#editModal').onclick = e => { if (e.target.id === 'editModal') $('#editModal').classList.remove('visible'); };
  
  // Image upload
  $('#imgUpload').onclick = () => $('#editCoverFile').click();
  $('#editCoverFile').onchange = e => {
    if (e.target.files[0]) uploadCover(e.target.files[0]);
  };
}

function startAutoRefresh() {
  if (autoRefresh) clearInterval(autoRefresh);
  autoRefresh = setInterval(() => {
    if ($('#setAutoRefresh')?.checked !== false) loadRequests();
  }, 10000);
}

// ==================== UTILS ====================
function toast(msg, type = '') {
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

// Start
init();
</script>
</body>
</html>
