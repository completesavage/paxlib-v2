<?php
require_once __DIR__ . '/config.php';

// Load settings
$settingsFile = __DIR__ . '/data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
$libraryName = $settings['libraryName'] ?? 'Paxton Carnegie Library';
$timeout = $settings['timeout'] ?? 60;
$warning = $settings['warning'] ?? 15;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title><?php echo htmlspecialchars($libraryName); ?> — DVD Collection</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; overflow: hidden; }
body {
  font-family: 'Inter', -apple-system, sans-serif;
  background: #f8f9fa;
  color: #333;
  -webkit-tap-highlight-color: transparent;
  user-select: none;
}

/* Header */
.header {
  background: linear-gradient(135deg, #1b5e20 0%, #388e3c 100%);
  color: white;
  padding: 18px 25px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.logo { font-size: 24px; font-weight: 700; }
.logo-sub { font-size: 14px; opacity: 0.9; }
.user-area { display: flex; align-items: center; gap: 12px; }
.user-info { text-align: right; display: none; }
.user-info.visible { display: block; }
.user-name { font-weight: 600; font-size: 16px; }
.user-card { font-size: 12px; opacity: 0.9; }
.btn {
  padding: 10px 20px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.15s;
}
.btn:active { transform: scale(0.97); }
.btn-white { background: white; color: #1b5e20; }
.btn-white:hover { background: #e8f5e9; }
.btn-outline { background: transparent; color: white; border: 2px solid rgba(255,255,255,0.8); }
.btn-outline:hover { background: rgba(255,255,255,0.1); }

/* Navigation */
.nav {
  background: white;
  border-bottom: 1px solid #e0e0e0;
  display: flex;
  padding: 0 20px;
}
.nav-btn {
  padding: 14px 24px;
  font-size: 15px;
  font-weight: 600;
  color: #666;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
}
.nav-btn:hover { color: #2e7d32; background: #f5f5f5; }
.nav-btn.active { color: #1b5e20; border-bottom-color: #1b5e20; }

/* Filter Bar */
.filter-bar {
  background: white;
  border-bottom: 2px solid #e0e0e0;
  padding: 15px 20px;
  display: flex;
  gap: 15px;
  align-items: center;
}
.filter-btn {
  flex: 1;
  max-width: 350px;
  padding: 18px 24px;
  font-size: 18px;
  font-weight: 700;
  border-radius: 10px;
  border: 3px solid #ddd;
  background: white;
  color: #666;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}
.filter-btn:hover {
  background: #f5f5f5;
  border-color: #bbb;
}
.filter-btn.active {
  background: #2e7d32;
  color: white;
  border-color: #2e7d32;
}
.filter-btn .count {
  background: rgba(0,0,0,0.15);
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 16px;
  font-weight: 700;
}
.filter-btn.active .count {
  background: rgba(255,255,255,0.25);
}

/* Unavailable movie styling */
.movie-card.unavailable {
  opacity: 0.6;
}
.movie-card.unavailable .movie-poster {
  filter: grayscale(100%);
}
.movie-card.unavailable:hover {
  opacity: 0.8;
}

/* Search bar */
.search-bar {
  background: white;
  padding: 12px 20px;
  border-bottom: 1px solid #e0e0e0;
  display: none;
}
.search-bar.visible { display: block; }
.search-input {
  width: 100%;
  max-width: 500px;
  padding: 12px 16px;
  font-size: 16px;
  border: 2px solid #ddd;
  border-radius: 8px;
  outline: none;
}
.search-input:focus { border-color: #2e7d32; }

/* On-screen keyboard */
#oskContainer {
  display: none;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #f1f8f1;
  padding: 14px 12px 18px;
  z-index: 9999;
  box-shadow: 0 -4px 16px rgba(0,0,0,0.15);
  border-top: 4px solid #2e7d32;
}
#oskContainer.visible { display: block; }
.osk-row {
  display: flex;
  justify-content: center;
  gap: 7px;
  margin-bottom: 7px;
}
.osk-key {
  background: #fff;
  color: #1a1a1a;
  border: 2px solid #c8e6c9;
  border-radius: 10px;
  font-size: 26px;
  font-weight: 700;
  min-width: 72px;
  height: 70px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.08s, transform 0.08s;
  user-select: none;
  -webkit-user-select: none;
  touch-action: manipulation;
  box-shadow: 0 3px 0 #c8e6c9;
}
.osk-key:active, .osk-key.pressed {
  background: #2e7d32;
  color: #fff;
  border-color: #1b5e20;
  box-shadow: 0 1px 0 #1b5e20;
  transform: translateY(2px);
}
.osk-key.wide { min-width: 110px; font-size: 20px; }
.osk-key.space { min-width: 380px; font-size: 18px; letter-spacing: 2px; color: #555; }
.osk-key.space:active, .osk-key.space.pressed { color: #fff; }
.osk-key.backspace { background: #fff3f3; border-color: #ffcdd2; box-shadow: 0 3px 0 #ffcdd2; color: #c62828; min-width: 110px; }
.osk-key.backspace:active, .osk-key.backspace.pressed { background: #c62828; color: #fff; border-color: #b71c1c; box-shadow: 0 1px 0 #b71c1c; }
.osk-key.clear-btn { background: #fff8e1; border-color: #ffe082; box-shadow: 0 3px 0 #ffe082; color: #f57f17; min-width: 110px; font-size: 20px; }
.osk-key.clear-btn:active, .osk-key.clear-btn.pressed { background: #f57f17; color: #fff; border-color: #e65100; box-shadow: 0 1px 0 #e65100; }

@media (orientation: landscape) {
  #oskContainer { display: none !important; }
  .main.osk-open { height: calc(100vh - 190px); }
}

/* Shift Get Now modal up when OSK is open */
#oskContainer.visible ~ * #getNowModal.visible,
body.getnow-osk-open #getNowModal.visible {
  align-items: flex-start;
  padding-top: 30px;
}

/* Main content */
.main {
  height: calc(100vh - 190px);
  overflow-y: auto;
  padding: 20px 0;
}
.main.osk-open {
  height: calc(100vh - 190px - 310px);
}
.section { display: none; }
.section.active { display: block; }

/* Category row (horizontal scroll) - FIXED: Show 4 cards, scroll horizontally */
.category { margin-bottom: 25px; }
.category-title {
  font-size: 18px;
  font-weight: 700;
  color: #1b5e20;
  padding: 0 20px 12px;
}
.category-scroll {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: calc(25% - 12px); /* Show exactly 4 cards */
  gap: 15px;
  overflow-x: auto;
  padding: 0 20px 10px;
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
}
.category-scroll::-webkit-scrollbar { height: 8px; }
.category-scroll::-webkit-scrollbar-track { background: #eee; border-radius: 4px; }
.category-scroll::-webkit-scrollbar-thumb { background: #2e7d32; border-radius: 4px; }

/* Movie card - FIXED: Take full width in grid */
.movie-card {
  width: 100%;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: all 0.15s;
}
.movie-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.15); transform: translateY(-2px); }
.movie-card:active { transform: scale(0.98); }
.movie-poster {
  width: 100%;
  aspect-ratio: 2/3;
  object-fit: cover;
  background: #e0e0e0;
}
.poster-wrap {
  position: relative;
}
.badge-new {
  position: absolute;
  bottom: 6px;
  right: 6px;
  background: #e53935;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  padding: 3px 7px;
  border-radius: 4px;
  letter-spacing: 0.5px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.35);
  z-index: 2;
}
.movie-info { padding: 10px; }
.movie-title {
  font-size: 13px;
  font-weight: 600;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 5px;
}
.movie-rating {
  display: inline-block;
  background: #e8f5e9;
  color: #1b5e20;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
}

/* Grid view (All Movies) - FIXED: Show 4 columns, scroll horizontally */
.movie-grid-container {
  overflow-x: auto;
  padding: 0 20px 20px;
}
.movie-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
  min-width: min-content;
}

/* Search Results - FIXED: Horizontal scroll with 4 visible */
.search-results-container {
  padding: 0 20px;
}
.search-results-scroll {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: calc(25% - 12px);
  gap: 15px;
  overflow-x: auto;
  padding-bottom: 10px;
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
}
.search-results-scroll::-webkit-scrollbar { height: 8px; }
.search-results-scroll::-webkit-scrollbar-track { background: #eee; border-radius: 4px; }
.search-results-scroll::-webkit-scrollbar-thumb { background: #2e7d32; border-radius: 4px; }

/* Loading / Empty */
.loading, .empty {
  text-align: center;
  padding: 50px;
  color: #666;
}
.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e0e0e0;
  border-top-color: #2e7d32;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 15px;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Modal */
.modal-bg {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
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
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}
.modal-head {
  display: flex;
  gap: 20px;
  padding: 20px;
  background: #f5f5f5;
  border-bottom: 1px solid #eee;
}
.modal-poster {
  width: 130px;
  aspect-ratio: 2/3;
  object-fit: cover;
  border-radius: 6px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.2);
}
.modal-details { flex: 1; }
.modal-title {
  font-size: 22px;
  font-weight: 700;
  color: #1b5e20;
  margin-bottom: 10px;
  line-height: 1.2;
}
.badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 700;
  margin-right: 6px;
  margin-bottom: 6px;
}
.badge-rating { background: #e8f5e9; color: #1b5e20; }
.badge-status { background: #e3f2fd; color: #1565c0; }
.badge-status.in { background: #e8f5e9; color: #2e7d32; }
.badge-status.out { background: #fff3e0; color: #e65100; }
.detail-row { font-size: 14px; margin-top: 8px; color: #555; }
.detail-label { color: #888; }
.modal-body { padding: 20px; }
.modal-actions { display: flex; flex-direction: column; gap: 10px; }
.btn-lg {
  padding: 16px;
  font-size: 16px;
  border-radius: 8px;
  font-weight: 700;
  width: 100%;
}
.btn-primary { background: #2e7d32; color: white; }
.btn-primary:hover { background: #1b5e20; }
.btn-blue { background: #1565c0; color: white; }
.btn-blue:hover { background: #0d47a1; }
.btn-gray { background: #f5f5f5; color: #333; border: 1px solid #ddd; }
.btn-gray:hover { background: #eee; }
.btn-purple { background: #6a1b9a; color: white; }
.btn-purple:hover { background: #4a148c; }

.ai-overview-section {
  margin-bottom: 16px;
  padding: 14px;
  background: #f3e5f5;
  border-radius: 8px;
  border-left: 4px solid #6a1b9a;
}
.ai-overview-title {
  font-size: 14px;
  font-weight: 700;
  color: #4a148c;
  margin-bottom: 8px;
}
.ai-overview-text {
  font-size: 14px;
  line-height: 1.5;
  color: #444;
}
.ai-overview-loading {
  font-size: 13px;
  color: #666;
  font-style: italic;
}
.ai-ask-panel {
  display: none;
  margin-bottom: 16px;
  padding: 14px;
  background: #e8eaf6;
  border-radius: 8px;
}
.ai-ask-panel.visible { display: block; }
.ai-ask-input {
  width: 100%;
  padding: 12px;
  font-size: 15px;
  border: 2px solid #c5cae9;
  border-radius: 8px;
  margin-bottom: 10px;
  outline: none;
}
.ai-ask-input:focus { border-color: #3f51b5; }
.ai-ask-answer {
  font-size: 14px;
  line-height: 1.5;
  color: #333;
  padding: 10px;
  background: white;
  border-radius: 6px;
  min-height: 40px;
}

/* Login Modal */
.login-box { padding: 30px; text-align: center; }
.login-title { font-size: 24px; font-weight: 700; color: #1b5e20; margin-bottom: 5px; }
.login-sub { color: #666; margin-bottom: 20px; }
.login-input {
  width: 100%;
  padding: 14px;
  font-size: 20px;
  text-align: center;
  border: 2px solid #ddd;
  border-radius: 8px;
  margin-bottom: 15px;
  letter-spacing: 2px;
}
.login-input:focus { border-color: #2e7d32; outline: none; }
.login-error { color: #d32f2f; font-size: 14px; margin-bottom: 10px; display: none; }
.login-error.visible { display: block; }
.numpad {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  margin-bottom: 15px;
}
.num-btn {
  padding: 18px;
  font-size: 24px;
  font-weight: 700;
  background: #f5f5f5;
  border: 1px solid #ddd;
  border-radius: 8px;
  cursor: pointer;
}
.num-btn:hover { background: #e8f5e9; }
.num-btn:active { background: #c8e6c9; }
.num-btn.go { background: #2e7d32; color: white; border-color: #2e7d32; }
.num-btn.go:hover { background: #1b5e20; }

/* Confirmation */
.confirm-box { padding: 40px 30px; text-align: center; }
.confirm-icon { font-size: 60px; margin-bottom: 15px; }
.confirm-title { font-size: 24px; font-weight: 700; color: #2e7d32; margin-bottom: 10px; }
.confirm-msg { font-size: 16px; color: #555; margin-bottom: 25px; line-height: 1.5; }

/* Timeout bar */
.timeout-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #ff9800;
  color: white;
  padding: 16px 25px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 16px;
  font-weight: 600;
  z-index: 2000;
  transform: translateY(100%);
  transition: transform 0.3s;
}
.timeout-bar.visible { transform: translateY(0); }
.timeout-bar button {
  padding: 10px 24px;
  background: white;
  color: #e65100;
  border: none;
  border-radius: 6px;
  font-weight: 700;
  cursor: pointer;
}

/* Toast */
.toast {
  position: fixed;
  bottom: 25px;
  left: 50%;
  transform: translateX(-50%) translateY(100px);
  background: #333;
  color: white;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 500;
  z-index: 3000;
  opacity: 0;
  transition: all 0.3s;
}
.toast.visible { opacity: 1; transform: translateX(-50%) translateY(0); }
.toast.success { background: #2e7d32; }
.toast.error { background: #d32f2f; }

/* Patron Status Bar */
.patron-status-bar {
  background: linear-gradient(to bottom, #f9f9f9, #f0f0f0);
  border-bottom: 3px solid #2e7d32;
  padding: 14px 24px;
  display: none;
  justify-content: space-between;
  align-items: center;
  font-size: 17px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.patron-status-bar.visible { display: flex; }
.patron-info { 
  display: flex; 
  gap: 35px; 
  align-items: center; 
}
.patron-info-item { 
  display: flex; 
  align-items: center; 
  gap: 10px;
  font-weight: 600;
}
.patron-info-item.warning { 
  color: #d32f2f; 
  font-weight: 700;
  animation: pulse 2s infinite;
}
.patron-info-item.good { color: #2e7d32; }
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.6; }
}
.checkout-counter {
  font-size: 20px;
  font-weight: 700;
  padding: 10px 20px;
  background: white;
  border-radius: 8px;
  border: 3px solid #2e7d32;
  color: #2e7d32;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}
.checkout-counter.warning {
  border-color: #ff9800;
  color: #ff9800;
}
.checkout-counter.blocked {
  border-color: #d32f2f;
  color: #d32f2f;
  background: #ffebee;
}

/* Loading spinner */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.status-spinner {
  display: inline-block;
  animation: spin 1s linear infinite;
  margin-right: 6px;
}

/* Full-screen loading overlay */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  flex-direction: column;
  gap: 20px;
}
.loading-overlay.visible {
  display: flex;
}
.loading-spinner {
  width: 80px;
  height: 80px;
  border: 8px solid #f3f3f3;
  border-top: 8px solid #2e7d32;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}
.loading-text {
  color: white;
  font-size: 24px;
  font-weight: 600;
  text-align: center;
  max-width: 80%;
}
.loading-subtext {
  color: #ccc;
  font-size: 18px;
  text-align: center;
}

/* Button loading states */
.btn.loading {
  position: relative;
  color: transparent;
  pointer-events: none;
}
.btn.loading::after {
  content: '';
  position: absolute;
  width: 20px;
  height: 20px;
  top: 50%;
  left: 50%;
  margin-left: -10px;
  margin-top: -10px;
  border: 3px solid #ffffff;
  border-radius: 50%;
  border-top-color: transparent;
  animation: spin 0.6s linear infinite;
}

/* Holds display */
.hold-card {
  background: white;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 12px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  display: flex;
  gap: 15px;
  align-items: start;
}
.hold-poster {
  width: 80px;
  aspect-ratio: 2/3;
  object-fit: cover;
  border-radius: 6px;
  background: #e0e0e0;
  flex-shrink: 0;
}
.hold-details { flex: 1; }
.hold-title {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 8px;
  color: #1b5e20;
}
.hold-meta {
  font-size: 13px;
  color: #666;
  margin-bottom: 4px;
}
.hold-status {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 700;
  margin-top: 8px;
}
.hold-status.ready {
  background: #4caf50;
  color: white;
}
.hold-status.pending {
  background: #ff9800;
  color: white;
}
.hold-status.shipped {
  background: #2196f3;
  color: white;
}
.hold-actions {
  display: flex;
  gap: 8px;
  margin-top: 10px;
}
.hold-actions .btn {
  padding: 8px 16px;
  font-size: 13px;
}
.holds-section-title {
  font-size: 20px;
  font-weight: 700;
  color: #1b5e20;
  margin-bottom: 15px;
  padding-left: 5px;
}

</style>
</head>
<body>

<header class="header">
  <div>
    <div class="logo"><?php echo htmlspecialchars($libraryName); ?></div>
    <div class="logo-sub">DVD & Movie Collection</div>
  </div>
  <div class="user-area">
    <div class="user-info" id="userInfo">
      <div class="user-name" id="userName">Welcome!</div>
      <div class="user-card" id="userCard"></div>
    </div>
    <button class="btn btn-white" id="btnLogin">Sign In</button>
    <button class="btn btn-outline" id="btnLogout" style="display:none;">Sign Out</button>
  </div>
</header>

<nav class="nav">
  <button class="nav-btn active" data-tab="browse">Browse</button>
  <button class="nav-btn" data-tab="all">All Movies</button>
  <button class="nav-btn" data-tab="search">Search</button>
  <button class="nav-btn" data-tab="holds" id="btnHoldsTab" style="display: none;">My Holds</button>
</nav>

<!-- Patron Status Bar -->
<div class="patron-status-bar" id="patronStatusBar">
  <div class="patron-info">
    <div class="patron-info-item" id="patronNameDisplay">
      <span>👤</span>
      <span id="patronName">Guest</span>
    </div>
    <div class="patron-info-item" id="patronFinesDisplay">
      <span>💰</span>
      <span>Fines: <strong id="patronFines">$0.00</strong></span>
    </div>
  </div>
  <div class="checkout-counter" id="checkoutCounter">
    DVDs Checked Out: <span id="dvdCount">0</span>/5
  </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
  <button class="filter-btn active" id="filterAll" onclick="setFilter('all')">
    <span>📚 All Movies</span>
    <span class="count" id="countAll">0</span>
  </button>
  <button class="filter-btn" id="filterAvailable" onclick="setFilter('available')">
    <span>✅ Available Now</span>
    <span class="count" id="countAvailable">0</span>
  </button>
</div>

<div class="search-bar" id="searchBar">
  <input type="text" class="search-input" id="searchInput" placeholder="Type a movie title...">
</div>

<!-- On-screen keyboard -->
<div id="oskContainer">
  <div class="osk-row">
    <?php foreach(['Q','W','E','R','T','Y','U','I','O','P'] as $k): ?>
    <button class="osk-key" data-key="<?= $k ?>"><?= $k ?></button>
    <?php endforeach; ?>
  </div>
  <div class="osk-row">
    <?php foreach(['A','S','D','F','G','H','J','K','L'] as $k): ?>
    <button class="osk-key" data-key="<?= $k ?>"><?= $k ?></button>
    <?php endforeach; ?>
  </div>
  <div class="osk-row">
    <?php foreach(['Z','X','C','V','B','N','M'] as $k): ?>
    <button class="osk-key" data-key="<?= $k ?>"><?= $k ?></button>
    <?php endforeach; ?>
    <button class="osk-key backspace" data-key="BACKSPACE">⌫</button>
  </div>
  <div class="osk-row">
    <button class="osk-key clear-btn" data-key="CLEAR">Clear</button>
    <button class="osk-key space" data-key="SPACE">SPACE</button>
    <button class="osk-key wide" data-key="APOSTROPHE">'</button>
    <button class="osk-key wide" data-key="COLON">:</button>
    <button class="osk-key wide osk-done-btn" data-key="DONE" id="oskDoneBtn" style="display:none;background:#2e7d32;color:#fff;border-color:#1b5e20;min-width:110px;">✓ Done</button>
  </div>
</div>

<main class="main">
  <!-- Browse tab: horizontal scroll rows -->
  <section class="section active" id="tabBrowse">
    <div class="category">
      <div class="category-title">⭐ Staff Picks</div>
      <div class="category-scroll" id="rowFeatured">
        <div class="loading"><div class="spinner"></div>Loading...</div>
      </div>
    </div>
    <div class="category">
      <div class="category-title">🆕 New Arrivals</div>
      <div class="category-scroll" id="rowNew">
        <div class="loading"><div class="spinner"></div>Loading...</div>
      </div>
    </div>
    <div class="category">
      <div class="category-title">🔥 Hot Movies</div>
      <div class="category-scroll" id="rowRecent">
        <div class="loading"><div class="spinner"></div>Loading...</div>
      </div>
    </div>
  </section>
  
  <!-- All Movies tab: grid -->
  <section class="section" id="tabAll">
    <div class="movie-grid-container">
      <div class="movie-grid" id="gridAll">
        <div class="loading"><div class="spinner"></div>Loading...</div>
      </div>
    </div>
  </section>
  
  <!-- Search tab: horizontal scroll -->
  <section class="section" id="tabSearch">
    <div class="search-results-container">
      <div class="search-results-scroll" id="searchResults"></div>
    </div>
    <div class="empty" id="searchEmpty">
      <div style="font-size:40px;margin-bottom:10px;">🔍</div>
      <div style="font-weight:600;">Search for a movie</div>
      <div>Enter a title above</div>
    </div>
  </section>
  
  <!-- My Holds tab -->
  <section class="section" id="tabHolds">
    <div id="holdsLoading" class="loading">
      <div class="spinner"></div>
      <div>Loading your holds...</div>
    </div>
    <div id="holdsContent" style="display:none; padding: 20px;">
      <div id="holdsReady" style="margin-bottom: 30px;"></div>
      <div id="holdsWaiting" style="margin-bottom: 30px;"></div>
      <div id="holdsOther"></div>
    </div>
    <div class="empty" id="holdsEmpty" style="display:none;">
      <div style="font-size:40px;margin-bottom:10px;">📚</div>
      <div style="font-weight:600;">No Holds</div>
      <div>You don't have any active holds</div>
    </div>
  </section>
</main>

<!-- Movie Detail Modal -->
<div class="modal-bg" id="movieModal">
  <div class="modal">
    <div class="modal-head">
      <img class="modal-poster" id="modalPoster" src="" alt="">
      <div class="modal-details">
        <div class="modal-title" id="modalTitle">Movie Title</div>
        <div>
          <span class="badge badge-rating" id="modalRating">PG</span>
          <span class="badge badge-status" id="modalStatus">
            <span class="status-spinner" style="display:none;">⏳</span>
            <span class="status-text">Checking...</span>
          </span>
        </div>
        <div class="detail-row"><span class="detail-label">Call #:</span> <span id="modalCall">—</span></div>
        <div class="detail-row"><span class="detail-label">Barcode:</span> <span id="modalBarcode">—</span></div>
        <div class="detail-row"><span class="detail-label">Location:</span> <span id="modalLocation">DVD Section</span></div>
      </div>
    </div>
    <div class="modal-body">
      <div class="ai-overview-section" id="aiOverviewSection">
        <div class="ai-overview-title">✨ AI Overview</div>
        <div class="ai-overview-text ai-overview-loading" id="modalOverview">Loading overview…</div>
      </div>
      <div class="ai-ask-panel" id="aiAskPanel">
        <div class="ai-overview-title">💬 Ask about this movie</div>
        <input type="text" class="ai-ask-input" id="aiAskInput" placeholder="e.g. Is this good for kids?">
        <button class="btn btn-lg btn-purple" id="btnAiAskSubmit" style="margin-bottom:10px;">Ask</button>
        <div class="ai-ask-answer" id="aiAskAnswer"></div>
      </div>
      <div class="modal-actions">
        <button class="btn btn-lg btn-purple" id="btnAskAi">🤖 Ask AI About This Movie</button>
        <button class="btn btn-lg btn-primary" id="btnRequestNow">📋 Get Now — Staff will pull it</button>
        <button class="btn btn-lg btn-blue" id="btnPlaceHold">📌 Place a Hold — Pick up another day</button>
        <button class="btn btn-lg btn-gray" id="btnCloseMovie">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Login Modal -->
<div class="modal-bg" id="loginModal">
  <div class="modal" style="max-width:400px;">
    <div class="login-box">
      <div class="login-title">Welcome!</div>
      <div class="login-sub">Scan or enter your library card</div>
      <input type="text" class="login-input" id="barcodeInput" placeholder="Library card #" autofocus autocomplete="off">
      <div class="login-error" id="loginError">Card not found</div>
      <div class="numpad">
        <button class="num-btn" data-n="1">1</button>
        <button class="num-btn" data-n="2">2</button>
        <button class="num-btn" data-n="3">3</button>
        <button class="num-btn" data-n="4">4</button>
        <button class="num-btn" data-n="5">5</button>
        <button class="num-btn" data-n="6">6</button>
        <button class="num-btn" data-n="7">7</button>
        <button class="num-btn" data-n="8">8</button>
        <button class="num-btn" data-n="9">9</button>
        <button class="num-btn" data-n="⌫">⌫</button>
        <button class="num-btn" data-n="0">0</button>
        <button class="num-btn go" data-n="GO">GO</button>
      </div>
      <button class="btn btn-lg btn-gray" id="btnCancelLogin">Cancel</button>
    </div>
  </div>
</div>

<!-- Get Now Modal (card OR name) -->
<div class="modal-bg" id="getNowModal">
  <div class="modal" style="max-width:400px;">
    <div class="login-box">
      <div class="login-title">Get Now</div>
      <div class="login-sub" id="getNowSub">Enter your library card number or your name</div>
      <input type="text" class="login-input" id="getNowInput" placeholder="Card number or name" autocomplete="off">
      <div class="login-error" id="getNowError"></div>
      <div class="numpad" id="getNowNumpad">
        <button class="num-btn" data-gn="1">1</button>
        <button class="num-btn" data-gn="2">2</button>
        <button class="num-btn" data-gn="3">3</button>
        <button class="num-btn" data-gn="4">4</button>
        <button class="num-btn" data-gn="5">5</button>
        <button class="num-btn" data-gn="6">6</button>
        <button class="num-btn" data-gn="7">7</button>
        <button class="num-btn" data-gn="8">8</button>
        <button class="num-btn" data-gn="9">9</button>
        <button class="num-btn" data-gn="⌫">⌫</button>
        <button class="num-btn" data-gn="0">0</button>
        <button class="num-btn go" data-gn="GO">GO</button>
      </div>
      <button class="btn btn-lg" id="btnGetNowKeyboard" style="background:#e8f5e9;color:#2e7d32;border:2px solid #c8e6c9;margin-bottom:8px;">⌨ Enter Name Instead</button>
      <button class="btn btn-lg btn-gray" id="btnCancelGetNow">Cancel</button>
    </div>
  </div>
</div>

<!-- Confirmation Modal -->
<div class="modal-bg" id="confirmModal">
  <div class="modal" style="max-width:450px;">
    <div class="confirm-box">
      <div class="confirm-icon" id="confirmIcon">✅</div>
      <div class="confirm-title" id="confirmTitle">Request Sent!</div>
      <div class="confirm-msg" id="confirmMsg">Staff will pull your movie. Please wait at the desk.</div>
      <button class="btn btn-lg btn-primary" id="btnConfirmOK">OK</button>
    </div>
  </div>
</div>

<!-- Timeout bar -->
<div class="timeout-bar" id="timeoutBar">
  <span>⏱️ Still there? Session ends in <strong id="timeoutNum"><?php echo $warning; ?></strong>s</span>
  <button id="btnStayHere">I'm Here!</button>
</div>

<div class="toast" id="toast"></div>

<script>
const NO_COVER = '/img/no-cover.svg';
const TIMEOUT_IDLE = <?php echo $timeout * 1000; ?>;
const TIMEOUT_WARN = <?php echo $warning * 1000; ?>;

// Helper functions
const $ = s => document.querySelector(s);
const $$ = s => document.querySelectorAll(s);
const esc = str => {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
};

// Loading overlay helpers
function showLoading(text = 'Loading...', subtext = '') {
  $('#loadingText').textContent = text;
  $('#loadingSubtext').textContent = subtext;
  $('#loadingOverlay').classList.add('visible');
}

function hideLoading() {
  $('#loadingOverlay').classList.remove('visible');
}

let movies = [];
let movieMap = {};
let currentFilter = 'all'; // 'all' or 'available'
let movieSource = 'unknown';
let lastSync = null;
let currentUser = null;
let patronStatus = null; // Stores fines, checkout counts, blocking info
let currentMovie = null;
let idleTimer = null;
let warnInterval = null;
let kioskSettings = {};

// Initialize — Paxlib Kiosk v3 (recordset; no availability polling)
async function init() {
  console.log('%c Paxlib Kiosk v3 — recordset mode ', 'background:#2e7d32;color:#fff;font-weight:bold;padding:2px 8px;border-radius:4px');
  await loadKioskSettings();
  await loadMovies();
  renderAll();
  setupEvents();
  maybeAutoSyncMovies();
  startCoverSyncIfNeeded();
}

async function loadKioskSettings() {
  try {
    const res = await fetch('api/settings.php');
    const data = await res.json();
    if (data.ok) kioskSettings = data.settings || {};
  } catch (e) {
    console.warn('Could not load kiosk settings', e);
  }
}

async function maybeAutoSyncMovies() {
  if (kioskSettings.autoSyncMovies === false) return;

  const intervalMin = parseInt(kioskSettings.syncIntervalMinutes, 10) || 15;
  try {
    const res = await fetch('api/sync-movies.php');
    const data = await res.json();
    if (!data.ok || data.running) return;

    const lastSync = data.meta?.lastSync ? new Date(data.meta.lastSync).getTime() : 0;
    const ageMs = lastSync ? (Date.now() - lastSync) : Infinity;

    if (ageMs < intervalMin * 60 * 1000) {
      console.log(`Recordset sync is fresh (${Math.round(ageMs / 60000)}m old)`);
      return;
    }

    console.log('Auto-syncing movies from Polaris recordset…');
    const syncRes = await fetch('api/sync-movies.php', { method: 'POST' });
    const syncData = await syncRes.json();
    if (syncData.ok) {
      console.log(`Auto-sync complete: ${syncData.count} movies`);
      await loadMovies();
      renderAll();
      startCoverSyncIfNeeded();
    } else {
      console.warn('Auto-sync failed:', syncData.error);
    }
  } catch (e) {
    console.warn('Auto-sync skipped:', e);
  }
}

async function startCoverSyncIfNeeded() {
  const missing = movies.filter(m => !m.cover || m.cover.includes('no-cover')).length;
  if (missing === 0) return;

  console.log(`Fetching covers in background (${missing} missing)…`);

  (async function runCoverSync() {
    while (true) {
      try {
        const res = await fetch('api/sync-covers.php', { method: 'POST' });
        const data = await res.json();
        if (!data.ok) break;

        if (data.updated > 0) {
          console.log(`Covers: +${data.updated} (${data.remaining} remaining)`);
          await loadMovies();
          renderAll();
        }

        if (data.done || data.remaining === 0) {
          console.log('Cover sync complete');
          break;
        }

        await new Promise(r => setTimeout(r, 1500));
      } catch (e) {
        console.warn('Cover sync paused:', e);
        break;
      }
    }
  })();
}

// Load movies (list + availability from Polaris recordset sync)
async function loadMovies() {
  try {
    const res = await fetch('api/movies.php');
    const data = await res.json();
    if (data.ok) {
      movies = data.items || [];
      movieMap = Object.fromEntries(movies.map(m => [m.barcode, m]));
      movieSource = data.source || 'unknown';
      lastSync = data.lastSync || null;
      const availableCount = movies.filter(m => m.available === true).length;
      const withCovers = movies.filter(m => m.cover && !m.cover.includes('no-cover')).length;
      console.log(`Loaded ${movies.length} movies from ${movieSource}` +
        (lastSync ? ` (synced ${lastSync})` : '') +
        ` — ${availableCount} available, ${withCovers} with covers`);
      if (movieSource === 'csv') {
        console.warn('Using CSV fallback — run "Sync Movies from Polaris" in the staff dashboard for live In/Out status.');
      }
      updateFilterCounts();
    }
  } catch (e) {
    console.error('Failed to load movies:', e);
  }
}

// Set filter
function setFilter(filter) {
  currentFilter = filter;
  
  // Update button states
  $('#filterAll').classList.toggle('active', filter === 'all');
  $('#filterAvailable').classList.toggle('active', filter === 'available');
  
  // Re-render all sections
  renderAll();
  
  // If on search, re-run search
  const searchInput = $('#searchInput');
  if (searchInput.value.trim()) {
    doSearch(searchInput.value);
  }
}

// Get filtered movies based on current filter
function getFilteredMovies(movieList = movies) {
  if (currentFilter === 'available') {
    return movieList.filter(m => m.available === true);
  }
  return movieList;
}

// Update filter counts
function updateFilterCounts() {
  const availableCount = movies.filter(m => m.available === true).length;
  $('#countAll').textContent = movies.length;
  $('#countAvailable').textContent = availableCount;
}

// Render movie card
function card(m) {
  const coverSrc = m.cover || NO_COVER;
  const unavailableClass = m.available === false ? ' unavailable' : '';
  const newBadge = m.isNew ? '<span class="badge-new">NEW</span>' : '';
  
  return `
    <div class="movie-card${unavailableClass}" data-barcode="${m.barcode}">
      <div class="poster-wrap">
        <img class="movie-poster" src="${coverSrc}" 
             onerror="this.src='${NO_COVER}'" 
             onload="if(this.naturalWidth <= 2 && this.naturalHeight <= 2) this.src='${NO_COVER}'"
             loading="lazy">
        ${newBadge}
      </div>
      <div class="movie-info">
        <div class="movie-title">${esc(m.title)}</div>
        ${m.rating ? `<span class="movie-rating">${esc(m.rating)}</span>` : ''}
      </div>
    </div>
  `;
}

function getHotMovies(movieList) {
  return movieList
    .filter(m => m.isHot)
    .sort((a, b) => {
      const ta = Date.parse(a.lastActivity || '') || 0;
      const tb = Date.parse(b.lastActivity || '') || 0;
      return tb - ta;
    });
}

function getAutoNewArrivals(movieList) {
  return movieList
    .filter(m => m.isNew)
    .sort((a, b) => {
      const ta = Date.parse(a.dateAdded || '') || 0;
      const tb = Date.parse(b.dateAdded || '') || 0;
      return tb - ta;
    });
}

// Render all sections
function renderAll() {
  const filteredMovies = getFilteredMovies();
  
  fetch('api/settings.php')
    .then(r => r.json())
    .then(data => {
      const s = data.settings || {};
      
      // Featured
      const featuredBarcodes = s.featured || [];
      const featured = getFilteredMovies(featuredBarcodes.map(bc => movieMap[bc]).filter(Boolean));
      $('#rowFeatured').innerHTML = featured.length 
        ? featured.map(card).join('') 
        : filteredMovies.slice(0, 10).map(card).join('');
      
      // New arrivals — staff picks or auto (added this month)
      const newBarcodes = s.newArrivals || [];
      const curatedNew = getFilteredMovies(newBarcodes.map(bc => movieMap[bc]).filter(Boolean));
      const autoNew = getAutoNewArrivals(filteredMovies);
      const newArrivals = curatedNew.length ? curatedNew : autoNew;
      $('#rowNew').innerHTML = newArrivals.length
        ? newArrivals.map(card).join('')
        : '<div class="empty" style="padding:20px;">No new arrivals this month</div>';
      
      // Hot movies — in-status with recent activity
      const hotMovies = getHotMovies(filteredMovies);
      $('#rowRecent').innerHTML = hotMovies.length
        ? hotMovies.map(card).join('')
        : '<div class="empty" style="padding:20px;">No hot movies right now</div>';
      
      // All movies grid
      $('#gridAll').innerHTML = filteredMovies.map(card).join('');
      
      attachClicks();
    })
    .catch(() => {
      $('#rowFeatured').innerHTML = filteredMovies.slice(0, 10).map(card).join('');
      const autoNew = getAutoNewArrivals(filteredMovies);
      $('#rowNew').innerHTML = autoNew.length ? autoNew.map(card).join('') : '';
      const hotMovies = getHotMovies(filteredMovies);
      $('#rowRecent').innerHTML = hotMovies.length ? hotMovies.map(card).join('') : '';
      $('#gridAll').innerHTML = filteredMovies.map(card).join('');
      
      attachClicks();
    });
}

const defaultSearchEmptyHTML = `
  <div style="font-size:40px;margin-bottom:10px;">🔍</div>
  <div style="font-weight:600;">Search for a movie</div>
  <div>Enter a title above</div>
`;

function doSearch(q) {
  q = (q || '').toLowerCase().trim();

  const searchResults = $('#searchResults');
  const searchEmpty = $('#searchEmpty');

  if (!q) {
    searchResults.innerHTML = '';
    searchEmpty.style.display = 'block';
    searchEmpty.innerHTML = defaultSearchEmptyHTML;
    return;
  }

  // First filter by search query
  const searchMatches = movies.filter(m =>
    (m.title || '').toLowerCase().includes(q) ||
    String(m.barcode || '').toLowerCase().includes(q)
  );
  
  // Then apply availability filter
  const results = getFilteredMovies(searchMatches);

  console.log(`Search for "${q}": found ${results.length} results`);

  if (results.length === 0) {
    searchResults.innerHTML = '';
    searchEmpty.style.display = 'block';
    searchEmpty.innerHTML = `
      <div style="font-size:40px;margin-bottom:10px;">😕</div>
      <div style="font-weight:600;">No movies found for "${esc(q)}"</div>
      <div>Try searching for something else</div>
    `;
  } else {
    searchEmpty.style.display = 'none';
    searchResults.innerHTML = results.map(card).join('');
    attachClicks();
  }
}

// Attach click handlers
function attachClicks() {
  $$('.movie-card').forEach(c => {
    c.onclick = () => openMovie(c.dataset.barcode);
  });
}

// Open movie modal
async function openMovie(barcode) {
  currentMovie = movieMap[barcode] || { barcode };

  $('#modalTitle').textContent = currentMovie.title || 'Unknown';
  const modalPoster = $('#modalPoster');
  modalPoster.src = currentMovie.cover || NO_COVER;
  modalPoster.onload = function() {
    if (this.naturalWidth <= 2 && this.naturalHeight <= 2) {
      this.src = NO_COVER;
    }
  };

  $('#modalRating').textContent = currentMovie.rating || 'NR';
  $('#modalBarcode').textContent = barcode;
  $('#modalCall').textContent = currentMovie.callNumber || '—';
  $('#modalLocation').textContent = currentMovie.location || 'DVD Section';

  const spinner = $('#modalStatus .status-spinner');
  const statusText = $('#modalStatus .status-text');
  spinner.style.display = 'none';

  const status = currentMovie.status || (currentMovie.available ? 'In' : 'Out');
  const isAvailable = currentMovie.available === true;

  statusText.textContent = status;
  $('#modalStatus').className = 'badge badge-status ' + (isAvailable ? 'in' : 'out');

  updateModalActionButtons(isAvailable);

  $('#aiAskPanel').classList.remove('visible');
  $('#aiAskInput').value = '';
  $('#aiAskAnswer').textContent = '';
  loadMovieOverview(barcode, currentMovie.title || '');

  $('#movieModal').classList.add('visible');
}

async function loadMovieOverview(barcode, title) {
  const el = $('#modalOverview');
  el.className = 'ai-overview-text ai-overview-loading';
  el.textContent = 'Loading overview…';

  try {
    const url = `api/movie-ai.php?action=overview&barcode=${encodeURIComponent(barcode)}&title=${encodeURIComponent(title)}`;
    const res = await fetch(url);
    const data = await res.json();
    el.className = 'ai-overview-text';
    if (data.ok && data.overview) {
      el.textContent = data.overview;
    } else {
      el.textContent = data.error || 'Overview not available.';
    }
  } catch (e) {
    el.className = 'ai-overview-text';
    el.textContent = 'Could not load overview.';
  }
}

function toggleAiAskPanel() {
  const panel = $('#aiAskPanel');
  panel.classList.toggle('visible');
  if (panel.classList.contains('visible')) {
    $('#aiAskInput').focus();
  }
}

async function submitAiQuestion() {
  if (!currentMovie) return;

  const question = ($('#aiAskInput').value || '').trim();
  if (!question) return;

  const answerEl = $('#aiAskAnswer');
  answerEl.textContent = 'Thinking…';

  try {
    const res = await fetch('api/movie-ai.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        barcode: currentMovie.barcode,
        title: currentMovie.title || '',
        question,
      }),
    });
    const data = await res.json();
    answerEl.textContent = data.ok ? data.answer : (data.error || 'Could not get an answer.');
  } catch (e) {
    answerEl.textContent = 'Could not reach the AI assistant.';
  }
}

function updateModalActionButtons(isAvailable) {
  $('#btnRequestNow').style.display = isAvailable ? 'block' : 'none';
  $('#btnPlaceHold').style.display = 'block';
}

function closeMovie() {
  $('#movieModal').classList.remove('visible');
}

// Login
function showLogin(forHold = false) {
  $('#barcodeInput').value = '';
  $('#loginError').classList.remove('visible');
  const sub = document.querySelector('#loginModal .login-sub');
  if (sub) {
    sub.textContent = forHold
      ? 'Enter your library card to place a hold'
      : 'Scan or enter your library card';
  }
  $('#loginModal').classList.add('visible');
  setTimeout(() => $('#barcodeInput').focus(), 100);
}

function showHoldLogin() {
  showLogin(true);
}

function closeLogin() {
  $('#loginModal').classList.remove('visible');
}

async function doLogin() {
  const barcode = $('#barcodeInput').value.trim();

  if (!barcode) {
    $('#loginError').textContent = 'Please enter your card number';
    $('#loginError').classList.add('visible');
    return;
  }

  // optional: basic barcode format check (prevents junk like "hello")
  if (!/^\d{5,20}$/.test(barcode)) {
    $('#loginError').textContent = 'Invalid barcode format';
    $('#loginError').classList.add('visible');
    return;
  }

  showLoading('Logging in...', 'Please wait');

  try {
    // First get basic patron info
    const res = await fetch(`api/patron.php?barcode=${encodeURIComponent(barcode)}`);
    const data = await res.json();

    if (!data.ok || !data.patron) {
      hideLoading();
      $('#loginError').textContent = 'Card not found';
      $('#loginError').classList.add('visible');
      toast('Invalid library card', 'error');
      return;
    }

    currentUser = data.patron;

    // Now check patron status (fines, checkouts)
    const statusRes = await fetch(`api/patron-status.php?barcode=${encodeURIComponent(barcode)}`);
    const statusData = await statusRes.json();
    
    hideLoading();
    
    if (statusData.ok) {
      patronStatus = statusData;
      
      // Update patron status bar
      $('#patronName').textContent = statusData.patronName;
      $('#patronFines').textContent = '$' + statusData.fines.total.toFixed(2);
      $('#dvdCount').textContent = statusData.checkouts.dvds;
      
      // Apply warning styling
      const finesDisplay = $('#patronFinesDisplay');
      finesDisplay.classList.remove('warning', 'good');
      if (statusData.fines.total > 5) {
        finesDisplay.classList.add('warning');
      } else if (statusData.fines.total > 0) {
        finesDisplay.classList.add('warning');
      } else {
        finesDisplay.classList.add('good');
      }
      
      const counter = $('#checkoutCounter');
      counter.classList.remove('warning', 'blocked');
      if (statusData.checkouts.dvds >= 5) {
        counter.classList.add('blocked');
      } else if (statusData.checkouts.dvds >= 4) {
        counter.classList.add('warning');
      }
      
      // Show patron status bar
      $('#patronStatusBar').classList.add('visible');
      
      // Show blocking message if needed
      if (!statusData.canCheckout && statusData.blockReasons.length > 0) {
        toast('⚠️ ' + statusData.blockReasons.join('. '), 'error');
      }
    }

    $('#userName').textContent = `Hello, ${currentUser.name}!`;
    $('#userCard').textContent = `Card: ${currentUser.barcode}`;
    $('#userInfo').classList.add('visible');
    $('#btnLogin').style.display = 'none';
    $('#btnLogout').style.display = 'block';
    $('#btnHoldsTab').style.display = 'block'; // Show holds tab

    closeLogin();
    toast(`Welcome, ${currentUser.name}!`, 'success');
    resetIdleTimer();

    if (_pendingHoldAfterLogin) {
      _pendingHoldAfterLogin = false;
      await requestMovie('hold');
      return;
    }

  } catch (e) {
    hideLoading();
    console.error("Login API error:", e);

    $('#loginError').textContent = 'Login system unavailable';
    $('#loginError').classList.add('visible');
    toast('Login failed. Try again.', 'error');
  }
}


function doLogout() {
  currentUser = null;
  patronStatus = null;
  $('#userInfo').classList.remove('visible');
  $('#btnLogin').style.display = 'block';
  $('#btnLogout').style.display = 'none';
  $('#btnHoldsTab').style.display = 'none'; // Hide holds tab
  $('#patronStatusBar').classList.remove('visible'); // Hide status bar
  
  // Switch back to browse tab if on holds
  const activeBtn = document.querySelector('.nav-btn.active');
  if (activeBtn && activeBtn.dataset.tab === 'holds') {
    $$('.nav-btn')[0].click(); // Click first tab (Browse)
  }
  
  hideTimeout();
  toast('Signed out');
}

// ── Get Now modal (card OR name) ──────────────────────────────────────────────
let _pendingGetNowMovie = null;
let _pendingHoldAfterLogin = false;

function showGetNowLogin(movie) {
  _pendingGetNowMovie = movie || currentMovie;
  $('#getNowInput').value = '';
  $('#getNowInput').placeholder = 'Card number or name';
  $('#getNowError').textContent = '';
  $('#getNowError').classList.remove('visible');
  $('#getNowNumpad').style.display = 'grid';
  $('#btnGetNowKeyboard').style.display = 'block';
  $('#getNowModal').classList.add('visible');
  setTimeout(() => $('#getNowInput').focus(), 100);
}

function closeGetNowModal() {
  $('#getNowModal').classList.remove('visible');
  // Hide OSK if it was open for Get Now
  const osk = $('#oskContainer');
  if (osk.classList.contains('visible')) {
    osk.classList.remove('visible');
    $('.main').classList.remove('osk-open');
    document.body.classList.remove('getnow-osk-open');
    $('#oskDoneBtn').style.display = 'none';
  }
  _pendingGetNowMovie = null;
}

async function doGetNowLogin() {
  const input = $('#getNowInput').value.trim();
  if (!input) {
    $('#getNowError').textContent = 'Please enter your card number or name';
    $('#getNowError').classList.add('visible');
    return;
  }

  const isBarcode = /^\d+$/.test(input);

  if (isBarcode) {
    // Validate card via API
    showLoading('Checking card...', 'Please wait');
    try {
      const res = await fetch(`api/patron.php?barcode=${encodeURIComponent(input)}`);
      const data = await res.json();
      if (!data.ok || !data.patron) {
        hideLoading();
        $('#getNowError').textContent = 'Card not found';
        $('#getNowError').classList.add('visible');
        return;
      }
      currentUser = data.patron;

      // Load status silently
      try {
        const statusRes = await fetch(`api/patron-status.php?barcode=${encodeURIComponent(input)}`);
        const statusData = await statusRes.json();
        if (statusData.ok) {
          patronStatus = statusData;
          $('#patronName').textContent = statusData.patronName;
          $('#patronFines').textContent = '$' + statusData.fines.total.toFixed(2);
          $('#dvdCount').textContent = statusData.checkouts.dvds;
          const finesDisplay = $('#patronFinesDisplay');
          finesDisplay.classList.remove('warning', 'good');
          finesDisplay.classList.add(statusData.fines.total > 0 ? 'warning' : 'good');
          const counter = $('#checkoutCounter');
          counter.classList.remove('warning', 'blocked');
          if (statusData.checkouts.dvds >= 5) counter.classList.add('blocked');
          else if (statusData.checkouts.dvds >= 4) counter.classList.add('warning');
          $('#patronStatusBar').classList.add('visible');
        }
      } catch(e) {}

      hideLoading();
    } catch(e) {
      hideLoading();
      $('#getNowError').textContent = 'Login system unavailable';
      $('#getNowError').classList.add('visible');
      return;
    }
  } else {
    // Name only — no API call, no validation
    currentUser = { name: input, barcode: null, id: null, nameOnly: true };
    patronStatus = null;
  }

  // Update header
  $('#userName').textContent = `Hello, ${currentUser.name}!`;
  $('#userCard').textContent = currentUser.barcode ? `Card: ${currentUser.barcode}` : 'Name sign-in';
  $('#userInfo').classList.add('visible');
  $('#btnLogin').style.display = 'none';
  $('#btnLogout').style.display = 'block';
  if (!currentUser.nameOnly) $('#btnHoldsTab').style.display = 'block';

  closeGetNowModal();
  toast(`Welcome, ${currentUser.name}!`, 'success');
  resetIdleTimer();

  // Now proceed with the Get Now request
  await requestMovie('now');
}

// Load patron holds
async function loadHolds() {
  if (!currentUser) return;
  
  $('#holdsLoading').style.display = 'block';
  $('#holdsContent').style.display = 'none';
  $('#holdsEmpty').style.display = 'none';
  
  try {
    const res = await fetch(`api/patron-holds.php?patronBarcode=${encodeURIComponent(currentUser.barcode)}`);
    const data = await res.json();
    
    if (!data.ok) {
      toast('Failed to load holds', 'error');
      return;
    }
    
    const holds = data.holds || [];
    
    if (holds.length === 0) {
      $('#holdsLoading').style.display = 'none';
      $('#holdsEmpty').style.display = 'block';
      return;
    }
    
    // Group holds by status
    const ready = holds.filter(h => h.RequestStatusDescription === 'Held');
    const waiting = holds.filter(h => ['Active', 'Pending', 'Shipped'].includes(h.RequestStatusDescription));
    const other = holds.filter(h => !['Held', 'Active', 'Pending', 'Shipped'].includes(h.RequestStatusDescription));
    
    // Display holds
    $('#holdsReady').innerHTML = ready.length > 0 
      ? `<div class="holds-section-title">📦 Ready for Pickup (${ready.length})</div>` + ready.map(renderHold).join('')
      : '';
      
    $('#holdsWaiting').innerHTML = waiting.length > 0
      ? `<div class="holds-section-title">⏳ Waiting (${waiting.length})</div>` + waiting.map(renderHold).join('')
      : '';
      
    $('#holdsOther').innerHTML = other.length > 0
      ? `<div class="holds-section-title">📋 Other (${other.length})</div>` + other.map(renderHold).join('')
      : '';
    
    $('#holdsLoading').style.display = 'none';
    $('#holdsContent').style.display = 'block';
    
  } catch (e) {
    console.error('Failed to load holds:', e);
    toast('Failed to load holds', 'error');
    $('#holdsLoading').style.display = 'none';
  }
}

// Render a single hold card
function renderHold(hold) {
  const statusClass = hold.RequestStatusDescription === 'Held' ? 'ready' 
    : hold.RequestStatusDescription === 'Pending' ? 'pending'
    : 'shipped';
  
  const canCancel = ['Active', 'Pending', 'Shipped', 'Held'].includes(hold.RequestStatusDescription);
  
  // Build cover URL from the movies array if we have the barcode
  let coverSrc = NO_COVER;
  if (hold.ItemBarcode) {
    const movie = movies.find(m => m.barcode === hold.ItemBarcode);
    if (movie && movie.cover) {
      coverSrc = movie.cover;
    }
  }
  
  return `
    <div class="hold-card">
      <img src="${coverSrc}" class="hold-poster" onerror="this.src='${NO_COVER}'" alt="${esc(hold.BrowseTitle)}">
      <div class="hold-details">
        <div class="hold-title">${esc(hold.BrowseTitle)}</div>
        <div class="hold-meta">📍 Pickup: ${esc(hold.PickupBranchName)}</div>
        <div class="hold-meta">📅 Requested: ${formatDate(hold.StatusDate)}</div>
        ${hold.ItemBarcode ? `<div class="hold-meta">🏷️ Barcode: ${esc(hold.ItemBarcode)}</div>` : ''}
        <span class="hold-status ${statusClass}">${esc(hold.RequestStatusDescription)}</span>
        ${canCancel ? `
          <div class="hold-actions">
            <button class="btn btn-gray" onclick="cancelHold(${hold.RequestID})">Cancel Hold</button>
          </div>
        ` : ''}
      </div>
    </div>
  `;
}

// Cancel a hold
async function cancelHold(holdRequestId) {
  if (!confirm('Are you sure you want to cancel this hold?')) return;
  
  showLoading('Cancelling hold...', 'Please wait');
  
  try {
    console.log('Cancelling hold:', holdRequestId, 'for patron:', currentUser.barcode);
    
    const res = await fetch('api/cancel-hold.php', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        holdRequestId: holdRequestId,
        patronBarcode: currentUser.barcode
      })
    });
    
    console.log('Cancel response status:', res.status);
    
    const responseText = await res.text();
    console.log('Cancel response text:', responseText);
    
    let data;
    try {
      data = JSON.parse(responseText);
    } catch (e) {
      console.error('Failed to parse cancel response:', e);
      hideLoading();
      toast('Cancel failed (invalid response)', 'error');
      return;
    }
    
    console.log('Cancel response data:', data);
    
    hideLoading();
    
    if (data.ok) {
      toast('Hold cancelled successfully', 'success');
      loadHolds(); // Reload holds
    } else {
      toast('Failed to cancel: ' + (data.error || 'Unknown error'), 'error');
    }
  } catch (e) {
    hideLoading();
    console.error('Cancel hold error:', e);
    toast('Failed to cancel hold', 'error');
  }
}

// Format date
function formatDate(dateStr) {
  if (!dateStr) return '—';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

// Request movie
async function requestMovie(type) {
  if (!currentMovie?.barcode) {
    toast('Movie not selected', 'error');
    return;
  }

  const isAvailable = currentMovie.available === true;

  if (type === 'now' && !isAvailable) {
    toast('This DVD is checked out. Place a hold to reserve it.', 'error');
    return;
  }

  if (type === 'now' && !currentUser) {
    closeMovie();
    showGetNowLogin();
    return;
  }

  if (type === 'hold') {
    if (!currentUser || !currentUser.barcode || currentUser.nameOnly) {
      _pendingHoldAfterLogin = true;
      closeMovie();
      showHoldLogin();
      return;
    }
  }
  
  if (patronStatus && !patronStatus.canCheckout && type === 'now' && currentUser?.barcode) {
    let message = '⚠️ Cannot check out DVD. ';
    if (patronStatus.blockReasons && patronStatus.blockReasons.length > 0) {
      message += patronStatus.blockReasons.join('. ');
    }
    toast(message, 'error');
    return;
  }
  
  if (type === 'hold') {
    showLoading('Placing hold...', 'This may take a few moments');
  } else {
    showLoading('Submitting request...', 'Please wait');
  }
  
  try {
    if (type === 'hold') {
      try {
        const holdBody = {
          patronBarcode: currentUser.barcode,
          itemBarcode: currentMovie.barcode,
          dvdId: currentMovie.dvdId ?? null
        };
        if (currentMovie.bibRecordId) {
          holdBody.bibRecordId = currentMovie.bibRecordId;
        }

        const holdRes = await fetch('api/hold.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(holdBody)
        });

        const responseText = await holdRes.text();
        let holdData;
        try {
          holdData = JSON.parse(responseText);
        } catch (parseError) {
          hideLoading();
          toast('Hold system error (invalid response)', 'error');
          return;
        }

        if (!holdRes.ok || !holdData.ok) {
          hideLoading();
          toast('Hold failed: ' + (holdData.error || 'Unknown error'), 'error');
          return;
        }
      } catch (e) {
        hideLoading();
        toast('Hold system unavailable', 'error');
        return;
      }
    }

    const reqData = {
      movie: {
        barcode: currentMovie.barcode,
        dvdId: currentMovie.dvdId ?? null,
        title: currentMovie.title,
        callNumber: currentMovie.callNumber,
        cover: currentMovie.cover,
        bibRecordId: currentMovie.bibRecordId
      },
      patron: {
        barcode: currentUser.barcode,
        name: currentUser.name,
        id: currentUser.id
      },
      type: type
    };
    
    const res = await fetch('api/requests.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(reqData)
    });
    
    const data = await res.json();
    
    if (!data.ok) {
      hideLoading();
      toast('Request failed: ' + (data.error || 'Unknown error'), 'error');
      return;
    }
    
    hideLoading();
    closeMovie();
    
    // PHASE 1: Refresh patron status after checkout
    if (type === 'now' && currentUser) {
      try {
        const statusRes = await fetch(`api/patron-status.php?barcode=${encodeURIComponent(currentUser.barcode)}`);
        const statusData = await statusRes.json();
        if (statusData.ok) {
          patronStatus = statusData;
          $('#dvdCount').textContent = statusData.checkouts.dvds;
          
          const counter = $('#checkoutCounter');
          counter.classList.remove('warning', 'blocked');
          if (statusData.checkouts.dvds >= 5) {
            counter.classList.add('blocked');
          } else if (statusData.checkouts.dvds >= 4) {
            counter.classList.add('warning');
          }
        }
      } catch (e) {
        console.error('Failed to refresh patron status:', e);
      }
    }
    
    if (type === 'hold') {
      $('#confirmIcon').textContent = '📌';
      $('#confirmTitle').textContent = 'Hold Placed!';
      $('#confirmMsg').textContent = `"${currentMovie.title}" is on hold. We'll let you know when it's ready.`;
    } else {
      $('#confirmIcon').textContent = '✅';
      $('#confirmTitle').textContent = 'Request Sent!';
      $('#confirmMsg').textContent = `Staff will pull "${currentMovie.title}" for you. Please wait at the front desk.`;
    }
    
    $('#confirmModal').classList.add('visible');
    
  } catch (e) {
    hideLoading();
    console.error('Request error:', e);
    toast('Request failed', 'error');
  }
}

// Event setup
function setupEvents() {
  // On-screen keyboard setup (defined first so oskShow/oskHide are available)
  const osk = $('#oskContainer');
  let oskTarget = $('#searchInput'); // which input the OSK types into
  let oskMode = 'search'; // 'search' or 'getNow'

  function oskShow(mode) {
    oskMode = mode || 'search';
    oskTarget = oskMode === 'getNow' ? $('#getNowInput') : $('#searchInput');
    osk.classList.add('visible');
    if (oskMode === 'search') $('.main').classList.add('osk-open');
    // Show/hide Done button
    $('#oskDoneBtn').style.display = oskMode === 'getNow' ? 'flex' : 'none';
    if (oskMode === 'getNow') document.body.classList.add('getnow-osk-open');
  }
  function oskHide() {
    osk.classList.remove('visible');
    $('.main').classList.remove('osk-open');
    document.body.classList.remove('getnow-osk-open');
    $('#oskDoneBtn').style.display = 'none';
    oskMode = 'search';
    oskTarget = $('#searchInput');
  }

  // searchTimeout declared here so OSK handlers can use it
  let searchTimeout;

  $$('.osk-key').forEach(key => {
    key.addEventListener('pointerdown', (e) => {
      e.preventDefault();
      key.classList.add('pressed');
      const k = key.dataset.key;
      const input = oskTarget;
      const start = input.selectionStart;
      const end = input.selectionEnd;
      const val = input.value;
      if (k === 'BACKSPACE') {
        if (start !== end) {
          input.value = val.slice(0, start) + val.slice(end);
          input.setSelectionRange(start, start);
        } else if (start > 0) {
          input.value = val.slice(0, start - 1) + val.slice(end);
          input.setSelectionRange(start - 1, start - 1);
        }
      } else if (k === 'CLEAR') {
        input.value = '';
      } else if (k === 'SPACE') {
        input.value = val.slice(0, start) + ' ' + val.slice(end);
        input.setSelectionRange(start + 1, start + 1);
      } else if (k === 'APOSTROPHE') {
        input.value = val.slice(0, start) + "'" + val.slice(end);
        input.setSelectionRange(start + 1, start + 1);
      } else if (k === 'COLON') {
        input.value = val.slice(0, start) + ':' + val.slice(end);
        input.setSelectionRange(start + 1, start + 1);
      } else {
        input.value = val.slice(0, start) + k + val.slice(end);
        input.setSelectionRange(start + 1, start + 1);
      }
      if (oskMode === 'search') {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => { doSearch(input.value); resetIdleTimer(); }, 150);
      } else if (oskMode === 'getNow' && k === 'DONE') {
        oskHide();
        doGetNowLogin();
      }
    });
    key.addEventListener('pointerup', () => key.classList.remove('pressed'));
    key.addEventListener('pointerleave', () => key.classList.remove('pressed'));
  });

  // Get Now keyboard toggle button
  $('#btnGetNowKeyboard').onclick = () => {
    $('#getNowNumpad').style.display = 'none';
    $('#btnGetNowKeyboard').style.display = 'none';
    $('#getNowInput').placeholder = 'Type your name';
    $('#getNowInput').value = '';
    $('#getNowError').textContent = '';
    if (window.matchMedia('(orientation: portrait)').matches) {
      oskShow('getNow');
    }
    setTimeout(() => $('#getNowInput').focus(), 100);
  };

  // Tab navigation
  $$('.nav-btn').forEach(btn => {
    btn.onclick = () => {
      $$('.nav-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      
      $$('.section').forEach(s => s.classList.remove('active'));
      $(`#tab${btn.dataset.tab.charAt(0).toUpperCase() + btn.dataset.tab.slice(1)}`).classList.add('active');
      
      $('#searchBar').classList.toggle('visible', btn.dataset.tab === 'search');
      if (btn.dataset.tab === 'search') {
        setTimeout(() => $('#searchInput').focus(), 100);
        if (window.matchMedia('(orientation: portrait)').matches) oskShow('search');
      } else {
        oskHide();
      }
      
      // Load holds when holds tab is clicked
      if (btn.dataset.tab === 'holds' && currentUser) {
        loadHolds();
      }
      
      resetIdleTimer();
    };
  });
  
  // Search with debounce
  $('#searchInput').addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      doSearch(e.target.value);
      resetIdleTimer();
    }, 300);
  });

  // Movie modal
  $('#btnCloseMovie').onclick = closeMovie;
  $('#btnRequestNow').onclick = () => requestMovie('now');
  $('#btnPlaceHold').onclick = () => requestMovie('hold');
  $('#btnAskAi').onclick = toggleAiAskPanel;
  $('#btnAiAskSubmit').onclick = submitAiQuestion;
  $('#aiAskInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') submitAiQuestion();
  });
  $('#movieModal').onclick = e => { if (e.target.id === 'movieModal') closeMovie(); };
  
  // Login
  $('#btnLogin').onclick = showLogin;
  $('#btnLogout').onclick = doLogout;
  $('#btnCancelLogin').onclick = closeLogin;
  $('#loginModal').onclick = e => { if (e.target.id === 'loginModal') closeLogin(); };
  
  // Numpad (card-only login)
  $$('.num-btn').forEach(b => {
    b.onclick = () => {
      const n = b.dataset.n;
      if (!n) return;
      const inp = $('#barcodeInput');
      if (n === '⌫') inp.value = inp.value.slice(0, -1);
      else if (n === 'GO') doLogin();
      else inp.value += n;
      inp.focus();
    };
  });
  
  $('#barcodeInput').onkeypress = e => { if (e.key === 'Enter') doLogin(); };

  // Get Now modal
  $('#btnCancelGetNow').onclick = closeGetNowModal;
  $('#getNowModal').onclick = e => { if (e.target.id === 'getNowModal') closeGetNowModal(); };
  $('#getNowInput').onkeypress = e => { if (e.key === 'Enter') doGetNowLogin(); };
  $('#getNowInput').oninput = () => {
    // Toggle numpad: show for digits-only input, hide when letters typed
    const val = $('#getNowInput').value;
    const isNum = /^\d*$/.test(val);
    $('#getNowNumpad').style.display = isNum ? 'grid' : 'none';
  };

  $$('[data-gn]').forEach(b => {
    b.onclick = () => {
      const n = b.dataset.gn;
      const inp = $('#getNowInput');
      if (n === '⌫') inp.value = inp.value.slice(0, -1);
      else if (n === 'GO') doGetNowLogin();
      else inp.value += n;
      inp.focus();
      // Keep numpad visible while typing numbers
      $('#getNowNumpad').style.display = 'grid';
    };
  });
  
  // Confirmation
  $('#btnConfirmOK').onclick = () => $('#confirmModal').classList.remove('visible');
  
  // Timeout
  $('#btnStayHere').onclick = () => { hideTimeout(); resetIdleTimer(); };
  
  // Global activity
  document.addEventListener('click', resetIdleTimer);
  document.addEventListener('touchstart', resetIdleTimer);
}

// Idle timeout
function resetIdleTimer() {
  if (!currentUser) return;
  
  clearTimeout(idleTimer);
  clearInterval(warnInterval);
  hideTimeout();
  
  idleTimer = setTimeout(showTimeout, TIMEOUT_IDLE);
}

function showTimeout() {
  let sec = TIMEOUT_WARN / 1000;
  $('#timeoutNum').textContent = sec;
  $('#timeoutBar').classList.add('visible');
  
  warnInterval = setInterval(() => {
    sec--;
    $('#timeoutNum').textContent = sec;
    if (sec <= 0) {
      hideTimeout();
      doLogout();
      toast('Session ended');
    }
  }, 1000);
}

function hideTimeout() {
  $('#timeoutBar').classList.remove('visible');
  clearInterval(warnInterval);
}

// Toast
function toast(msg, type = '') {
  const t = $('#toast');
  t.textContent = msg;
  t.className = 'toast visible ' + type;
  setTimeout(() => t.classList.remove('visible'), 3000);
}

// Escape HTML


// Start
init();
</script>
<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
  <div class="loading-spinner"></div>
  <div class="loading-text" id="loadingText">Loading...</div>
  <div class="loading-subtext" id="loadingSubtext"></div>
</div>

</body>
</html>
