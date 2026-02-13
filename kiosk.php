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

/* Main content */
.main {
  height: calc(100vh - 190px);
  overflow-y: auto;
  padding: 20px 0;
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
</nav>

<div class="search-bar" id="searchBar">
  <input type="text" class="search-input" id="searchInput" placeholder="Type a movie title...">
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
      <div class="category-title">🎬 Recently Added</div>
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
          <span class="badge badge-status" id="modalStatus">Checking...</span>
        </div>
        <div class="detail-row"><span class="detail-label">Call #:</span> <span id="modalCall">—</span></div>
        <div class="detail-row"><span class="detail-label">Barcode:</span> <span id="modalBarcode">—</span></div>
        <div class="detail-row"><span class="detail-label">Location:</span> <span id="modalLocation">DVD Section</span></div>
      </div>
    </div>
    <div class="modal-body">
      <div class="modal-actions">
        <button class="btn btn-lg btn-primary" id="btnRequestNow">📋 Request Now — Staff will pull it</button>
        <button class="btn btn-lg btn-blue" id="btnPlaceHold">📌 Place on Hold — Pick up later</button>
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

let movies = [];
let movieMap = {};
let currentUser = null;
let currentMovie = null;
let idleTimer = null;
let warnInterval = null;

const $ = s => document.querySelector(s);
const $$ = s => [...document.querySelectorAll(s)];

// Initialize
async function init() {
  await loadMovies();
  renderAll();
  setupEvents();
}

// Load from cached movies API
async function loadMovies() {
  try {
    const res = await fetch('api/movies.php');
    const data = await res.json();
    if (data.ok) {
      movies = data.items || [];
      movieMap = Object.fromEntries(movies.map(m => [m.barcode, m]));
      console.log(`Loaded ${movies.length} movies`);
    }
  } catch (e) {
    console.error('Failed to load movies:', e);
  }
}

// Render movie card
function card(m) {
  const coverSrc = m.cover || NO_COVER;
  return `
    <div class="movie-card" data-barcode="${m.barcode}">
      <img class="movie-poster" src="${coverSrc}" onerror="this.src='${NO_COVER}'" loading="lazy">
      <div class="movie-info">
        <div class="movie-title">${esc(m.title)}</div>
        ${m.rating ? `<span class="movie-rating">${esc(m.rating)}</span>` : ''}
      </div>
    </div>
  `;
}

// Render all sections
function renderAll() {
  fetch('api/settings.php')
    .then(r => r.json())
    .then(data => {
      const s = data.settings || {};
      
      // Featured
      const featuredBarcodes = s.featured || [];
      const featured = featuredBarcodes.map(bc => movieMap[bc]).filter(Boolean);
      $('#rowFeatured').innerHTML = featured.length 
        ? featured.map(card).join('') 
        : movies.slice(0, 10).map(card).join('');
      
      // New arrivals
      const newBarcodes = s.newArrivals || [];
      const newArrivals = newBarcodes.map(bc => movieMap[bc]).filter(Boolean);
      $('#rowNew').innerHTML = newArrivals.length 
        ? newArrivals.map(card).join('') 
        : movies.slice(10, 20).map(card).join('');
      
      // Recent
      $('#rowRecent').innerHTML = movies.slice(20, 35).map(card).join('');
      
      // All movies grid
      $('#gridAll').innerHTML = movies.map(card).join('');
      
      attachClicks();
    })
    .catch(() => {
      $('#rowFeatured').innerHTML = movies.slice(0, 10).map(card).join('');
      $('#rowNew').innerHTML = movies.slice(10, 20).map(card).join('');
      $('#rowRecent').innerHTML = movies.slice(20, 35).map(card).join('');
      $('#gridAll').innerHTML = movies.map(card).join('');
      
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

  const results = movies.filter(m =>
    (m.title || '').toLowerCase().includes(q) ||
    String(m.barcode || '').toLowerCase().includes(q)
  );

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
  
  $('#modalTitle').textContent = currentMovie.title || 'Loading...';
  $('#modalPoster').src = currentMovie.cover || NO_COVER;
  $('#modalRating').textContent = currentMovie.rating || 'NR';
  $('#modalBarcode').textContent = barcode;
  $('#modalCall').textContent = currentMovie.callNumber || '—';
  $('#modalLocation').textContent = currentMovie.location || 'DVD Section';
  $('#modalStatus').textContent = 'Checking...';
  $('#modalStatus').className = 'badge badge-status';
  
  $('#movieModal').classList.add('visible');
  
  // Fetch fresh availability
  try {
    const res = await fetch(`api/movies.php?barcode=${encodeURIComponent(barcode)}`);
    const data = await res.json();
    
    if (data.ok && data.movie) {
      const m = data.movie;
      if (m.cover) $('#modalPoster').src = m.cover;
      $('#modalCall').textContent = m.callNumber || '—';
      $('#modalLocation').textContent = m.location || 'DVD Section';
      
      const status = m.status || 'Unknown';
      const isIn = status.toLowerCase().includes('in');
      $('#modalStatus').textContent = status;
      $('#modalStatus').className = 'badge badge-status ' + (isIn ? 'in' : 'out');
      
      currentMovie = m;
    }
  } catch (e) {
    console.error('Failed to load movie details:', e);
  }
}

function closeMovie() {
  $('#movieModal').classList.remove('visible');
}

// Login
function showLogin() {
  $('#barcodeInput').value = '';
  $('#loginError').classList.remove('visible');
  $('#loginModal').classList.add('visible');
  setTimeout(() => $('#barcodeInput').focus(), 100);
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

  try {
    const res = await fetch(`api/patron.php?barcode=${encodeURIComponent(barcode)}`);
    const data = await res.json();

    if (!data.ok || !data.patron) {
      $('#loginError').textContent = 'Card not found';
      $('#loginError').classList.add('visible');
      toast('Invalid library card', 'error');
      return;
    }

    currentUser = data.patron;

    $('#userName').textContent = `Hello, ${currentUser.name}!`;
    $('#userCard').textContent = `Card: ${currentUser.barcode}`;
    $('#userInfo').classList.add('visible');
    $('#btnLogin').style.display = 'none';
    $('#btnLogout').style.display = 'block';

    closeLogin();
    toast(`Welcome, ${currentUser.name}!`, 'success');
    resetIdleTimer();

  } catch (e) {
    console.error("Login API error:", e);

    $('#loginError').textContent = 'Login system unavailable';
    $('#loginError').classList.add('visible');
    toast('Login failed. Try again.', 'error');
  }
}


function doLogout() {
  currentUser = null;
  $('#userInfo').classList.remove('visible');
  $('#btnLogin').style.display = 'block';
  $('#btnLogout').style.display = 'none';
  hideTimeout();
  toast('Signed out');
}

// Request movie
async function requestMovie(type) {
  if (!currentUser) {
    closeMovie();
    showLogin();
    return;
  }
  
  try {
    const reqData = {
      movie: {
        barcode: currentMovie.barcode,
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
      toast('Request failed: ' + (data.error || 'Unknown error'), 'error');
      return;
    }
    
    if (type === 'hold' && currentMovie.bibRecordId) {
      try {
        await fetch('api/hold.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            patronBarcode: currentUser.barcode,
            bibRecordId: currentMovie.bibRecordId,
            itemBarcode: currentMovie.barcode
          })
        });
      } catch (e) {
        console.warn('Polaris hold failed:', e);
      }
    }
    
    closeMovie();
    
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
    console.error('Request error:', e);
    toast('Request failed', 'error');
  }
}

// Event setup
function setupEvents() {
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
      }
      
      resetIdleTimer();
    };
  });
  
  // Search with debounce
  let searchTimeout;
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
  $('#movieModal').onclick = e => { if (e.target.id === 'movieModal') closeMovie(); };
  
  // Login
  $('#btnLogin').onclick = showLogin;
  $('#btnLogout').onclick = doLogout;
  $('#btnCancelLogin').onclick = closeLogin;
  $('#loginModal').onclick = e => { if (e.target.id === 'loginModal') closeLogin(); };
  
  // Numpad
  $$('.num-btn').forEach(b => {
    b.onclick = () => {
      const n = b.dataset.n;
      const inp = $('#barcodeInput');
      if (n === '⌫') inp.value = inp.value.slice(0, -1);
      else if (n === 'GO') doLogin();
      else inp.value += n;
      inp.focus();
    };
  });
  
  $('#barcodeInput').onkeypress = e => { if (e.key === 'Enter') doLogin(); };
  
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
