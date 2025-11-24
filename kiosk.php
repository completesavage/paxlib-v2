<?php
require_once __DIR__ . '/config.php';
$no_cover = defined('NO_COVER_PATH') ? NO_COVER_PATH : '/img/no-cover.svg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Paxton Carnegie Library — Movie Kiosk</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #0a0f0d;
  --bg-card: #141a17;
  --bg-elevated: #1a211d;
  --bg-glass: rgba(20, 26, 23, 0.85);
  --text: #f0f4f1;
  --text-muted: #8a9a90;
  --accent: #4ade80;
  --accent-glow: rgba(74, 222, 128, 0.3);
  --accent-soft: rgba(74, 222, 128, 0.15);
  --gold: #fbbf24;
  --border: rgba(138, 154, 144, 0.2);
  --shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
  --radius: 20px;
  --radius-sm: 12px;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  -webkit-tap-highlight-color: transparent;
  -webkit-touch-callout: none;
  -webkit-user-select: none;
  user-select: none;
}

html, body {
  height: 100%;
  overflow: hidden;
}

body {
  font-family: 'Outfit', sans-serif;
  background: var(--bg);
  color: var(--text);
  line-height: 1.5;
}

/* Animated background gradient */
.bg-gradient {
  position: fixed;
  inset: 0;
  background: 
    radial-gradient(ellipse 80% 50% at 20% 0%, rgba(74, 222, 128, 0.08) 0%, transparent 50%),
    radial-gradient(ellipse 60% 40% at 80% 100%, rgba(251, 191, 36, 0.05) 0%, transparent 50%),
    var(--bg);
  z-index: -1;
}

/* Main layout */
.kiosk {
  height: 100vh;
  display: flex;
  flex-direction: column;
  padding: 24px;
  gap: 20px;
}

/* Header */
.header {
  text-align: center;
  padding: 16px 0;
  flex-shrink: 0;
}

.logo {
  font-family: 'Playfair Display', serif;
  font-size: clamp(28px, 5vw, 42px);
  font-weight: 700;
  background: linear-gradient(135deg, var(--text) 0%, var(--accent) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.02em;
}

.tagline {
  color: var(--text-muted);
  font-size: 14px;
  font-weight: 400;
  margin-top: 4px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

/* User greeting bar */
.user-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--bg-elevated);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 12px 20px;
  flex-shrink: 0;
  opacity: 0;
  transform: translateY(-10px);
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.user-bar.visible {
  opacity: 1;
  transform: translateY(0);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--accent-soft);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.user-name {
  font-weight: 600;
  font-size: 16px;
}

.user-subtitle {
  font-size: 13px;
  color: var(--text-muted);
}

.btn-logout {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text-muted);
  padding: 10px 20px;
  border-radius: 999px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-logout:active {
  transform: scale(0.95);
  background: var(--bg-card);
}

/* Navigation tabs */
.nav-tabs {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
  overflow-x: auto;
  padding: 4px;
  scrollbar-width: none;
}

.nav-tabs::-webkit-scrollbar {
  display: none;
}

.nav-tab {
  flex: 1;
  min-width: 100px;
  padding: 14px 20px;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text-muted);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  text-align: center;
  white-space: nowrap;
}

.nav-tab.active {
  background: var(--accent);
  border-color: var(--accent);
  color: var(--bg);
  box-shadow: 0 0 30px var(--accent-glow);
}

.nav-tab:not(.active):active {
  transform: scale(0.97);
  background: var(--bg-elevated);
}

/* Search bar */
.search-bar {
  position: relative;
  flex-shrink: 0;
}

.search-input {
  width: 100%;
  padding: 18px 24px 18px 56px;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  color: var(--text);
  font-size: 16px;
  font-family: inherit;
  outline: none;
  transition: all 0.25s;
}

.search-input::placeholder {
  color: var(--text-muted);
}

.search-input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 4px var(--accent-soft);
}

.search-icon {
  position: absolute;
  left: 20px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  font-size: 20px;
  pointer-events: none;
}

/* Content area */
.content {
  flex: 1;
  overflow: hidden;
  position: relative;
}

.section {
  display: none;
  height: 100%;
  flex-direction: column;
  animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.section.active {
  display: flex;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.section-title {
  font-family: 'Playfair Display', serif;
  font-size: 22px;
  font-weight: 600;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

.section-title .icon {
  font-size: 24px;
}

/* Movie grid */
.movie-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  overflow-y: auto;
  padding: 4px;
  padding-bottom: 24px;
  scrollbar-width: thin;
  scrollbar-color: var(--border) transparent;
}

.movie-grid::-webkit-scrollbar {
  width: 6px;
}

.movie-grid::-webkit-scrollbar-track {
  background: transparent;
}

.movie-grid::-webkit-scrollbar-thumb {
  background: var(--border);
  border-radius: 3px;
}

/* Horizontal scroll rail */
.movie-rail {
  display: flex;
  gap: 16px;
  overflow-x: auto;
  padding: 4px 4px 20px;
  scroll-snap-type: x mandatory;
  scrollbar-width: none;
}

.movie-rail::-webkit-scrollbar {
  display: none;
}

.movie-rail .movie-card {
  flex: 0 0 calc(33.333% - 11px);
  scroll-snap-align: start;
}

/* Movie cards */
.movie-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  position: relative;
}

.movie-card:active {
  transform: scale(0.97);
}

.movie-card:hover {
  border-color: var(--accent);
  box-shadow: var(--shadow), 0 0 40px var(--accent-glow);
  transform: translateY(-4px);
}

.movie-poster {
  width: 100%;
  aspect-ratio: 2/3;
  object-fit: cover;
  background: var(--bg-elevated);
  display: block;
}

.movie-info {
  padding: 14px;
}

.movie-title {
  font-weight: 600;
  font-size: 14px;
  line-height: 1.3;
  margin-bottom: 6px;
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
  font-size: 11px;
  font-weight: 600;
  padding: 4px 8px;
  background: var(--accent-soft);
  color: var(--accent);
  border-radius: 6px;
  text-transform: uppercase;
}

.movie-status {
  font-size: 12px;
  color: var(--text-muted);
}

.movie-status.available {
  color: var(--accent);
}

.movie-status.checked-out {
  color: var(--gold);
}

/* Featured card (larger) */
.movie-card.featured {
  grid-column: span 2;
}

.movie-card.featured .movie-poster {
  aspect-ratio: 4/3;
}

/* Loading skeleton */
.skeleton {
  background: linear-gradient(90deg, var(--bg-elevated) 0%, var(--bg-card) 50%, var(--bg-elevated) 100%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Modal overlay */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(8px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-overlay.visible {
  opacity: 1;
  visibility: visible;
}

.modal {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  transform: scale(0.9) translateY(20px);
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-overlay.visible .modal {
  transform: scale(1) translateY(0);
}

.modal-poster {
  width: 100%;
  aspect-ratio: 16/9;
  object-fit: cover;
  background: var(--bg-elevated);
}

.modal-content {
  padding: 24px;
}

.modal-title {
  font-family: 'Playfair Display', serif;
  font-size: 26px;
  font-weight: 700;
  margin-bottom: 8px;
  line-height: 1.2;
}

.modal-subtitle {
  color: var(--text-muted);
  font-size: 14px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.modal-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: var(--accent-soft);
  color: var(--accent);
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
}

.modal-badge.gold {
  background: rgba(251, 191, 36, 0.15);
  color: var(--gold);
}

.modal-details {
  display: grid;
  gap: 12px;
  margin-bottom: 24px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 12px 16px;
  background: var(--bg-elevated);
  border-radius: var(--radius-sm);
}

.detail-label {
  color: var(--text-muted);
  font-size: 13px;
}

.detail-value {
  font-weight: 600;
  font-size: 14px;
}

.modal-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.btn {
  padding: 18px 24px;
  border-radius: var(--radius);
  font-size: 16px;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  text-align: center;
}

.btn:active {
  transform: scale(0.97);
}

.btn-primary {
  background: var(--accent);
  color: var(--bg);
  box-shadow: 0 4px 20px var(--accent-glow);
}

.btn-primary:hover {
  box-shadow: 0 8px 30px var(--accent-glow);
}

.btn-secondary {
  background: var(--bg-elevated);
  color: var(--text);
  border: 1px solid var(--border);
}

.btn-secondary:hover {
  border-color: var(--text-muted);
}

/* Login modal */
.login-modal .modal-content {
  text-align: center;
}

.login-title {
  font-family: 'Playfair Display', serif;
  font-size: 24px;
  margin-bottom: 8px;
}

.login-subtitle {
  color: var(--text-muted);
  margin-bottom: 24px;
  font-size: 14px;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  text-align: left;
}

.form-label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-muted);
  margin-bottom: 8px;
}

.form-input {
  width: 100%;
  padding: 16px 20px;
  background: var(--bg-elevated);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text);
  font-size: 16px;
  font-family: inherit;
  outline: none;
  transition: all 0.2s;
}

.form-input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 4px var(--accent-soft);
}

.form-input::placeholder {
  color: var(--text-muted);
}

.form-error {
  color: #f87171;
  font-size: 13px;
  margin-top: 8px;
  display: none;
}

.form-error.visible {
  display: block;
}

/* Numpad for barcode entry */
.numpad {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-top: 16px;
}

.numpad-btn {
  padding: 20px;
  background: var(--bg-elevated);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text);
  font-size: 24px;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  transition: all 0.15s;
}

.numpad-btn:active {
  transform: scale(0.95);
  background: var(--accent-soft);
}

.numpad-btn.wide {
  grid-column: span 2;
}

.numpad-btn.accent {
  background: var(--accent);
  color: var(--bg);
  border-color: var(--accent);
}

/* Empty state */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  text-align: center;
  padding: 40px;
  color: var(--text-muted);
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-title {
  font-size: 18px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 8px;
}

/* Inactivity warning */
.timeout-warning {
  position: fixed;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%) translateY(100px);
  background: var(--gold);
  color: var(--bg);
  padding: 16px 32px;
  border-radius: var(--radius);
  font-weight: 600;
  z-index: 2000;
  opacity: 0;
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 10px 40px rgba(251, 191, 36, 0.3);
}

.timeout-warning.visible {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}

/* Toast notifications */
.toast {
  position: fixed;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%) translateY(100px);
  background: var(--bg-elevated);
  border: 1px solid var(--border);
  color: var(--text);
  padding: 16px 24px;
  border-radius: var(--radius);
  font-weight: 500;
  z-index: 2000;
  opacity: 0;
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  display: flex;
  align-items: center;
  gap: 12px;
}

.toast.visible {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}

.toast.success {
  border-color: var(--accent);
  background: rgba(74, 222, 128, 0.1);
}

.toast.error {
  border-color: #f87171;
  background: rgba(248, 113, 113, 0.1);
}

/* Responsive adjustments for portrait 16:9 */
@media (max-width: 600px) {
  .kiosk {
    padding: 16px;
    gap: 16px;
  }
  
  .movie-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
  
  .movie-card.featured {
    grid-column: span 2;
  }
  
  .nav-tab {
    padding: 12px 16px;
    font-size: 13px;
  }
}

@media (min-width: 800px) {
  .movie-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

/* Pulse animation for interactive elements */
@keyframes pulse {
  0%, 100% { box-shadow: 0 0 0 0 var(--accent-glow); }
  50% { box-shadow: 0 0 0 10px transparent; }
}

.pulse {
  animation: pulse 2s infinite;
}
</style>
</head>
<body>

<div class="bg-gradient"></div>

<div class="kiosk">
  <!-- Header -->
  <header class="header">
    <h1 class="logo">Paxton Carnegie Library</h1>
    <p class="tagline">Movie Collection</p>
  </header>

  <!-- User bar (shown when logged in) -->
  <div class="user-bar" id="userBar">
    <div class="user-info">
      <div class="user-avatar">👤</div>
      <div>
        <div class="user-name" id="userName">Guest</div>
        <div class="user-subtitle">Tap a movie to request it</div>
      </div>
    </div>
    <button class="btn-logout" id="btnLogout">Sign Out</button>
  </div>

  <!-- Navigation -->
  <nav class="nav-tabs">
    <button class="nav-tab active" data-section="featured">✨ Featured</button>
    <button class="nav-tab" data-section="all">🎬 All Movies</button>
    <button class="nav-tab" data-section="search">🔍 Search</button>
  </nav>

  <!-- Search bar (shown in search section) -->
  <div class="search-bar" id="searchBar" style="display: none;">
    <span class="search-icon">🔍</span>
    <input 
      type="text" 
      class="search-input" 
      id="searchInput" 
      placeholder="Search movies by title..."
      autocomplete="off"
    >
  </div>

  <!-- Content sections -->
  <main class="content">
    <!-- Featured section -->
    <section class="section active" id="sectionFeatured">
      <h2 class="section-title"><span class="icon">🌟</span> New & Recommended</h2>
      <div class="movie-rail" id="featuredRail"></div>
      
      <h2 class="section-title" style="margin-top: 24px;"><span class="icon">🎯</span> Popular This Week</h2>
      <div class="movie-rail" id="popularRail"></div>
    </section>

    <!-- All movies section -->
    <section class="section" id="sectionAll">
      <h2 class="section-title"><span class="icon">🎬</span> All Movies</h2>
      <div class="movie-grid" id="allMoviesGrid"></div>
    </section>

    <!-- Search section -->
    <section class="section" id="sectionSearch">
      <div class="movie-grid" id="searchResults"></div>
      <div class="empty-state" id="searchEmpty">
        <div class="empty-icon">🔍</div>
        <div class="empty-title">Search for movies</div>
        <p>Type a title to find movies in our collection</p>
      </div>
    </section>
  </main>
</div>

<!-- Movie detail modal -->
<div class="modal-overlay" id="movieModal">
  <div class="modal">
    <img class="modal-poster" id="modalPoster" src="" alt="Movie poster">
    <div class="modal-content">
      <h2 class="modal-title" id="modalTitle">Movie Title</h2>
      <div class="modal-subtitle">
        <span class="modal-badge" id="modalRating">PG</span>
        <span class="modal-badge gold" id="modalStatus">Available</span>
      </div>
      <div class="modal-details">
        <div class="detail-row">
          <span class="detail-label">Call Number</span>
          <span class="detail-value" id="modalCallNumber">—</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Barcode</span>
          <span class="detail-value" id="modalBarcode">—</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Location</span>
          <span class="detail-value" id="modalLocation">DVD Section</span>
        </div>
      </div>
      <div class="modal-actions">
        <button class="btn btn-primary" id="btnRequest">📋 Request This Movie</button>
        <button class="btn btn-secondary" id="btnCloseMovie">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Login modal -->
<div class="modal-overlay" id="loginModal">
  <div class="modal login-modal">
    <div class="modal-content">
      <h2 class="login-title">👋 Welcome!</h2>
      <p class="login-subtitle">Enter your library card to request movies</p>
      
      <div class="login-form">
        <div class="form-group">
          <label class="form-label">Your Name</label>
          <input type="text" class="form-input" id="inputName" placeholder="First name">
        </div>
        <div class="form-group">
          <label class="form-label">Library Card Number</label>
          <input type="text" class="form-input" id="inputBarcode" placeholder="Scan or enter your barcode" inputmode="numeric">
          <div class="form-error" id="loginError">Invalid library card. Please try again.</div>
        </div>
        
        <!-- Numpad for touch entry -->
        <div class="numpad" id="numpad">
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
          <button class="numpad-btn accent" data-num="submit">→</button>
        </div>
        
        <button class="btn btn-secondary" id="btnSkipLogin" style="margin-top: 16px;">Browse as Guest</button>
      </div>
    </div>
  </div>
</div>

<!-- Request confirmation modal -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal">
    <div class="modal-content" style="text-align: center;">
      <div style="font-size: 64px; margin-bottom: 16px;">✅</div>
      <h2 class="login-title">Request Sent!</h2>
      <p class="login-subtitle" id="confirmMessage">Staff will pull your movie shortly. Please wait at the front desk.</p>
      <button class="btn btn-primary" id="btnConfirmClose" style="margin-top: 24px;">Done</button>
    </div>
  </div>
</div>

<!-- Timeout warning -->
<div class="timeout-warning" id="timeoutWarning">
  ⏱️ Session ending in <span id="timeoutCount">30</span> seconds — touch to stay
</div>

<!-- Toast -->
<div class="toast" id="toast">
  <span id="toastIcon">✓</span>
  <span id="toastMessage">Success!</span>
</div>

<script>
const NO_COVER = '<?php echo htmlspecialchars($no_cover, ENT_QUOTES, 'UTF-8'); ?>';

// State
let movies = [];
let movieIndex = {};
let currentUser = null;
let currentMovie = null;
let inactivityTimer = null;
let warningTimer = null;
let countdownInterval = null;

const INACTIVITY_TIMEOUT = 90000; // 90 seconds
const WARNING_DURATION = 30000; // 30 second warning

// DOM elements
const $ = s => document.querySelector(s);
const $$ = s => Array.from(document.querySelectorAll(s));

// Initialize
async function init() {
  await loadMovies();
  renderFeatured();
  renderAllMovies();
  setupEventListeners();
  resetInactivityTimer();
  
  // Show login modal on start (optional - can be disabled)
  // showLoginModal();
}

// Load movies from API
async function loadMovies() {
  try {
    const res = await fetch('api/list.php');
    const data = await res.json();
    
    if (data.ok) {
      movies = data.items || [];
      movies.sort((a, b) => (a.title || '').localeCompare(b.title || ''));
      movieIndex = Object.fromEntries(movies.map(m => [m.barcode, m]));
    }
  } catch (err) {
    console.error('Failed to load movies:', err);
    showToast('Failed to load movies', 'error');
  }
}

// Render movie card HTML
function movieCard(movie, featured = false) {
  const cover = movie.cover || NO_COVER;
  const rating = movie.rating || '';
  
  return `
    <article class="movie-card ${featured ? 'featured' : ''}" data-barcode="${esc(movie.barcode)}">
      <img 
        class="movie-poster" 
        src="${esc(cover)}" 
        alt="${esc(movie.title)}"
        loading="lazy"
        onerror="this.src='${NO_COVER}'"
      >
      <div class="movie-info">
        <div class="movie-title">${esc(movie.title || 'Untitled')}</div>
        <div class="movie-meta">
          ${rating ? `<span class="movie-rating">${esc(rating)}</span>` : ''}
        </div>
      </div>
    </article>
  `;
}

// Render featured section
function renderFeatured() {
  // For "New & Recommended" - show random selection or newest
  const featured = shuffleArray([...movies]).slice(0, 9);
  $('#featuredRail').innerHTML = featured.map(m => movieCard(m)).join('');
  
  // For "Popular" - another random selection
  const popular = shuffleArray([...movies]).slice(0, 9);
  $('#popularRail').innerHTML = popular.map(m => movieCard(m)).join('');
  
  attachCardListeners('#featuredRail');
  attachCardListeners('#popularRail');
}

// Render all movies grid
function renderAllMovies() {
  $('#allMoviesGrid').innerHTML = movies.map(m => movieCard(m)).join('');
  attachCardListeners('#allMoviesGrid');
}

// Search movies
function searchMovies(query) {
  const q = query.toLowerCase().trim();
  if (!q) {
    $('#searchResults').innerHTML = '';
    $('#searchEmpty').style.display = 'flex';
    return;
  }
  
  const results = movies.filter(m => {
    const title = (m.title || '').toLowerCase();
    const barcode = (m.barcode || '').toLowerCase();
    return title.includes(q) || barcode.includes(q);
  });
  
  if (results.length === 0) {
    $('#searchResults').innerHTML = '';
    $('#searchEmpty').innerHTML = `
      <div class="empty-icon">😕</div>
      <div class="empty-title">No movies found</div>
      <p>Try a different search term</p>
    `;
    $('#searchEmpty').style.display = 'flex';
  } else {
    $('#searchEmpty').style.display = 'none';
    $('#searchResults').innerHTML = results.map(m => movieCard(m)).join('');
    attachCardListeners('#searchResults');
  }
}

// Attach click listeners to movie cards
function attachCardListeners(containerSelector) {
  $$(containerSelector + ' .movie-card').forEach(card => {
    card.addEventListener('click', () => {
      const barcode = card.dataset.barcode;
      openMovieModal(barcode);
    });
  });
}

// Open movie detail modal
async function openMovieModal(barcode) {
  currentMovie = movieIndex[barcode] || { barcode };
  
  // Set initial data
  $('#modalTitle').textContent = currentMovie.title || 'Loading...';
  $('#modalPoster').src = currentMovie.cover || NO_COVER;
  $('#modalRating').textContent = currentMovie.rating || 'NR';
  $('#modalBarcode').textContent = barcode;
  $('#modalCallNumber').textContent = '—';
  $('#modalStatus').textContent = 'Checking...';
  $('#modalLocation').textContent = 'DVD Section';
  
  $('#movieModal').classList.add('visible');
  
  // Fetch detailed info
  try {
    const res = await fetch(`api/item.php?barcode=${encodeURIComponent(barcode)}`);
    const data = await res.json();
    
    if (data.ok && data.data) {
      const d = data.data;
      const bib = d.BibInfo || {};
      
      if (data.cover) {
        $('#modalPoster').src = data.cover;
        currentMovie.cover = data.cover;
      }
      
      $('#modalCallNumber').textContent = bib.CallNumber || d.CallNumber || '—';
      $('#modalStatus').textContent = d.ItemStatusDescription || 'Unknown';
      $('#modalLocation').textContent = bib.AssignedBranch || 'DVD Section';
      
      // Update badge color based on status
      const status = (d.ItemStatusDescription || '').toLowerCase();
      if (status.includes('in') || status.includes('available')) {
        $('#modalStatus').classList.remove('gold');
        $('#modalStatus').style.background = 'var(--accent-soft)';
        $('#modalStatus').style.color = 'var(--accent)';
      } else {
        $('#modalStatus').classList.add('gold');
      }
    }
  } catch (err) {
    console.error('Failed to load movie details:', err);
  }
}

// Close movie modal
function closeMovieModal() {
  $('#movieModal').classList.remove('visible');
  currentMovie = null;
}

// Show login modal
function showLoginModal() {
  $('#inputName').value = '';
  $('#inputBarcode').value = '';
  $('#loginError').classList.remove('visible');
  $('#loginModal').classList.add('visible');
}

// Close login modal
function closeLoginModal() {
  $('#loginModal').classList.remove('visible');
}

// Handle login
async function handleLogin() {
  const name = $('#inputName').value.trim();
  const barcode = $('#inputBarcode').value.trim();
  
  if (!name) {
    $('#loginError').textContent = 'Please enter your name';
    $('#loginError').classList.add('visible');
    return;
  }
  
  if (!barcode) {
    $('#loginError').textContent = 'Please enter your library card number';
    $('#loginError').classList.add('visible');
    return;
  }
  
  // Optionally validate against Polaris API here
  // For now, just accept any input
  
  currentUser = { name, barcode };
  $('#userName').textContent = name;
  $('#userBar').classList.add('visible');
  closeLoginModal();
  showToast(`Welcome, ${name}!`, 'success');
  resetInactivityTimer();
}

// Handle logout
function handleLogout() {
  currentUser = null;
  $('#userBar').classList.remove('visible');
  showToast('Signed out', 'success');
}

// Request movie
async function requestMovie() {
  if (!currentMovie) return;
  
  // If not logged in, prompt login first
  if (!currentUser) {
    closeMovieModal();
    showLoginModal();
    return;
  }
  
  // Send request to staff panel
  try {
    const res = await fetch('api/request.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        movie: {
          barcode: currentMovie.barcode,
          title: currentMovie.title,
          callNumber: $('#modalCallNumber').textContent,
          cover: currentMovie.cover
        },
        patron: {
          name: currentUser.name,
          barcode: currentUser.barcode
        }
      })
    });
    
    const data = await res.json();
    
    if (data.ok) {
      closeMovieModal();
      $('#confirmMessage').textContent = `"${currentMovie.title}" has been requested. Staff will pull it for you shortly!`;
      $('#confirmModal').classList.add('visible');
    } else {
      showToast(data.error || 'Failed to send request', 'error');
    }
  } catch (err) {
    console.error('Request failed:', err);
    showToast('Failed to send request', 'error');
  }
}

// Setup event listeners
function setupEventListeners() {
  // Navigation tabs
  $$('.nav-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      const section = tab.dataset.section;
      switchSection(section);
    });
  });
  
  // Search
  $('#searchInput').addEventListener('input', (e) => {
    searchMovies(e.target.value);
    resetInactivityTimer();
  });
  
  // Movie modal
  $('#btnCloseMovie').addEventListener('click', closeMovieModal);
  $('#btnRequest').addEventListener('click', requestMovie);
  $('#movieModal').addEventListener('click', (e) => {
    if (e.target === $('#movieModal')) closeMovieModal();
  });
  
  // Login modal
  $('#btnSkipLogin').addEventListener('click', closeLoginModal);
  $('#loginModal').addEventListener('click', (e) => {
    if (e.target === $('#loginModal')) closeLoginModal();
  });
  
  // Numpad
  $$('.numpad-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const num = btn.dataset.num;
      const input = $('#inputBarcode');
      
      if (num === 'clear') {
        input.value = input.value.slice(0, -1);
      } else if (num === 'submit') {
        handleLogin();
      } else {
        input.value += num;
      }
      resetInactivityTimer();
    });
  });
  
  // Logout
  $('#btnLogout').addEventListener('click', handleLogout);
  
  // Confirmation modal
  $('#btnConfirmClose').addEventListener('click', () => {
    $('#confirmModal').classList.remove('visible');
  });
  
  // Timeout warning - touch to stay
  $('#timeoutWarning').addEventListener('click', () => {
    hideTimeoutWarning();
    resetInactivityTimer();
  });
  
  // Global touch/click resets inactivity
  document.addEventListener('click', resetInactivityTimer);
  document.addEventListener('touchstart', resetInactivityTimer);
  document.addEventListener('scroll', resetInactivityTimer, true);
}

// Switch active section
function switchSection(sectionId) {
  $$('.nav-tab').forEach(tab => {
    tab.classList.toggle('active', tab.dataset.section === sectionId);
  });
  
  $$('.section').forEach(section => {
    section.classList.toggle('active', section.id === `section${capitalize(sectionId)}`);
  });
  
  // Show/hide search bar
  $('#searchBar').style.display = sectionId === 'search' ? 'block' : 'none';
  
  if (sectionId === 'search') {
    setTimeout(() => $('#searchInput').focus(), 100);
  }
}

// Inactivity timeout
function resetInactivityTimer() {
  clearTimeout(inactivityTimer);
  clearTimeout(warningTimer);
  clearInterval(countdownInterval);
  hideTimeoutWarning();
  
  if (currentUser) {
    // Start warning after inactivity
    inactivityTimer = setTimeout(() => {
      showTimeoutWarning();
    }, INACTIVITY_TIMEOUT);
  }
}

function showTimeoutWarning() {
  let countdown = 30;
  $('#timeoutCount').textContent = countdown;
  $('#timeoutWarning').classList.add('visible');
  
  countdownInterval = setInterval(() => {
    countdown--;
    $('#timeoutCount').textContent = countdown;
    
    if (countdown <= 0) {
      clearInterval(countdownInterval);
      hideTimeoutWarning();
      handleLogout();
      showToast('Session ended due to inactivity', 'info');
    }
  }, 1000);
}

function hideTimeoutWarning() {
  $('#timeoutWarning').classList.remove('visible');
  clearInterval(countdownInterval);
}

// Toast notification
function showToast(message, type = 'success') {
  const toast = $('#toast');
  const icons = { success: '✓', error: '✕', info: 'ℹ' };
  
  $('#toastIcon').textContent = icons[type] || '✓';
  $('#toastMessage').textContent = message;
  
  toast.className = 'toast visible ' + type;
  
  setTimeout(() => {
    toast.classList.remove('visible');
  }, 3000);
}

// Utility functions
function esc(str) {
  const div = document.createElement('div');
  div.textContent = str || '';
  return div.innerHTML;
}

function capitalize(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

function shuffleArray(array) {
  const arr = [...array];
  for (let i = arr.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
  return arr;
}

// Start app
init();
</script>
</body>
</html>
