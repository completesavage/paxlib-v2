<?php
require_once __DIR__ . '/config.php';
$no_cover = '/img/no-cover.svg';

// Load featured movies from settings
$settingsFile = __DIR__ . '/data/settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
$featuredBarcodes = $settings['featured'] ?? [];
$newArrivalBarcodes = $settings['newArrivals'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Paxton Carnegie Library — Browse Movies</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

html, body {
  height: 100%;
  overflow: hidden;
}

body {
  font-family: 'Open Sans', Arial, sans-serif;
  background: #f5f5f5;
  color: #333;
  line-height: 1.5;
  -webkit-tap-highlight-color: transparent;
  user-select: none;
}

/* Header */
.header {
  background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%);
  color: white;
  padding: 20px 30px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.logo {
  font-family: 'Merriweather', Georgia, serif;
  font-size: 28px;
  font-weight: 700;
}

.logo-sub {
  font-size: 16px;
  opacity: 0.9;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 15px;
}

.user-greeting {
  text-align: right;
  display: none;
}

.user-greeting.visible {
  display: block;
}

.user-name {
  font-weight: 600;
  font-size: 18px;
}

.user-hint {
  font-size: 13px;
  opacity: 0.9;
}

.btn {
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn:active {
  transform: scale(0.97);
}

.btn-white {
  background: white;
  color: #1b5e20;
}

.btn-white:hover {
  background: #e8f5e9;
}

.btn-outline {
  background: transparent;
  color: white;
  border: 2px solid white;
}

.btn-outline:hover {
  background: rgba(255,255,255,0.1);
}

/* Navigation */
.nav {
  background: white;
  border-bottom: 1px solid #ddd;
  padding: 0 30px;
  display: flex;
  gap: 0;
}

.nav-tab {
  padding: 18px 30px;
  font-size: 17px;
  font-weight: 600;
  color: #666;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.nav-tab:hover {
  color: #2e7d32;
  background: #f0f7f0;
}

.nav-tab.active {
  color: #1b5e20;
  border-bottom-color: #1b5e20;
}

/* Search bar */
.search-container {
  background: white;
  border-bottom: 1px solid #ddd;
  padding: 15px 30px;
  display: none;
}

.search-container.visible {
  display: block;
}

.search-box {
  display: flex;
  gap: 10px;
  max-width: 600px;
}

.search-input {
  flex: 1;
  padding: 14px 20px;
  font-size: 18px;
  border: 2px solid #ddd;
  border-radius: 8px;
  font-family: inherit;
  outline: none;
  transition: border-color 0.2s;
}

.search-input:focus {
  border-color: #2e7d32;
}

.search-input::placeholder {
  color: #999;
}

.btn-search {
  padding: 14px 28px;
  background: #2e7d32;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
}

.btn-search:hover {
  background: #1b5e20;
}

/* Main content */
.main {
  height: calc(100vh - 145px);
  overflow-y: auto;
  padding: 25px 30px;
}

.section {
  display: none;
}

.section.active {
  display: block;
}

.section-title {
  font-family: 'Merriweather', Georgia, serif;
  font-size: 22px;
  font-weight: 700;
  color: #1b5e20;
  margin-bottom: 15px;
  padding-bottom: 8px;
  border-bottom: 2px solid #c8e6c9;
}

/* Movie Grid - 4 columns */
.movie-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 30px;
}

/* Movie Card */
.movie-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: all 0.2s;
  border: 2px solid transparent;
}

.movie-card:hover {
  box-shadow: 0 4px 15px rgba(0,0,0,0.15);
  border-color: #2e7d32;
}

.movie-card:active {
  transform: scale(0.98);
}

.movie-poster {
  width: 100%;
  aspect-ratio: 2/3;
  object-fit: cover;
  background: #e8e8e8;
  display: block;
}

.movie-info {
  padding: 12px;
}

.movie-title {
  font-weight: 600;
  font-size: 14px;
  color: #333;
  margin-bottom: 5px;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.movie-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.movie-rating {
  background: #e8f5e9;
  color: #1b5e20;
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 700;
}

.movie-status {
  font-size: 13px;
  font-weight: 600;
}

.movie-status.available {
  color: #2e7d32;
}

.movie-status.out {
  color: #e65100;
}

/* Loading State */
.loading {
  text-align: center;
  padding: 40px;
  color: #666;
  font-size: 16px;
  grid-column: span 4;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e0e0e0;
  border-top-color: #2e7d32;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 15px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 50px;
  color: #666;
  grid-column: span 4;
}

.empty-icon {
  font-size: 50px;
  margin-bottom: 10px;
}

.empty-title {
  font-size: 20px;
  font-weight: 600;
  color: #333;
  margin-bottom: 5px;
}

/* Modal Overlay */
.modal-overlay {
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
  transition: all 0.25s;
}

.modal-overlay.visible {
  opacity: 1;
  visibility: visible;
}

/* Movie Detail Modal */
.modal {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 650px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 15px 50px rgba(0,0,0,0.3);
}

.modal-header {
  display: flex;
  gap: 20px;
  padding: 25px;
  background: #fafafa;
  border-bottom: 1px solid #eee;
}

.modal-poster {
  width: 150px;
  aspect-ratio: 2/3;
  object-fit: cover;
  border-radius: 6px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.2);
  flex-shrink: 0;
}

.modal-info {
  flex: 1;
}

.modal-title {
  font-family: 'Merriweather', Georgia, serif;
  font-size: 24px;
  font-weight: 700;
  color: #1b5e20;
  margin-bottom: 10px;
  line-height: 1.2;
}

.modal-badges {
  display: flex;
  gap: 8px;
  margin-bottom: 15px;
  flex-wrap: wrap;
}

.badge {
  padding: 5px 12px;
  border-radius: 5px;
  font-size: 13px;
  font-weight: 700;
}

.badge-rating {
  background: #e8f5e9;
  color: #1b5e20;
}

.badge-status {
  background: #e3f2fd;
  color: #1565c0;
}

.badge-status.available {
  background: #e8f5e9;
  color: #2e7d32;
}

.badge-status.out {
  background: #fff3e0;
  color: #e65100;
}

.modal-details {
  display: grid;
  gap: 8px;
}

.detail-row {
  display: flex;
  font-size: 14px;
}

.detail-label {
  color: #666;
  width: 90px;
  flex-shrink: 0;
}

.detail-value {
  font-weight: 600;
  color: #333;
}

.modal-body {
  padding: 25px;
}

.modal-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.btn-lg {
  padding: 16px 24px;
  font-size: 17px;
  border-radius: 8px;
  font-weight: 700;
}

.btn-primary {
  background: #2e7d32;
  color: white;
  border: none;
}

.btn-primary:hover {
  background: #1b5e20;
}

.btn-secondary {
  background: #f5f5f5;
  color: #333;
  border: 2px solid #ddd;
}

.btn-secondary:hover {
  background: #eee;
}

.btn-hold {
  background: #1565c0;
  color: white;
  border: none;
}

.btn-hold:hover {
  background: #0d47a1;
}

/* Login Modal */
.login-content {
  padding: 35px;
  text-align: center;
}

.login-title {
  font-family: 'Merriweather', Georgia, serif;
  font-size: 26px;
  color: #1b5e20;
  margin-bottom: 5px;
}

.login-subtitle {
  color: #666;
  font-size: 15px;
  margin-bottom: 25px;
}

.form-group {
  text-align: left;
  margin-bottom: 15px;
}

.form-label {
  display: block;
  font-weight: 600;
  margin-bottom: 6px;
  color: #333;
  font-size: 14px;
}

.form-input {
  width: 100%;
  padding: 14px 18px;
  font-size: 20px;
  border: 2px solid #ddd;
  border-radius: 8px;
  font-family: inherit;
  outline: none;
  transition: border-color 0.2s;
  text-align: center;
  letter-spacing: 2px;
}

.form-input:focus {
  border-color: #2e7d32;
}

.form-error {
  color: #d32f2f;
  font-size: 14px;
  margin-top: 8px;
  display: none;
}

.form-error.visible {
  display: block;
}

.scanner-hint {
  background: #e8f5e9;
  color: #1b5e20;
  padding: 12px;
  border-radius: 8px;
  font-size: 14px;
  margin-bottom: 15px;
}

/* Numpad */
.numpad {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  margin: 15px 0;
}

.numpad-btn {
  padding: 18px;
  font-size: 26px;
  font-weight: 700;
  background: #f5f5f5;
  border: 2px solid #ddd;
  border-radius: 8px;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.15s;
}

.numpad-btn:hover {
  background: #e8f5e9;
  border-color: #2e7d32;
}

.numpad-btn:active {
  background: #c8e6c9;
  transform: scale(0.97);
}

.numpad-btn.action {
  background: #2e7d32;
  color: white;
  border-color: #2e7d32;
}

.numpad-btn.action:hover {
  background: #1b5e20;
}

/* Confirmation Modal */
.confirm-content {
  padding: 45px 35px;
  text-align: center;
}

.confirm-icon {
  font-size: 70px;
  margin-bottom: 15px;
}

.confirm-title {
  font-family: 'Merriweather', Georgia, serif;
  font-size: 26px;
  color: #2e7d32;
  margin-bottom: 12px;
}

.confirm-message {
  font-size: 17px;
  color: #555;
  margin-bottom: 25px;
  line-height: 1.5;
}

/* Timeout Warning */
.timeout-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #ff9800;
  color: white;
  padding: 18px 30px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 18px;
  font-weight: 600;
  z-index: 2000;
  transform: translateY(100%);
  transition: transform 0.3s;
  box-shadow: 0 -4px 20px rgba(0,0,0,0.2);
}

.timeout-bar.visible {
  transform: translateY(0);
}

.timeout-bar button {
  padding: 12px 28px;
  font-size: 16px;
  font-weight: 700;
  background: white;
  color: #e65100;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

/* Toast */
.toast {
  position: fixed;
  bottom: 30px;
  left: 50%;
  transform: translateX(-50%) translateY(100px);
  background: #333;
  color: white;
  padding: 14px 28px;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  z-index: 3000;
  opacity: 0;
  transition: all 0.3s;
}

.toast.visible {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}

.toast.success {
  background: #2e7d32;
}

.toast.error {
  background: #d32f2f;
}

/* Scrollbar */
.main::-webkit-scrollbar {
  width: 10px;
}

.main::-webkit-scrollbar-track {
  background: #e0e0e0;
}

.main::-webkit-scrollbar-thumb {
  background: #2e7d32;
  border-radius: 5px;
}

/* Status count display */
.status-bar {
  background: white;
  padding: 10px 30px;
  font-size: 14px;
  color: #666;
  border-bottom: 1px solid #ddd;
}
</style>
</head>
<body>

<!-- Header -->
<header class="header">
  <div class="header-left">
    <div>
      <h1 class="logo">Paxton Carnegie Library</h1>
      <div class="logo-sub">DVD & Movie Collection</div>
    </div>
  </div>
  <div class="header-right">
    <div class="user-greeting" id="userGreeting">
      <div class="user-name" id="userName">Welcome!</div>
      <div class="user-hint">Tap a movie to request it</div>
    </div>
    <button class="btn btn-white" id="btnLogin">Sign In</button>
    <button class="btn btn-outline" id="btnLogout" style="display:none;">Sign Out</button>
  </div>
</header>

<!-- Navigation -->
<nav class="nav">
  <button class="nav-tab active" data-section="browse">Browse</button>
  <button class="nav-tab" data-section="all">All Movies</button>
  <button class="nav-tab" data-section="search">Search</button>
</nav>

<!-- Search -->
<div class="search-container" id="searchContainer">
  <div class="search-box">
    <input type="text" class="search-input" id="searchInput" placeholder="Type a movie title...">
    <button class="btn-search" id="btnSearch">Search</button>
  </div>
</div>

<!-- Status bar -->
<div class="status-bar" id="statusBar">Loading movies...</div>

<!-- Main Content -->
<main class="main">
  <!-- Browse Section -->
  <section class="section active" id="sectionBrowse">
    <h2 class="section-title">★ Staff Picks</h2>
    <div class="movie-grid" id="featuredGrid"></div>
    
    <h2 class="section-title">New Arrivals</h2>
    <div class="movie-grid" id="newArrivalsGrid"></div>
  </section>
  
  <!-- All Movies Section -->
  <section class="section" id="sectionAll">
    <h2 class="section-title">All Movies (A-Z)</h2>
    <div class="movie-grid" id="allMoviesGrid"></div>
  </section>
  
  <!-- Search Section -->
  <section class="section" id="sectionSearch">
    <h2 class="section-title" id="searchTitle">Search Results</h2>
    <div class="movie-grid" id="searchResults"></div>
    <div class="empty-state" id="searchEmpty">
      <div class="empty-icon">🔍</div>
      <div class="empty-title">Search for a movie</div>
      <p>Enter a title above to find movies</p>
    </div>
  </section>
</main>

<!-- Movie Detail Modal -->
<div class="modal-overlay" id="movieModal">
  <div class="modal">
    <div class="modal-header">
      <img class="modal-poster" id="modalPoster" src="" alt="">
      <div class="modal-info">
        <h2 class="modal-title" id="modalTitle">Movie Title</h2>
        <div class="modal-badges">
          <span class="badge badge-rating" id="modalRating">PG</span>
          <span class="badge badge-status" id="modalStatus">Available</span>
        </div>
        <div class="modal-details">
          <div class="detail-row">
            <span class="detail-label">Call #:</span>
            <span class="detail-value" id="modalCallNumber">—</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Barcode:</span>
            <span class="detail-value" id="modalBarcode">—</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Location:</span>
            <span class="detail-value" id="modalLocation">DVD Section</span>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-body">
      <div class="modal-actions">
        <button class="btn btn-lg btn-primary" id="btnRequestNow">
          📋 Request Now — Staff will pull it
        </button>
        <button class="btn btn-lg btn-hold" id="btnPlaceHold">
          📌 Place on Hold — Pick up later
        </button>
        <button class="btn btn-lg btn-secondary" id="btnCloseMovie">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Login Modal -->
<div class="modal-overlay" id="loginModal">
  <div class="modal" style="max-width: 420px;">
    <div class="login-content">
      <h2 class="login-title">Welcome!</h2>
      <p class="login-subtitle">Scan or enter your library card</p>
      
      <div class="scanner-hint">📱 Point barcode at scanner below</div>
      
      <div class="form-group">
        <input type="text" class="form-input" id="inputBarcode" placeholder="Library card #" autofocus>
        <div class="form-error" id="loginError">Card not found</div>
      </div>
      
      <div class="numpad">
        <button class="numpad-btn" data-num="1">1</button>
        <button class="numpad-btn" data-num="2">2</button>
        <button class="numpad-btn" data-num="3">3</button>
        <button class="numpad-btn" data-num="4">4</button>
        <button class="numpad-btn" data-num="5">5</button>
        <button class="numpad-btn" data-num="6">6</button>
        <button class="numpad-btn" data-num="7">7</button>
        <button class="numpad-btn" data-num="8">8</button>
        <button class="numpad-btn" data-num="9">9</button>
        <button class="numpad-btn" data-num="clear">⌫</button>
        <button class="numpad-btn" data-num="0">0</button>
        <button class="numpad-btn action" data-num="submit">GO</button>
      </div>
      
      <button class="btn btn-lg btn-secondary" id="btnCancelLogin" style="width: 100%;">Cancel</button>
    </div>
  </div>
</div>

<!-- Confirmation Modal -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal" style="max-width: 480px;">
    <div class="confirm-content">
      <div class="confirm-icon" id="confirmIcon">✅</div>
      <h2 class="confirm-title" id="confirmTitle">Request Sent!</h2>
      <p class="confirm-message" id="confirmMessage">Staff will pull your movie shortly.</p>
      <button class="btn btn-lg btn-primary" id="btnConfirmClose" style="width: 100%;">OK</button>
    </div>
  </div>
</div>

<!-- Timeout Warning -->
<div class="timeout-bar" id="timeoutBar">
  <span>⏱️ Are you still there? (<strong id="timeoutCount">15</strong>s)</span>
  <button id="btnStayLoggedIn">I'm Here!</button>
</div>

<!-- Toast -->
<div class="toast" id="toast"><span id="toastMessage"></span></div>

<script>
const NO_COVER = '<?php echo $no_cover; ?>';
const FEATURED_BARCODES = <?php echo json_encode($featuredBarcodes); ?>;
const NEW_ARRIVAL_BARCODES = <?php echo json_encode($newArrivalBarcodes); ?>;

let movies = [];
let movieIndex = {};
let currentUser = null;
let currentMovie = null;
let inactivityTimer = null;
let countdownInterval = null;

const $ = s => document.querySelector(s);
const $$ = s => Array.from(document.querySelectorAll(s));

async function init() {
  await loadMovies();
  renderAll();
  setupEvents();
  loadCoversInBackground();
}

async function loadMovies() {
  try {
    const res = await fetch('api/list.php');
    const data = await res.json();
    if (data.ok) {
      movies = data.items || [];
      movieIndex = Object.fromEntries(movies.map(m => [m.barcode, m]));
      $('#statusBar').textContent = `${movies.length} movies loaded`;
    }
  } catch (e) {
    $('#statusBar').textContent = 'Error loading movies';
  }
}

async function loadCoversInBackground() {
  for (let i = 0; i < movies.length; i++) {
    const movie = movies[i];
    try {
      const res = await fetch(`api/item.php?barcode=${encodeURIComponent(movie.barcode)}`);
      const data = await res.json();
      if (data.ok) {
        if (data.cover) movie.cover = data.cover;
        if (data.data) {
          movie.status = data.data.ItemStatusDescription;
          movie.callNumber = data.data.CallNumber;
        }
        // Update just this card's image
        const cards = $$(`[data-barcode="${movie.barcode}"] .movie-poster`);
        cards.forEach(img => {
          if (data.cover) img.src = data.cover;
        });
        const statusEls = $$(`[data-barcode="${movie.barcode}"] .movie-status`);
        statusEls.forEach(el => {
          const isIn = (movie.status || '').toLowerCase().includes('in');
          el.textContent = isIn ? '● In' : '● Out';
          el.className = 'movie-status ' + (isIn ? 'available' : 'out');
        });
      }
    } catch (e) {}
    
    // Update progress
    if (i % 10 === 0) {
      $('#statusBar').textContent = `Loading covers... ${i}/${movies.length}`;
    }
  }
  $('#statusBar').textContent = `${movies.length} movies ready`;
}

function movieCard(m) {
  const isIn = (m.status || '').toLowerCase().includes('in');
  return `
    <div class="movie-card" data-barcode="${m.barcode}">
      <img class="movie-poster" src="${m.cover || NO_COVER}" onerror="this.src='${NO_COVER}'">
      <div class="movie-info">
        <div class="movie-title">${esc(m.title)}</div>
        <div class="movie-meta">
          ${m.rating ? `<span class="movie-rating">${esc(m.rating)}</span>` : ''}
          <span class="movie-status ${isIn ? 'available' : 'out'}">${m.status ? (isIn ? '● In' : '● Out') : ''}</span>
        </div>
      </div>
    </div>`;
}

function renderAll() {
  // Staff Picks
  const featured = FEATURED_BARCODES.length 
    ? FEATURED_BARCODES.map(bc => movieIndex[bc]).filter(Boolean)
    : movies.slice(0, 8);
  $('#featuredGrid').innerHTML = featured.map(movieCard).join('') || '<div class="empty-state">No featured movies set</div>';
  
  // New Arrivals  
  const newArr = NEW_ARRIVAL_BARCODES.length
    ? NEW_ARRIVAL_BARCODES.map(bc => movieIndex[bc]).filter(Boolean)
    : movies.slice(8, 16);
  $('#newArrivalsGrid').innerHTML = newArr.map(movieCard).join('') || '<div class="empty-state">No new arrivals set</div>';
  
  // All Movies
  $('#allMoviesGrid').innerHTML = movies.map(movieCard).join('');
  
  attachCardListeners();
}

function searchMovies(q) {
  q = q.toLowerCase().trim();
  if (!q) {
    $('#searchResults').innerHTML = '';
    $('#searchEmpty').style.display = 'block';
    return;
  }
  const results = movies.filter(m => 
    (m.title || '').toLowerCase().includes(q) || 
    (m.barcode || '').includes(q)
  );
  $('#searchEmpty').style.display = results.length ? 'none' : 'block';
  $('#searchTitle').textContent = `Search Results (${results.length})`;
  $('#searchResults').innerHTML = results.map(movieCard).join('');
  if (!results.length) {
    $('#searchEmpty').innerHTML = '<div class="empty-icon">😕</div><div class="empty-title">No movies found</div>';
  }
  attachCardListeners();
}

function attachCardListeners() {
  $$('.movie-card').forEach(c => c.onclick = () => openModal(c.dataset.barcode));
}

async function openModal(barcode) {
  currentMovie = movieIndex[barcode] || { barcode };
  $('#modalTitle').textContent = currentMovie.title || 'Loading...';
  $('#modalPoster').src = currentMovie.cover || NO_COVER;
  $('#modalRating').textContent = currentMovie.rating || 'NR';
  $('#modalBarcode').textContent = barcode;
  $('#modalCallNumber').textContent = currentMovie.callNumber || '—';
  updateModalStatus(currentMovie.status);
  $('#movieModal').classList.add('visible');
  
  // Fetch fresh
  try {
    const res = await fetch(`api/item.php?barcode=${encodeURIComponent(barcode)}`);
    const data = await res.json();
    if (data.ok && data.data) {
      if (data.cover) $('#modalPoster').src = data.cover;
      $('#modalCallNumber').textContent = data.data.CallNumber || data.data.BibInfo?.CallNumber || '—';
      $('#modalLocation').textContent = data.data.BibInfo?.AssignedBranch || 'DVD Section';
      updateModalStatus(data.data.ItemStatusDescription);
      currentMovie.callNumber = data.data.CallNumber;
      currentMovie.cover = data.cover;
    }
  } catch (e) {}
}

function updateModalStatus(status) {
  const s = status || 'Unknown';
  const isIn = s.toLowerCase().includes('in');
  $('#modalStatus').textContent = s;
  $('#modalStatus').className = 'badge badge-status ' + (isIn ? 'available' : 'out');
}

function closeModal() {
  $('#movieModal').classList.remove('visible');
}

function showLogin() {
  $('#inputBarcode').value = '';
  $('#loginError').classList.remove('visible');
  $('#loginModal').classList.add('visible');
  setTimeout(() => $('#inputBarcode').focus(), 100);
}

function closeLogin() {
  $('#loginModal').classList.remove('visible');
}

function handleLogin() {
  const barcode = $('#inputBarcode').value.trim();
  if (!barcode) {
    $('#loginError').textContent = 'Enter your card number';
    $('#loginError').classList.add('visible');
    return;
  }
  currentUser = { barcode };
  $('#userName').textContent = 'Welcome!';
  $('#userGreeting').classList.add('visible');
  $('#btnLogin').style.display = 'none';
  $('#btnLogout').style.display = 'block';
  closeLogin();
  showToast('Signed in!', 'success');
  resetTimer();
}

function handleLogout() {
  currentUser = null;
  $('#userGreeting').classList.remove('visible');
  $('#btnLogin').style.display = 'block';
  $('#btnLogout').style.display = 'none';
  hideTimeout();
  showToast('Signed out');
}

async function requestMovie(type) {
  if (!currentUser) { closeModal(); showLogin(); return; }
  
  try {
    await fetch('api/request.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        movie: { barcode: currentMovie.barcode, title: currentMovie.title, callNumber: currentMovie.callNumber, cover: currentMovie.cover },
        patron: { barcode: currentUser.barcode },
        type
      })
    });
    closeModal();
    $('#confirmIcon').textContent = type === 'hold' ? '📌' : '✅';
    $('#confirmTitle').textContent = type === 'hold' ? 'Hold Placed!' : 'Request Sent!';
    $('#confirmMessage').textContent = type === 'hold' 
      ? `"${currentMovie.title}" is on hold. We'll let you know when ready.`
      : `Staff will pull "${currentMovie.title}" for you. Please wait at the desk.`;
    $('#confirmModal').classList.add('visible');
  } catch (e) {
    showToast('Request failed', 'error');
  }
}

function setupEvents() {
  $$('.nav-tab').forEach(t => t.onclick = () => {
    $$('.nav-tab').forEach(x => x.classList.remove('active'));
    t.classList.add('active');
    $$('.section').forEach(s => s.classList.remove('active'));
    $(`#section${t.dataset.section.charAt(0).toUpperCase() + t.dataset.section.slice(1)}`).classList.add('active');
    $('#searchContainer').classList.toggle('visible', t.dataset.section === 'search');
    if (t.dataset.section === 'search') setTimeout(() => $('#searchInput').focus(), 100);
    resetTimer();
  });
  
  $('#searchInput').oninput = () => { searchMovies($('#searchInput').value); resetTimer(); };
  $('#btnSearch').onclick = () => searchMovies($('#searchInput').value);
  
  $('#btnCloseMovie').onclick = closeModal;
  $('#btnRequestNow').onclick = () => requestMovie('now');
  $('#btnPlaceHold').onclick = () => requestMovie('hold');
  $('#movieModal').onclick = e => { if (e.target.id === 'movieModal') closeModal(); };
  
  $('#btnLogin').onclick = showLogin;
  $('#btnLogout').onclick = handleLogout;
  $('#btnCancelLogin').onclick = closeLogin;
  $('#loginModal').onclick = e => { if (e.target.id === 'loginModal') closeLogin(); };
  
  $$('.numpad-btn').forEach(b => b.onclick = () => {
    const n = b.dataset.num, inp = $('#inputBarcode');
    if (n === 'clear') inp.value = inp.value.slice(0, -1);
    else if (n === 'submit') handleLogin();
    else inp.value += n;
    inp.focus();
  });
  
  $('#inputBarcode').onkeypress = e => { if (e.key === 'Enter') handleLogin(); };
  $('#btnConfirmClose').onclick = () => $('#confirmModal').classList.remove('visible');
  $('#btnStayLoggedIn').onclick = () => { hideTimeout(); resetTimer(); };
  
  document.addEventListener('click', resetTimer);
  document.addEventListener('touchstart', resetTimer);
}

function resetTimer() {
  if (!currentUser) return;
  clearTimeout(inactivityTimer);
  clearInterval(countdownInterval);
  hideTimeout();
  inactivityTimer = setTimeout(showTimeout, 60000);
}

function showTimeout() {
  let c = 15;
  $('#timeoutCount').textContent = c;
  $('#timeoutBar').classList.add('visible');
  countdownInterval = setInterval(() => {
    c--;
    $('#timeoutCount').textContent = c;
    if (c <= 0) { hideTimeout(); handleLogout(); showToast('Session ended'); }
  }, 1000);
}

function hideTimeout() {
  $('#timeoutBar').classList.remove('visible');
  clearInterval(countdownInterval);
}

function showToast(msg, type = '') {
  const t = $('#toast');
  $('#toastMessage').textContent = msg;
  t.className = 'toast visible ' + type;
  setTimeout(() => t.classList.remove('visible'), 3000);
}

function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

init();
</script>
</body>
</html>
