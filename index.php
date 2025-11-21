<?php
require_once __DIR__ . '/config.php';

// safe no-cover value
$no_cover = defined('NO_COVER_PATH') ? NO_COVER_PATH : '';
$no_cover_escaped = htmlspecialchars($no_cover, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Paxton Carnegie — Movies</title>
<style>
:root{
  --bg:#f5f7f5;
  --bg-soft:#e9f3ec;
  --text:#123024;
  --muted:#64727c;
  --accent:#15803d;
  --accent-soft:#d9fbe3;
  --accent-dark:#14532d;
  --card:#ffffff;
  --border:#d0ddd3;
  --rail:#f0f7f1;
}
*{box-sizing:border-box}
body{
  margin:0;
  font-family:system-ui,Segoe UI,Roboto,Helvetica,Arial;
  background:var(--bg);
  color:var(--text);
  -webkit-tap-highlight-color:transparent;
}
.wrap{
  max-width:1400px;
  margin:0 auto;
  padding:20px;
}

/* top header bar */
header{
  background:#ffffff;
  border-radius:18px;
  border:1px solid var(--border);
  padding:14px 16px;
  display:flex;
  flex-direction:column;
  gap:10px;
  margin-bottom:16px;
}
.header-top{
  display:flex;
  flex-wrap:wrap;
  gap:12px;
  align-items:center;
  justify-content:space-between;
}
.brand{
  display:flex;
  flex-direction:column;
  gap:2px;
}
h1{
  color:var(--accent-dark);
  font-size:24px;
  margin:0;
}
.brand-sub{
  font-size:13px;
  color:var(--muted);
}
.header-bottom{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  align-items:center;
}
.input{
  border:1px solid var(--border);
  background:#ffffff;
  color:var(--text);
  border-radius:999px;
  padding:10px 14px;
  font-size:15px;
}
.input::placeholder{color:#9aa5ae}
#q{
  flex:1 1 260px;
}
    
.input-select{
  padding-right:34px;
  min-width:140px;
  appearance:none;
  background-image:linear-gradient(45deg,transparent 50%,#64727c 50%),linear-gradient(135deg,#64727c 50%,transparent 50%);
  background-position:calc(100% - 16px) 15px,calc(100% - 11px) 15px;
  background-size:6px 6px,6px 6px;
  background-repeat:no-repeat;
}
.filters-row{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  align-items:center;
}
.filter-check{
  display:flex;
  align-items:center;
  gap:6px;
  font-size:13px;
  color:var(--muted);
}
.filter-check input{
  width:18px;
  height:18px;
}
.btn{
  border:1px solid var(--border);
  background:#ffffff;
  color:var(--accent-dark);
  border-radius:999px;
  padding:8px 12px;
  cursor:pointer;
  font-size:13px;
  touch-action:manipulation;
  display:flex;
  align-items:center;
  gap:6px;
}
.btn:active{
  transform:scale(.97);
}

.section{margin-top:20px}
.section h2{
  color:var(--accent-dark);
  font-size:20px;
  margin:0 0 10px 2px;
}
.rail{
  position:relative;
  background:var(--rail);
  border-radius:16px;
  padding:12px;
  overflow:hidden;
}

/* grid for all titles */
.rail-all{
  max-height:none;
}
.grid-all{
  display:grid;
  grid-template-columns:repeat(4,minmax(0,1fr));
  gap:14px;
  padding:4px;
}

/* only shrink to 2 on small screens like phones */
@media (max-width: 800px){
  .grid-all{
    grid-template-columns:repeat(2,minmax(0,1fr));
  }
}

/* 3-wide on medium, 2-wide on small */
@media (max-width: 1100px){
  .grid-all{
    grid-template-columns:repeat(4,minmax(0,1fr));
  }
}
@media (max-width: 700px){
  .grid-all{
    grid-template-columns:repeat(3,minmax(0,1fr));
  }
}

/* cards */
.card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:16px;
  scroll-snap-align:start;
  overflow:hidden;
  cursor:pointer;
  transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease;
}
.card:hover{
  transform:translateY(-2px);
  box-shadow:0 8px 18px rgba(15,23,15,.18);
  border-color:var(--accent);
}

/* poster */
.poster{
  width:100%;
  aspect-ratio:2/3;
  object-fit:cover;
  background:#d5e2d9;
  display:block;
}

.title{
  color:var(--text);
  font-weight:700;
  font-size:14px;
  padding:10px 10px 4px;
}
.meta{
  color:var(--muted);
  font-size:13px;
  padding:0 10px 10px;
}

#status{
  color:var(--muted);
  margin:6px 2px 0;
  font-size:13px;
}

/* modal */
dialog{
  border:1px solid var(--border);
  border-radius:18px;
  max-width:900px;
  width:96%;
  background:#ffffff;
  color:var(--text);
}
.modal-h{
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:16px;
  border-bottom:1px solid var(--border);
}
.modal-h strong{
  font-size:20px;
}
.modal-b{
  display:grid;
  gap:16px;
  padding:16px;
}
.two{
  display:grid;
  grid-template-columns:260px 1fr;
  gap:16px;
}
.modal-cover{
  width:100%;
  aspect-ratio:2/3;
  object-fit:cover;
  background:#d5e2d9;
  border:1px solid var(--border);
  border-radius:14px;
}
.kv{
  border:1px solid var(--border);
  border-radius:12px;
  padding:10px;
  background:var(--bg-soft);
  margin-bottom:6px;
}
.k{
  font-size:13px;
  color:var(--muted);
}
.v{
  font-weight:700;
  font-size:15px;
}

/* small header layout */
@media (max-width: 768px){
  .header-top{
    flex-direction:column;
    align-items:flex-start;
  }
  .header-bottom{
    flex-direction:column;
    align-items:stretch;
  }
}
</style>
</head>
<body>
<div class="wrap">
  <header>
    <div class="header-top">
      <div class="brand">
        <h1>Paxton Carnegie Library</h1>
        <div class="brand-sub">dvd &amp; movie catalog</div>
      </div>
    </div>
    <div class="header-bottom">
      <input id="q" class="input" placeholder="search title, barcode, or call #">
      <div class="filters-row">
        <select id="filter-rating" class="input input-select">
          <option value="">all ratings</option>
          <option value="G">g</option>
          <option value="PG">pg</option>
          <option value="PG-13">pg-13</option>
          <option value="PG13">pg-13 (pg13)</option>
          <option value="R">r</option>
          <option value="NR">not rated</option>
        </select>
        <label class="filter-check">
          <input type="checkbox" id="filter-cover">
          only show covers
        </label>
      </div>
    </div>
  </header>

  <div id="status"></div>

  <section class="section">
    <h2>All titles</h2>
    <div class="rail rail-all">
      <div id="grid-all" class="grid-all"></div>
    </div>
  </section>
</div>

<!-- Modal -->
<dialog id="modal">
  <div class="modal-h">
    <strong id="mTitle">details</strong>
    <button id="mClose" class="btn" type="button">close</button>
  </div>
  <div class="modal-b">
    <div class="two">
      <img id="mCover" class="modal-cover" alt="Cover"/>
      <div>
        <div class="kv"><span class="k">barcode</span> <div class="v" id="mBarcode">—</div></div>
        <div class="kv"><span class="k">rating</span>  <div class="v" id="mRating">—</div></div>
        <div class="kv"><span class="k">call #</span>  <div class="v" id="mCall">—</div></div>
        <div class="kv"><span class="k">status</span>  <div class="v" id="mStatus">—</div></div>
        <div class="kv"><span class="k">branch</span>  <div class="v" id="mBranch">—</div></div>
        <div class="kv"><span class="k">due date</span><div class="v" id="mDue">—</div></div>
        <details style="margin-top:6px">
          <summary>raw json</summary>
          <pre id="mRaw" style="max-height:240px;overflow:auto"></pre>
        </details>
      </div>
    </div>
  </div>
</dialog>

<script>
const $  = s => document.querySelector(s);
const $$ = s => Array.from(document.querySelectorAll(s));
const NO_COVER = '<?php echo $no_cover_escaped; ?>';

let ITEMS = [];
let INDEX = {};

function esc(s) {
  return String(s || '').replace(/[&<>"']/g, m => ({
    "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"
  }[m]));
}

function card(it){
  return `<article class="card" data-bc="${esc(it.barcode)}">
    <img class="poster" loading="lazy" alt="cover"
         src="${esc(it.cover || NO_COVER)}">
    <div class="title">${esc(it.title||'Untitled')}</div>
    <div class="meta">${esc(it.rating||'')}</div>
  </article>`;
}

function attachPosterGuard(cardEl, bc){
  const img = cardEl.querySelector('.poster');
  if (!img) return;

  img.addEventListener('load', () => {
    if (img.naturalWidth <= 2 && img.naturalHeight <= 2) {
      img.src = NO_COVER;
      if (INDEX[bc]) {
        INDEX[bc].cover = NO_COVER;
        INDEX[bc].noCover = true;
      }
    }
  });

  img.addEventListener('error', () => {
    img.src = NO_COVER;
    if (INDEX[bc]) {
      INDEX[bc].cover = NO_COVER;
      INDEX[bc].noCover = true;
    }
  });
}

function needsDetailFetch(it){
  if (!it) return true;
  if (!it.cover || it.cover === NO_COVER) return true;
  if (it.noCover) return false;
  return false;
}

// render grid and then lazy-load covers via item.php
function renderAllGrid(list){
  const el = $('#grid-all');
  el.innerHTML = list.map(bc => {
    const it = INDEX[bc] || {barcode:bc,title:'unknown',cover:NO_COVER};
    return card(it);
  }).join('');

  const cards = el.querySelectorAll('.card');
  cards.forEach(c => {
    const bc = c.getAttribute('data-bc');
    c.addEventListener('click', () => openModal(bc));
    attachPosterGuard(c, bc);
  });
}


  // lazy fetch covers for each card
  
}

// filters
function applyFilters(){
  const q = ($('#q').value || '').toLowerCase();
  const rating    = $('#filter-rating').value;
  const coverOnly = $('#filter-cover').checked;

  return ITEMS.filter(x => {
    if (q) {
      const t    = (x.title||'').toLowerCase();
      const bc   = (x.barcode||'').toLowerCase();
      const call = (x.callNumber||'').toLowerCase();
      if (!t.includes(q) && !bc.includes(q) && !call.includes(q)) return false;
    }
    if (rating) {
      const r = (x.rating || '').toUpperCase();
      if (rating === 'NR') {
        if (r && r !== 'NR' && r !== 'NOT RATED') return false;
      } else if (r !== rating.toUpperCase()) {
        return false;
      }
    }
    if (coverOnly) {
      if (!x.cover || x.cover === NO_COVER) return false;
    }
    return true;
  }).map(x => x.barcode);
}

$('#q').addEventListener('input', () => {
  const list = applyFilters();
  renderAllGrid(list);
});
$('#filter-rating').addEventListener('change', () => {
  const list = applyFilters();
  renderAllGrid(list);
});
$('#filter-cover').addEventListener('change', () => {
  const list = applyFilters();
  renderAllGrid(list);
});

// modal
const modal = $('#modal');
$('#mClose').addEventListener('click', () => modal.close());

async function openModal(bc){
  const it = INDEX[bc] || {};

  $('#mTitle').textContent   = it.title || 'item';
  $('#mBarcode').textContent = bc;
  $('#mRating').textContent  = it.rating || '—';

  $('#mCover').src = it.cover || NO_COVER;
  $('#mCall').textContent   = '—';
  $('#mStatus').textContent = '—';
  $('#mBranch').textContent = '—';
  $('#mDue').textContent    = '—';
  $('#mRaw').textContent    = '';

  try {
    const res = await fetch(`api/item.php?barcode=${encodeURIComponent(bc)}`);
    const j = await res.json();
    const d = j.data || {};
    const b = d.BibInfo || {};
    const c = d.CirculationData || {};

    if (j.cover) {
      $('#mCover').src = j.cover;
      if (!INDEX[bc]) INDEX[bc] = {barcode:bc};
      INDEX[bc].cover = j.cover;
    }

    $('#mCall').textContent   = b.CallNumber || d.CallNumber || '—';
    $('#mStatus').textContent = d.ItemStatusDescription || '—';
    $('#mBranch').textContent = b.AssignedBranch || '—';

    const due = b.DueDate || c.DueDate || null;
    $('#mDue').textContent = due ? due : '—';

    $('#mRaw').textContent = JSON.stringify(d, null, 2);
  } catch (e) {
    console.error(e);
  }

  if (!modal.open) modal.showModal();
}

// boot
(async function boot(){
  try{
    const res = await fetch('api/list.php',{cache:'no-cache'});
    const li  = await res.json();

    if (li.ok) {
      ITEMS = li.items || [];
      ITEMS.sort((a,b) => (a.title||'').localeCompare(b.title||'', undefined, {sensitivity:'base'}));
      INDEX = Object.fromEntries(ITEMS.map(x => [x.barcode, x]));
      $('#status').textContent = `loaded ${ITEMS.length} items`;
    } else {
      $('#status').textContent = 'failed to load items';
    }
  }catch(e){
    console.error(e);
    $('#status').textContent = 'unable to load items';
  }

  const allList = ITEMS.map(x => x.barcode);
  renderAllGrid(allList);
})();
</script>
</body>
</html>
