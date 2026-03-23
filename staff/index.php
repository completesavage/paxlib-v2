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
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
  --green-dark:  #1b5e20;
  --green-mid:   #2e7d32;
  --green-light: #e8f5e9;
  --green-pale:  #f1f8f1;
  --blue:        #1565c0;
  --blue-light:  #e3f2fd;
  --orange:      #e65100;
  --orange-light:#fff3e0;
  --red:         #c62828;
  --gold:        #f57f17;
  --gold-light:  #fff8e1;
  --purple:      #6a1b9a;
  --purple-light:#f3e5f5;
  --border:      #e0e0e0;
  --bg:          #f4f6f4;
  --card:        #ffffff;
  --text:        #1a1a1a;
  --muted:       #666;
  --shadow-sm:   0 1px 3px rgba(0,0,0,0.08);
  --shadow-md:   0 4px 16px rgba(0,0,0,0.1);
  --shadow-lg:   0 8px 32px rgba(0,0,0,0.14);
  --radius:      10px;
}

* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

/* SIDEBAR */
.sidebar { width: 230px; background: var(--green-dark); color: white; flex-shrink: 0; position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; display: flex; flex-direction: column; }
.sidebar-logo { padding: 22px 18px 18px; border-bottom: 1px solid rgba(255,255,255,0.12); }
.sidebar-logo h1 { font-size: 17px; font-weight: 700; letter-spacing: -0.02em; }
.sidebar-logo p  { font-size: 11px; opacity: 0.6; margin-top: 2px; }
.nav-section { padding: 16px 18px 6px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255,255,255,0.45); font-weight: 600; }
.nav-item { display: flex; align-items: center; gap: 10px; padding: 11px 18px; color: rgba(255,255,255,0.8); text-decoration: none; font-size: 13.5px; font-weight: 500; cursor: pointer; border: none; background: none; width: 100%; text-align: left; transition: background 0.15s, color 0.15s; border-left: 3px solid transparent; }
.nav-item .nav-icon { font-size: 18px; flex-shrink: 0; }
.nav-item:hover { background: rgba(255,255,255,0.08); color: white; }
.nav-item.active { background: rgba(255,255,255,0.14); color: white; border-left-color: #a5d6a7; }
.nav-badge { margin-left: auto; background: #ef5350; color: white; padding: 2px 7px; border-radius: 20px; font-size: 11px; font-weight: 700; }

/* MAIN */
.main { margin-left: 230px; flex: 1; min-height: 100vh; display: flex; flex-direction: column; }
.topbar { background: var(--card); padding: 14px 28px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 200; box-shadow: var(--shadow-sm); }
.topbar h2 { font-size: 20px; font-weight: 700; letter-spacing: -0.02em; color: var(--green-dark); }
.topbar-right { display: flex; gap: 10px; align-items: center; }
.content { padding: 24px 28px; flex: 1; }

/* PAGES */
.page { display: none; }
.page.active { display: block; }

/* BUTTONS */
.btn { padding: 9px 16px; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; font-family: inherit; display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s; white-space: nowrap; }
.btn:active { transform: scale(0.97); }
.btn-primary { background: var(--green-dark); color: white; }
.btn-primary:hover { background: var(--green-mid); }
.btn-secondary { background: #f5f5f5; color: var(--text); border: 1px solid var(--border); }
.btn-secondary:hover { background: #ebebeb; }
.btn-danger { background: var(--red); color: white; }
.btn-ghost { background: transparent; color: var(--muted); border: 1px solid var(--border); }
.btn-ghost:hover { background: #f5f5f5; color: var(--text); }
.btn-sm { padding: 6px 12px; font-size: 12px; }
.btn-xs { padding: 4px 9px; font-size: 11px; border-radius: 5px; }

/* STATS */
.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 22px; }
.stat { background: var(--card); border-radius: var(--radius); padding: 18px 20px; box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
.stat-val { font-size: 32px; font-weight: 700; letter-spacing: -0.03em; color: var(--green-dark); line-height: 1; }
.stat-lbl { font-size: 12px; color: var(--muted); margin-top: 5px; font-weight: 500; }

/* CARD */
.card { background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border); margin-bottom: 20px; overflow: hidden; }
.card-head { padding: 14px 18px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fafafa; }
.card-head h3 { font-size: 15px; font-weight: 700; }
.card-body { padding: 18px; }

/* REQUEST LIST */
.req-list { display: flex; flex-direction: column; gap: 12px; }

.req-card { background: var(--card); border-radius: var(--radius); border: 1px solid var(--border); border-left: 5px solid var(--orange); display: flex; align-items: stretch; box-shadow: var(--shadow-sm); transition: box-shadow 0.15s; }
.req-card:hover { box-shadow: var(--shadow-md); }
.req-card.is-hold { border-left-color: var(--blue); }
.req-card.is-done { border-left-color: #4caf50; opacity: 0.6; }
.req-card.drag-over { outline: 3px dashed var(--green-mid); outline-offset: -2px; background: var(--green-pale); }
.req-card.dragging  { opacity: 0.35; }
.req-card.new-flash { animation: newFlash 2s ease-in-out 3; }
@keyframes newFlash { 0%,100%{box-shadow:var(--shadow-sm);} 50%{box-shadow:0 0 0 4px rgba(239,83,80,.25),var(--shadow-md);} }

/* drag handle */
.req-drag { flex-shrink: 0; width: 34px; display: flex; align-items: center; justify-content: center; cursor: grab; color: #ccc; font-size: 22px; border-right: 1px solid #f0f0f0; user-select: none; transition: color 0.15s; }
.req-drag:hover { color: #999; }
.req-drag:active { cursor: grabbing; }
.is-done .req-drag { cursor: default; color: #ddd; }

/* poster */
.req-poster-wrap { flex-shrink: 0; width: 84px; background: #f5f5f5; overflow: hidden; display: flex; align-items: center; justify-content: center; }
.req-poster { width: 84px; height: 126px; object-fit: cover; display: block; }

/* body */
.req-body { flex: 1; padding: 14px 16px; display: flex; flex-direction: column; gap: 9px; min-width: 0; }
.req-top { display: flex; align-items: flex-start; gap: 9px; flex-wrap: wrap; }
.req-title { font-size: 16px; font-weight: 700; flex: 1; min-width: 0; line-height: 1.25; }

/* badges */
.badge { flex-shrink: 0; font-size: 12px; font-weight: 700; letter-spacing: 0.02em; padding: 4px 10px; border-radius: 5px; display: inline-flex; align-items: center; gap: 5px; }
.badge-dvd  { background: var(--green-dark); color: white; font-size: 14px; }
.badge-now  { background: var(--orange-light); color: var(--orange); border: 1px solid #ffcc80; font-size: 13px; }
.badge-hold { background: var(--blue-light); color: var(--blue); border: 1px solid #90caf9; font-size: 13px; }
.badge-done { background: var(--green-light); color: var(--green-dark); }

/* pills */
.req-pills { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.pill { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 500; }
.pill-call   { background: var(--green-light); color: var(--green-dark); font-weight: 600; }
.pill-patron { background: var(--blue-light); color: var(--blue); font-weight: 600; }
.pill-card   { background: var(--purple-light); color: var(--purple); font-family: 'DM Mono', monospace; font-size: 12px; }
.pill-name   { background: var(--gold-light); color: var(--gold); }

/* time */
.req-time { font-size: 12px; color: var(--muted); display: flex; gap: 12px; align-items: center; }
.req-time .ago { font-weight: 700; color: #555; }

/* notes */
.req-notes-row { display: flex; flex-direction: column; gap: 5px; }
.req-note-display { font-size: 13px; color: #555; background: var(--gold-light); border-left: 3px solid var(--gold); padding: 6px 10px; border-radius: 0 6px 6px 0; font-style: italic; display: none; }
.req-note-display.has-note { display: block; }
.req-note-input { font-size: 13px; padding: 7px 10px; border: 1px solid var(--border); border-radius: 6px; font-family: inherit; width: 100%; resize: none; display: none; background: #fafafa; }
.req-note-input.open { display: block; }
.req-note-actions { display: none; gap: 6px; }
.req-note-actions.open { display: flex; }

/* actions col */
.req-actions { flex-shrink: 0; display: flex; flex-direction: column; justify-content: center; gap: 7px; padding: 14px 14px; border-left: 1px solid #f0f0f0; min-width: 118px; }
.req-done-label { font-size: 13px; color: #4caf50; font-weight: 700; text-align: center; }

/* TABLES */
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #f0f0f0; }
.table th { font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 600; letter-spacing: 0.04em; background: #fafafa; }
.table tbody tr:hover { background: var(--green-pale); }
.table img { width: 38px; height: 56px; object-fit: cover; border-radius: 4px; }

/* PICKERS */
.picker { border: 1px solid var(--border); border-radius: 8px; max-height: 340px; overflow-y: auto; }
.picker-search { padding: 10px; border-bottom: 1px solid var(--border); position: sticky; top: 0; background: white; }
.picker-search input { width: 100%; padding: 8px 11px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px; font-family: inherit; }
.picker-list { padding: 5px; }
.picker-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 6px; cursor: pointer; }
.picker-item:hover { background: var(--green-pale); }
.picker-item img { width: 34px; height: 50px; object-fit: cover; border-radius: 3px; background: #eee; }
.picker-item .p-title { font-size: 13px; font-weight: 600; }
.picker-item .p-bc { font-size: 11px; color: var(--muted); }
.selected-list { display: flex; flex-wrap: wrap; gap: 8px; min-height: 36px; }
.selected-tag { display: flex; align-items: center; gap: 6px; background: var(--green-light); padding: 4px 9px 4px 4px; border-radius: 6px; font-size: 12px; font-weight: 500; }
.selected-tag img { width: 24px; height: 36px; object-fit: cover; border-radius: 2px; }
.selected-tag .rm { cursor: pointer; color: #999; font-size: 15px; line-height: 1; }
.selected-tag .rm:hover { color: var(--red); }

/* FORMS */
.form-group { margin-bottom: 15px; }
.form-label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 13px; }
.form-input, .form-select, .form-textarea { width: 100%; padding: 9px 12px; font-size: 13px; border: 1px solid var(--border); border-radius: 6px; font-family: inherit; transition: border-color 0.15s; }
.form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--green-mid); outline: none; }
.form-hint { font-size: 11px; color: var(--muted); margin-top: 4px; }
.toggle-row { display: flex; justify-content: space-between; align-items: center; padding: 11px 0; border-bottom: 1px solid #f5f5f5; }
.toggle-row:last-child { border-bottom: none; }
.toggle-label { font-size: 13px; font-weight: 500; }
.toggle-desc  { font-size: 11px; color: var(--muted); margin-top: 1px; }
.toggle { position: relative; width: 42px; height: 24px; }
.toggle input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; inset: 0; background: #ccc; border-radius: 24px; transition: 0.2s; }
.toggle-slider:before { content:""; position:absolute; width:18px; height:18px; left:3px; bottom:3px; background:white; border-radius:50%; transition:0.2s; box-shadow:0 1px 3px rgba(0,0,0,.2); }
.toggle input:checked + .toggle-slider { background: #4caf50; }
.toggle input:checked + .toggle-slider:before { transform: translateX(18px); }

/* IMAGE UPLOAD */
.img-upload { border: 2px dashed var(--border); border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s; }
.img-upload:hover { border-color: var(--green-mid); background: var(--green-pale); }
.img-upload img { max-width: 110px; max-height: 165px; border-radius: 4px; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto; }
.img-upload input { display: none; }

/* MODAL */
.modal-bg { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; display:flex; align-items:center; justify-content:center; padding:20px; opacity:0; visibility:hidden; transition:all .2s; }
.modal-bg.visible { opacity:1; visibility:visible; }
.modal { background:white; border-radius:12px; width:100%; max-width:540px; max-height:90vh; overflow-y:auto; }
.modal-head { padding:16px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
.modal-head h3 { font-size:16px; font-weight:700; }
.modal-head .close { background:none; border:none; font-size:22px; cursor:pointer; color:var(--muted); line-height:1; }
.modal-body { padding:20px; }
.modal-foot { padding:14px 20px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:8px; }

/* TOAST */
.toast { position:fixed; bottom:22px; right:22px; background:#222; color:white; padding:12px 20px; border-radius:8px; font-size:13px; font-weight:600; z-index:2000; transform:translateY(80px); opacity:0; transition:all .3s; box-shadow:var(--shadow-lg); }
.toast.visible { transform:translateY(0); opacity:1; }
.toast.success { background: var(--green-dark); }
.toast.error   { background: var(--red); }

/* EMPTY */
.empty { text-align:center; padding:52px 20px; color:var(--muted); }
.empty-icon { font-size:48px; margin-bottom:12px; opacity:.5; }
.empty-title { font-size:16px; font-weight:600; color:var(--text); margin-bottom:6px; }

#cacheProgress { display:none; margin-top:14px; }
</style>
</head>
<body>

<nav class="sidebar">
  <div class="sidebar-logo">
    <h1>📚 Paxton Carnegie</h1>
    <p>Staff Dashboard</p>
  </div>
  <div class="nav-section">Requests</div>
  <button class="nav-item active" data-page="requests">
    <span class="nav-icon">📋</span> Movie Requests
    <span class="nav-badge" id="reqBadge" style="display:none">0</span>
  </button>
  <div class="nav-section">Content</div>
  <button class="nav-item" data-page="featured"><span class="nav-icon">⭐</span> Staff Picks</button>
  <button class="nav-item" data-page="arrivals"><span class="nav-icon">🆕</span> New Arrivals</button>
  <button class="nav-item" data-page="movies"><span class="nav-icon">🎬</span> All Movies</button>
  <div class="nav-section">System</div>
  <button class="nav-item" data-page="settings"><span class="nav-icon">⚙️</span> Settings</button>
  <button class="nav-item" data-page="cache"><span class="nav-icon">🔄</span> Cache / Sync</button>
</nav>

<div class="main">
  <header class="topbar">
    <h2 id="pageTitle">Movie Requests</h2>
    <div class="topbar-right">
      <button class="btn btn-secondary" id="btnRefresh">🔄 Refresh</button>
    </div>
  </header>

  <div class="content">

    <!-- REQUESTS -->
    <div class="page active" id="pageRequests">
      <div class="stats">
        <div class="stat"><div class="stat-val" id="statPending">0</div><div class="stat-lbl">Pending Get Now</div></div>
        <div class="stat"><div class="stat-val" id="statHolds">0</div><div class="stat-lbl">Pending Holds</div></div>
        <div class="stat"><div class="stat-val" id="statToday">0</div><div class="stat-lbl">Today</div></div>
        <div class="stat"><div class="stat-val" id="statTotal">0</div><div class="stat-lbl">All Time</div></div>
      </div>
      <div class="card">
        <div class="card-head">
          <h3>Request Queue <span style="font-size:12px;font-weight:400;color:var(--muted);margin-left:6px;">drag ⠿ to reorder</span></h3>
          <div style="display:flex;gap:10px;align-items:center;">
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;user-select:none;">
              <input type="checkbox" id="chkShowDone"> Show completed
            </label>
            <button class="btn btn-sm btn-ghost" id="btnClearDone">🗑️ Clear completed</button>
          </div>
        </div>
        <div class="card-body">
          <div class="req-list" id="reqList">
            <div class="empty"><div class="empty-icon">📭</div><div class="empty-title">No requests yet</div></div>
          </div>
        </div>
      </div>
    </div>

    <!-- STAFF PICKS -->
    <div class="page" id="pageFeatured">
      <div class="card">
        <div class="card-head"><h3>⭐ Staff Picks</h3><button class="btn btn-primary" id="btnSaveFeatured">💾 Save</button></div>
        <div class="card-body">
          <p style="margin-bottom:12px;font-size:13px;color:var(--muted);">Select movies to feature on the kiosk "Staff Picks" row.</p>
          <div class="selected-list" id="featuredTags"></div>
          <div class="picker" style="margin-top:15px;">
            <div class="picker-search"><input type="text" placeholder="Search movies…" id="featuredSearch"></div>
            <div class="picker-list" id="featuredList"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- NEW ARRIVALS -->
    <div class="page" id="pageArrivals">
      <div class="card">
        <div class="card-head"><h3>🆕 New Arrivals</h3><button class="btn btn-primary" id="btnSaveArrivals">💾 Save</button></div>
        <div class="card-body">
          <p style="margin-bottom:12px;font-size:13px;color:var(--muted);">Select movies for the "New Arrivals" row on the kiosk.</p>
          <div class="selected-list" id="arrivalsTags"></div>
          <div class="picker" style="margin-top:15px;">
            <div class="picker-search"><input type="text" placeholder="Search movies…" id="arrivalsSearch"></div>
            <div class="picker-list" id="arrivalsList"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ALL MOVIES -->
    <div class="page" id="pageMovies">
      <div class="card">
        <div class="card-head">
          <h3>🎬 Movie Inventory</h3>
          <div style="display:flex;gap:10px;align-items:center;">
            <input type="text" placeholder="Search…" id="movieSearch" style="padding:7px 11px;border:1px solid var(--border);border-radius:6px;width:200px;font-family:inherit;font-size:13px;">
            <span id="movieCount" style="font-size:12px;color:var(--muted);">0 movies</span>
          </div>
        </div>
        <div class="card-body" style="padding:0;max-height:520px;overflow-y:auto;">
          <table class="table">
            <thead><tr><th>Cover</th><th>#</th><th>Title</th><th>Barcode</th><th>Rating</th><th>Call #</th><th></th></tr></thead>
            <tbody id="movieBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- SETTINGS -->
    <div class="page" id="pageSettings">
      <div class="card">
        <div class="card-head"><h3>⚙️ Kiosk Settings</h3><button class="btn btn-primary" id="btnSaveSettings">💾 Save Settings</button></div>
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

    <!-- CACHE -->
    <div class="page" id="pageCache">
      <div class="card">
        <div class="card-head"><h3>🔄 Cache Management</h3></div>
        <div class="card-body">
          <p style="margin-bottom:15px;font-size:13px;color:var(--muted);">Movie data is cached locally. Availability is fetched fresh when a patron clicks a movie.</p>
          <div class="form-group">
            <button class="btn btn-primary" id="btnRebuildCache">🔄 Rebuild Entire Cache</button>
            <div class="form-hint">Fetches fresh data from Polaris for all movies. May take several minutes.</div>
          </div>
          <div id="cacheProgress">
            <div style="height:8px;background:#eee;border-radius:4px;overflow:hidden;"><div id="cacheProgressBar" style="height:100%;background:#4caf50;width:0%;transition:width .3s;"></div></div>
            <div id="cacheStatus" style="font-size:12px;color:var(--muted);margin-top:5px;"></div>
          </div>
          <hr style="margin:20px 0;border:none;border-top:1px solid var(--border);">
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
        <div class="card-head"><h3>🗂️ Data Overrides</h3></div>
        <div class="card-body">
          <p style="margin-bottom:15px;font-size:13px;color:var(--muted);">Movie edits are saved as overrides and take priority over API data.</p>
          <button class="btn btn-danger" id="btnClearOverrides">🗑️ Clear All Overrides</button>
          <div class="form-hint">Resets all custom titles, covers, etc. back to API data.</div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Edit Movie Modal -->
<div class="modal-bg" id="editModal">
  <div class="modal">
    <div class="modal-head"><h3>Edit Movie</h3><button class="close" id="btnCloseEdit">&times;</button></div>
    <div class="modal-body">
      <input type="hidden" id="editBarcode">
      <div class="form-group">
        <label class="form-label">Cover Image</label>
        <div class="img-upload" id="imgUpload">
          <img id="editCoverPreview" src="/img/no-cover.svg" alt="Cover">
          <div style="font-size:13px;color:var(--muted);">Click to upload new image</div>
          <input type="file" id="editCoverFile" accept="image/*">
        </div>
      </div>
      <div class="form-group"><label class="form-label">Title</label><input type="text" class="form-input" id="editTitle"></div>
      <div class="form-group">
        <label class="form-label">Rating</label>
        <select class="form-select" id="editRating">
          <option value="">—</option><option value="G">G</option><option value="PG">PG</option>
          <option value="PG-13">PG-13</option><option value="R">R</option>
          <option value="NC-17">NC-17</option><option value="NR">NR</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Call Number</label><input type="text" class="form-input" id="editCallNumber"></div>
      <div class="form-group"><label class="form-label">Location</label><input type="text" class="form-input" id="editLocation" placeholder="DVD Section"></div>
      <div class="form-group"><label class="form-label">Description</label><textarea class="form-textarea" id="editDescription" rows="3"></textarea></div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" id="btnCancelEdit">Cancel</button>
      <button class="btn btn-primary" id="btnSaveEdit">💾 Save Changes</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>
<audio id="notifySound" preload="auto">
  <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdH2OnqeXjHlwaHB8i5aZlol5bGVufI2dn5eKd2pkcH2OoJ+Wi3VoYW1+kqKgloZwZGFsf5Wkn5KAa2JgbIGYpp2NdmVgaH2Uo52TgW1iYGqCmaaej3RiX2V6kaCdlIJuYV5ofJSinpKBbGFeaH2WpaCQfGphXWd7k6GdlYRvY11meZOgnJWFcWVdZXiRn5yWhXNmXmR2j52ak4VzaF5jdY2bmJKGdWpeYnOLmZeRh3ZsX2Fxipear4t1Yl1gcIibm5SJeW1hX26Gk5ORiHltYV9uhpOSkYh6bWFfbYWSkZGIe21hX22EkZCQiHxtYl9thJGQkIh8bWFfbISQj4+IfG1iYGyDj46OiH1tYmBrgo6Njoh9bWJga4KOjY6IfW1iYGuCjo2NiH1tYmBrgo6NjYl9bWJga4GOjI2JfW1iYWuBjYyMiX1tYmFrgI2MjIl+bWJha4CNjIyJfm1iYWqAjIuLin5tYmJqgIyLi4p+bWJiaoSQj4+LgG9kY2yGkpGRjIJwZWRtiJSTkoyDcWZlbo2ZmJWPhnRoZnCRnJuYkolyamd0laCdmpSMdWtpe5mjoJuWj3ltbH2cpaKdmJF7b26BoKeinpl+cXCEoqagm5l/c3GGo6ehn5qAc3KHpKiioJuBdHKIpKiioZyBdXOIpKiioZyBdXOIpKiioZyBdXOIpKiioZyCdXOIpKiioZyCdXSIpKiioZyCdXSIo6ehoJuBdHSHo6ehoJuBdHOHoqagoJuBc3OGoqagn5qAc3KGoaWfnpmAcnGFoaWfnpmAcXCFoKSenpiBcXCEn6SdnZiBcG+Dn6OdnZiBcG+CnqKcnJeBb2+CnqKcnJeBb2+CnaGbm5aBbm6BnaGbm5aBbm6BnKCam5aBbm2AnKCam5WBbW2Am5+ZmpWAbW1/m5+ZmpWAbWx/mp6YmZSAbGx+mp6YmZSAbGx+mZ2XmJOAbGt9mZ2XmJOAbGt9mJyWl5KAamp8mJyWl5KAamp8l5uVlpGAamp7l5uVlpF/aml7lpqUlZB/aWl6lpqUlZB/aWh5lZmTlI9/aGh5lZmTlI9/aGh4lJiSk45/Z2d3lJiSk45/Z2d3k5eRko5+Zmd2k5eRko5+ZmZ2kpaQkY1+ZmZ1kpaQkY1+ZWV0kZWPkIx9ZWV0kZWPkIx9ZGRzkJSOj4t9ZGRzkJSOj4t9Y2NykJSOj4t9Y2NxkJONjop8Y2NxkJONjop8YmJwj5KMjYl8YmJwj5KMjYl8YWFvjpGLjIl7YWFvjpGLjIl7YWFujZCKi4h7YGBujZCKi4h7YGBtjY+JioZ6X19tjY+JioZ6X19sjI6IiYZ6X15sjI6IiYZ6X15ri42Hh4V5Xl1ri42Hh4V5XVxqi4yGhoR4XVxqioqFhYN4XFtpiomEhIN3W1tpiYiDg4J3W1poiIeCgoF2Wlpoh4aBgYB2WlpnhoWAgH91WVlnhoWAgH91WFhmhYR/f371WFhmhIN+fn10V1dlhIN+fn10V1dlg4J9fXxzVlZkg4J9fXxzVlVkgoF8fHtyVVVjgYB7e3pyVVRjgH97e3lyVFRif396enlyU1Nif395eXhxU1JhfX55eHdwUlJhfX14eHdwUVFge3x3d3ZvUFFge3x3d3ZvT1Bfen12dnVuT09fen12dnVuT05eeXt1dXRtTk5eeXt1dXRtTU1dd3pzdHNsTE1dd3pzdHNsTExcdnlycnJrS0xcdnlycnJrS0tbdXhxcXFqSkpbdXhxcXFqSUladHdwcHBpSUladHdwcG9pSEhZc3ZvbnBoSEhZc3ZvbnBoRkdYcnVubW9nRkdYcnVubW9nRUZXcXRtbG1mRUZXcXRtbG1mRERWcHNsbGxlRERWcHNsbGxlQ0NVb3JramtkQ0NVb3JramtkQkJUbnFqaWljQkJUbnFqaWljQUFTbXBpaGhiQUFTbXBpaGhiQEBSbG9oZ2dhQEBSbG9oZ2dhPz9Ra25nZmZgPz9Ra25nZmZgPj5QamxmZWVfPj5QamxmZWVfPT1PaWtlZGRePT1PaWtlZGRePTxOaGpkY2NdPDxOaGpkY2NdOzs=" type="audio/wav">
</audio>

<script>
const NO_COVER = '/img/no-cover.svg';
const $ = s => document.querySelector(s);
const $$ = s => [...document.querySelectorAll(s)];

let movies = [], movieMap = {}, requests = [];
let featuredBarcodes = <?php echo json_encode($settings['featured'] ?? []); ?>;
let arrivalsBarcodes  = <?php echo json_encode($settings['newArrivals'] ?? []); ?>;
let autoRefreshTimer  = null;
let lastPendingCount  = 0;
let showDone          = false;
let localNotes        = {}; // id => note text, persists across re-renders

// ── INIT ──────────────────────────────────────────────────────────────────────
async function init() {
  await loadMovies();
  await loadRequests();
  renderPickers();
  renderMovieTable();
  setupEvents();
  startAutoRefresh();
}

// ── DATA ──────────────────────────────────────────────────────────────────────
async function loadMovies() {
  try {
    const d = await fetch('../api/movies.php').then(r => r.json());
    if (d.ok) {
      movies = d.items || [];
      movieMap = Object.fromEntries(movies.map(m => [m.barcode, m]));
      $('#movieCount').textContent = `${movies.length} movies`;
    }
  } catch(e) { console.error(e); }
}

async function loadRequests() {
  try {
    const d = await fetch('../api/requests.php').then(r => r.json());
    if (!d.ok) return;
    const newPending = (d.stats?.pendingNow || 0) + (d.stats?.pendingHolds || 0);
    if (newPending > lastPendingCount && requests.length > 0 && $('#setSound')?.checked !== false) {
      $('#notifySound').play().catch(()=>{});
    }
    lastPendingCount = newPending;
    // Seed localNotes from server on first load
    (d.requests || []).forEach(r => {
      if (r.notes && localNotes[r.id] === undefined) localNotes[r.id] = r.notes;
    });
    requests = d.requests || [];
    renderRequests();
    updateStats(d.stats);
  } catch(e) { console.error(e); }
}

// ── TIME ──────────────────────────────────────────────────────────────────────
function timeAgo(iso) {
  const s = Math.floor((Date.now() - new Date(iso)) / 1000);
  if (s < 60)    return `${s}s ago`;
  if (s < 3600)  return `${Math.floor(s/60)}m ago`;
  if (s < 86400) return `${Math.floor(s/3600)}h ago`;
  return `${Math.floor(s/86400)}d ago`;
}
function fmtTime(iso) { return new Date(iso).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}); }

// ── RENDER REQUESTS ───────────────────────────────────────────────────────────
function renderRequests() {
  const list    = $('#reqList');
  const visible = showDone ? requests : requests.filter(r => !r.completed);

  if (!visible.length) {
    list.innerHTML = `<div class="empty">
      <div class="empty-icon">${showDone ? '📭' : '✨'}</div>
      <div class="empty-title">${showDone ? 'No requests' : 'All caught up!'}</div>
    </div>`;
    return;
  }

  list.innerHTML = visible.map(r => {
    const isNew    = !r.completed && (Date.now() - new Date(r.timestamp)) < 60000;
    const isHold   = (r.type || 'now') === 'hold';
    const dvdId    = r.movie?.dvdId;
    const call     = r.movie?.callNumber;
    const pBarcode = r.patron?.barcode;
    const pName    = r.patron?.name || 'Guest';
    const note     = localNotes[r.id] ?? r.notes ?? '';

    return `
<div class="req-card ${r.completed?'is-done':''} ${isHold?'is-hold':''} ${isNew?'new-flash':''}"
     data-id="${r.id}" draggable="${!r.completed}">

  <div class="req-drag" title="Drag to reorder">⠿</div>

  <div class="req-poster-wrap">
    <img class="req-poster" src="${esc(r.movie?.cover||NO_COVER)}" onerror="this.src='${NO_COVER}'" alt="">
  </div>

  <div class="req-body">
    <div class="req-top">
      <div class="req-title">${esc(r.movie?.title||'Unknown')}</div>
      ${dvdId ? `<span class="badge badge-dvd">#${esc(String(dvdId))}</span>` : ''}
      <span class="badge ${isHold?'badge-hold':'badge-now'}">${isHold?'📌 Hold':'🎬 Get Now'}</span>
      ${r.completed ? `<span class="badge badge-done">✅ Done</span>` : ''}
    </div>

    <div class="req-pills">
      ${call     ? `<span class="pill pill-call">📍 ${esc(call)}</span>` : ''}
      <span class="pill pill-patron">👤 ${esc(pName)}</span>
      ${pBarcode ? `<span class="pill pill-card">🪪 ${esc(pBarcode)}</span>`
                 : `<span class="pill pill-name">✍️ Name sign-in</span>`}
    </div>

    <div class="req-time">
      <span class="ago">${timeAgo(r.timestamp)}</span>
      <span>at ${fmtTime(r.timestamp)}</span>
      ${r.completed && r.completedAt ? `<span>· completed ${timeAgo(r.completedAt)}</span>` : ''}
    </div>

    <div class="req-notes-row">
      <div class="req-note-display ${note?'has-note':''}" id="noteDisplay_${r.id}">${note ? '📝 '+esc(note) : ''}</div>
      <textarea class="req-note-input" id="noteInput_${r.id}" rows="2"
        placeholder="Add a staff note… (e.g. 'on hold shelf', 'patron called')">${esc(note)}</textarea>
      <div class="req-note-actions" id="noteActions_${r.id}">
        <button class="btn btn-xs btn-primary" onclick="saveNote('${r.id}')">💾 Save note</button>
        <button class="btn btn-xs btn-ghost"   onclick="cancelNote('${r.id}')">Cancel</button>
      </div>
    </div>
  </div>

  <div class="req-actions">
    ${!r.completed
      ? `<button class="btn btn-sm btn-primary" onclick="completeReq('${r.id}')">✅ Done</button>`
      : `<div class="req-done-label">✅ Completed</div>`}
    <button class="btn btn-sm btn-secondary" onclick="toggleNote('${r.id}')">📝 Note</button>
    <button class="btn btn-sm btn-ghost"     onclick="deleteReq('${r.id}')">✕ Remove</button>
  </div>
</div>`;
  }).join('');

  attachDrag();
}

// ── NOTES ─────────────────────────────────────────────────────────────────────
function toggleNote(id) {
  const input   = $(`#noteInput_${id}`);
  const actions = $(`#noteActions_${id}`);
  const display = $(`#noteDisplay_${id}`);
  const open    = input.classList.toggle('open');
  actions.classList.toggle('open', open);
  if (open) { display.style.display = 'none'; input.focus(); }
  else { display.style.display = display.classList.contains('has-note') ? 'block' : 'none'; }
}

async function saveNote(id) {
  const input = $(`#noteInput_${id}`);
  const note  = (input?.value || '').trim();
  localNotes[id] = note;
  try {
    await fetch('../api/requests.php', {
      method: 'PUT', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ id, notes: note })
    });
  } catch(e) {}
  const display = $(`#noteDisplay_${id}`);
  if (display) {
    display.innerHTML = note ? `📝 ${esc(note)}` : '';
    display.classList.toggle('has-note', !!note);
    display.style.display = note ? 'block' : 'none';
  }
  input?.classList.remove('open');
  $(`#noteActions_${id}`)?.classList.remove('open');
  toast('Note saved', 'success');
}

function cancelNote(id) {
  const input   = $(`#noteInput_${id}`);
  const actions = $(`#noteActions_${id}`);
  const display = $(`#noteDisplay_${id}`);
  if (input)   { input.value = localNotes[id] || ''; input.classList.remove('open'); }
  if (actions) actions.classList.remove('open');
  if (display) display.style.display = display.classList.contains('has-note') ? 'block' : 'none';
}

// ── DRAG TO REORDER ───────────────────────────────────────────────────────────
let dragSrcId = null;

function attachDrag() {
  $$('.req-card[draggable="true"]').forEach(card => {
    card.addEventListener('dragstart', e => {
      dragSrcId = card.dataset.id;
      card.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
    });
    card.addEventListener('dragend', () => {
      card.classList.remove('dragging');
      $$('.req-card').forEach(c => c.classList.remove('drag-over'));
    });
    card.addEventListener('dragover', e => {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      $$('.req-card').forEach(c => c.classList.remove('drag-over'));
      card.classList.add('drag-over');
    });
    card.addEventListener('drop', e => {
      e.preventDefault();
      card.classList.remove('drag-over');
      if (dragSrcId && dragSrcId !== card.dataset.id) {
        reorder(dragSrcId, card.dataset.id);
      }
    });
  });
}

async function reorder(srcId, destId) {
  const si = requests.findIndex(r => r.id === srcId);
  const di = requests.findIndex(r => r.id === destId);
  if (si === -1 || di === -1) return;
  const [moved] = requests.splice(si, 1);
  requests.splice(di, 0, moved);
  renderRequests();
  try {
    await fetch('../api/requests.php', {
      method: 'PUT', headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ reorder: requests.map(r => r.id) })
    });
  } catch(e) {}
}

// ── STATS ──────────────────────────────────────────────────────────────────────
function updateStats(stats) {
  $('#statPending').textContent = stats?.pendingNow   || 0;
  $('#statHolds').textContent   = stats?.pendingHolds || 0;
  $('#statToday').textContent   = stats?.today        || 0;
  $('#statTotal').textContent   = stats?.total        || 0;
  const total = (stats?.pendingNow || 0) + (stats?.pendingHolds || 0);
  const badge = $('#reqBadge');
  badge.textContent   = total;
  badge.style.display = total > 0 ? 'inline' : 'none';
  document.title = total > 0 ? `(${total}) Staff Dashboard` : 'Staff Dashboard';
}

// ── PICKERS ────────────────────────────────────────────────────────────────────
function renderPickers() {
  renderPicker('featured', featuredBarcodes, '#featuredTags', '#featuredList', '#featuredSearch');
  renderPicker('arrivals',  arrivalsBarcodes,  '#arrivalsTags',  '#arrivalsList',  '#arrivalsSearch');
}
function renderPicker(type, list, tagsEl, listEl, searchEl) {
  $(tagsEl).innerHTML = list.map(bc => {
    const m = movieMap[bc]; if (!m) return '';
    return `<div class="selected-tag"><img src="${m.cover||NO_COVER}" onerror="this.src='${NO_COVER}'"><span>${esc(m.title)}</span><span class="rm" onclick="removeFrom('${type}','${bc}')">&times;</span></div>`;
  }).join('') || '<span style="color:var(--muted);font-size:12px;">None selected</span>';
  const q = ($(searchEl)?.value||'').toLowerCase();
  $(listEl).innerHTML = movies.filter(m => !list.includes(m.barcode) && (!q||(m.title||'').toLowerCase().includes(q))).slice(0,50).map(m =>
    `<div class="picker-item" onclick="addTo('${type}','${m.barcode}')">
      <img src="${m.cover||NO_COVER}" onerror="this.src='${NO_COVER}'">
      <div><div class="p-title">${esc(m.title)}</div><div class="p-bc">${m.barcode}</div></div>
    </div>`).join('');
}
function renderMovieTable() {
  const q = ($('#movieSearch')?.value||'').toLowerCase();
  $('#movieBody').innerHTML = movies.filter(m => !q||(m.title||'').toLowerCase().includes(q)||m.barcode.includes(q)).map(m =>
    `<tr>
      <td><img src="${m.cover||NO_COVER}" onerror="this.src='${NO_COVER}'"></td>
      <td style="font-weight:700;color:var(--green-dark);">${esc(m.dvdId||'—')}</td>
      <td><strong>${esc(m.title)}</strong></td>
      <td style="font-family:'DM Mono',monospace;font-size:12px;">${m.barcode}</td>
      <td>${m.rating||'—'}</td><td>${m.callNumber||'—'}</td>
      <td><button class="btn btn-xs btn-secondary" onclick="editMovie('${m.barcode}')">✏️ Edit</button></td>
    </tr>`).join('');
}

// ── ACTIONS ────────────────────────────────────────────────────────────────────
async function completeReq(id) {
  await fetch('../api/requests.php', { method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id, completed:true}) });
  toast('Marked complete ✅','success'); loadRequests();
}
async function deleteReq(id) {
  if (!confirm('Remove this request?')) return;
  await fetch(`../api/requests.php?id=${id}`, {method:'DELETE'});
  toast('Request removed'); loadRequests();
}
function addTo(type, bc) {
  if (type==='featured' && !featuredBarcodes.includes(bc)) featuredBarcodes.push(bc);
  if (type==='arrivals'  && !arrivalsBarcodes.includes(bc))  arrivalsBarcodes.push(bc);
  renderPickers();
}
function removeFrom(type, bc) {
  if (type==='featured') featuredBarcodes = featuredBarcodes.filter(x=>x!==bc);
  if (type==='arrivals')  arrivalsBarcodes  = arrivalsBarcodes.filter(x=>x!==bc);
  renderPickers();
}
async function savePicker(type) {
  const list = type==='featured' ? featuredBarcodes : arrivalsBarcodes;
  const key  = type==='featured' ? 'featured' : 'newArrivals';
  await fetch('../api/settings.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({[key]:list})});
  toast('Saved!','success');
}
async function saveSettings() {
  await fetch('../api/settings.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
    libraryName:$('#setLibraryName').value, timeout:parseInt($('#setTimeout').value)||60,
    warning:parseInt($('#setWarning').value)||15, showFeatured:$('#setShowFeatured').checked,
    showArrivals:$('#setShowArrivals').checked, sound:$('#setSound').checked, autoRefresh:$('#setAutoRefresh').checked
  })});
  toast('Settings saved!','success');
}
function editMovie(bc) {
  const m = movieMap[bc]||{barcode:bc};
  $('#editBarcode').value=$editTitle_val=bc; $('#editTitle').value=m.title||''; $('#editRating').value=m.rating||'';
  $('#editCallNumber').value=m.callNumber||''; $('#editLocation').value=m.location||''; $('#editDescription').value=m.description||'';
  $('#editCoverPreview').src=m.cover||NO_COVER; $('#editBarcode').value=bc;
  $('#editModal').classList.add('visible');
}
async function saveMovie() {
  const bc=$('#editBarcode').value;
  await fetch('../api/movies.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
    barcode:bc,title:$('#editTitle').value,rating:$('#editRating').value,
    callNumber:$('#editCallNumber').value,location:$('#editLocation').value,description:$('#editDescription').value
  })});
  toast('Movie updated!','success'); $('#editModal').classList.remove('visible');
  await loadMovies(); renderMovieTable(); renderPickers();
}
async function uploadCover(file) {
  const bc=$('#editBarcode').value; if(!bc) return;
  const fd=new FormData(); fd.append('image',file); fd.append('barcode',bc);
  const d=await fetch('../api/upload.php',{method:'POST',body:fd}).then(r=>r.json());
  if(d.ok){$('#editCoverPreview').src=d.url;toast('Image uploaded!','success');}else toast('Upload failed: '+d.error,'error');
}
async function rebuildCache() {
  if(!confirm('This may take several minutes. Continue?')) return;
  $('#cacheProgress').style.display='block'; $('#cacheProgressBar').style.width='0%'; $('#cacheStatus').textContent='Starting…';
  try {
    const d=await fetch('../api/movies.php?action=rebuild',{method:'PUT'}).then(r=>r.json());
    if(d.ok){$('#cacheProgressBar').style.width='100%';$('#cacheStatus').textContent=`Done! Processed ${d.processed} movies.`;toast('Cache rebuilt!','success');await loadMovies();renderMovieTable();}
    else{$('#cacheStatus').textContent='Error: '+(d.error||'Unknown');toast('Failed','error');}
  }catch(e){$('#cacheStatus').textContent='Error: '+e.message;toast('Failed','error');}
}
async function refreshSingle() {
  const bc=$('#refreshBarcode').value.trim(); if(!bc){toast('Enter a barcode','error');return;}
  const d=await fetch(`../api/movies.php?action=refresh&barcode=${encodeURIComponent(bc)}`,{method:'PUT'}).then(r=>r.json());
  if(d.ok){toast('Refreshed!','success');await loadMovies();renderMovieTable();}else toast('Failed: '+(d.error||'Unknown'),'error');
}

// ── EVENTS ─────────────────────────────────────────────────────────────────────
function setupEvents() {
  $$('.nav-item').forEach(n => n.onclick = () => {
    $$('.nav-item').forEach(x=>x.classList.remove('active')); n.classList.add('active');
    $$('.page').forEach(p=>p.classList.remove('active'));
    document.getElementById('page'+n.dataset.page.charAt(0).toUpperCase()+n.dataset.page.slice(1))?.classList.add('active');
    $('#pageTitle').textContent = n.textContent.replace(/\d/g,'').trim();
  });
  $('#btnRefresh').onclick   = loadRequests;
  $('#chkShowDone').onchange = e => { showDone=e.target.checked; renderRequests(); };
  $('#btnClearDone').onclick = async () => {
    if(!confirm('Remove all completed requests?')) return;
    await fetch('../api/requests.php?clearCompleted=true',{method:'DELETE'}); toast('Cleared'); loadRequests();
  };
  $('#featuredSearch').oninput = () => renderPicker('featured',featuredBarcodes,'#featuredTags','#featuredList','#featuredSearch');
  $('#arrivalsSearch').oninput  = () => renderPicker('arrivals',arrivalsBarcodes,'#arrivalsTags','#arrivalsList','#arrivalsSearch');
  $('#btnSaveFeatured').onclick  = () => savePicker('featured');
  $('#btnSaveArrivals').onclick  = () => savePicker('arrivals');
  $('#movieSearch').oninput     = renderMovieTable;
  $('#btnSaveSettings').onclick  = saveSettings;
  $('#btnRebuildCache').onclick  = rebuildCache;
  $('#btnRefreshSingle').onclick = refreshSingle;
  $('#btnClearOverrides').onclick = async () => {
    if(!confirm('Clear ALL custom movie edits?')) return;
    await fetch('../api/settings.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({_clearOverrides:true})});
    toast('Overrides cleared','success'); await loadMovies(); renderMovieTable();
  };
  $('#btnCloseEdit').onclick  = () => $('#editModal').classList.remove('visible');
  $('#btnCancelEdit').onclick = () => $('#editModal').classList.remove('visible');
  $('#btnSaveEdit').onclick   = saveMovie;
  $('#editModal').onclick = e => { if(e.target===$('#editModal')) $('#editModal').classList.remove('visible'); };
  $('#imgUpload').onclick     = () => $('#editCoverFile').click();
  $('#editCoverFile').onchange = e => { if(e.target.files[0]) uploadCover(e.target.files[0]); };
}

function startAutoRefresh() {
  if(autoRefreshTimer) clearInterval(autoRefreshTimer);
  autoRefreshTimer = setInterval(() => { if($('#setAutoRefresh')?.checked!==false) loadRequests(); }, 10000);
}
function toast(msg,type='') {
  const t=$('#toast'); t.textContent=msg; t.className='toast visible '+type;
  setTimeout(()=>t.classList.remove('visible'),3000);
}
function esc(s) { const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

let $editTitle_val = ''; // temp var to avoid scoping issue
init();
</script>
</body>
</html>
