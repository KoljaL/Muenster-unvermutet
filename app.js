
'use strict';
/* ==========================================================
   1. Konfiguration (Schlüssel identisch zu index.html, damit Fortschritt übernommen wird)
   ========================================================== */
const STORAGE_KEY = 'muenster-unvermutet:progress';
const LEGACY_STORAGE_KEY = 'ms-suchspiel';
const INTRO_STATE_KEY = 'muenster-unvermutet:intro-state';
const CONSENT_KEY = 'muenster-unvermutet:stats-consent';
const PLAYER_KEY = 'muenster-unvermutet:player-id';
const LAYOUT_KEY = 'muenster-unvermutet:layout';
const LOOK_KEY = 'muenster-unvermutet:look';
const PROGRESS_VERSION = 1;
const OPTION_LETTERS = ['A', 'B', 'C', 'D'];
const MAP_CENTER = [51.9622, 7.6278];
const MAP_TILE_URL = 'https://tile.openstreetmap.de/{z}/{x}/{y}.png';
const LOCATION_TIMEOUT_MS = 15000;
const LOCATION_MAX_AGE_MS = 120000;
const REQUEST_TIMEOUT_MS = 15000;
const TAB_H = 72;
const PEEK = TAB_H;

const $ = (s) => document.querySelector(s);
const $$ = (s) => Array.from(document.querySelectorAll(s));
const app = $('#app');
let sheet = $('#sheet');

/* ==========================================================
   2. API
   ========================================================== */
function previewQuery(separator = '&') {
  const preview = new URLSearchParams(window.location.search).get('preview');
  return preview === null ? '' : separator + 'preview=' + encodeURIComponent(preview);
}
const Api = {
  urls: { pins: 'api/map_pins.php', details: 'api/get_details.php', categories: 'api/categories.php', submit: 'api/submit_station.php', settings: 'api/settings.php', verifyLocation: 'api/verify_location.php', track: 'api/track.php' },
  async fetchApi(url, options = {}) {
    const controller = new AbortController();
    const timeoutId = window.setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);
    try {
      const response = await fetch(url, { ...options, headers: { Accept: 'application/json', ...(options.headers || {}) }, cache: 'no-cache', signal: controller.signal });
      let payload = null;
      try { payload = await response.json(); } catch (error) { /* wird unten als Fehler behandelt */ }
      if (!response.ok || !payload || payload.error || payload.data === null || payload.data === undefined) {
        throw new Error((payload && payload.error && payload.error.message) || 'HTTP ' + response.status);
      }
      return payload.data;
    } finally {
      window.clearTimeout(timeoutId);
    }
  },
  loadCategories() { return this.fetchApi(this.urls.categories); },
  loadPins() { return this.fetchApi(this.urls.pins); },
  loadSettings() {
    const preview = new URLSearchParams(window.location.search).get('preview');
    return this.fetchApi(this.urls.settings + (preview === null ? '' : '?preview=' + encodeURIComponent(preview)));
  },
  loadDetails(id) { return this.fetchApi(this.urls.details + '?id=' + encodeURIComponent(id) + previewQuery()); },
  loadProtectedDetails(id) { return this.fetchApi(this.urls.details + '?id=' + encodeURIComponent(id) + '&protected=1' + previewQuery()); },
  resetLocationAccess() { return this.fetchApi('api/reset_location.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' }); },
  verifyLocation(id, location) {
    return this.fetchApi(this.urls.verifyLocation + previewQuery('?'), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id, ...location }) });
  },
};

/* Anonyme Statistik: nur nach Einwilligung, mit zufälliger Browser-Kennung, ohne Standortdaten */
const Stats = {
  consent() { try { return localStorage.getItem(CONSENT_KEY); } catch (e) { return null; } },
  enabled() { return this.consent() === 'granted'; },
  setConsent(granted) {
    try {
      localStorage.setItem(CONSENT_KEY, granted ? 'granted' : 'denied');
      if (!granted) localStorage.removeItem(PLAYER_KEY);
    } catch (e) { /* nur Sitzung */ }
  },
  newPlayer() { try { localStorage.removeItem(PLAYER_KEY); } catch (e) { /* egal */ } },
  playerId() {
    try {
      let id = localStorage.getItem(PLAYER_KEY);
      if (!/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/.test(id || '')) {
        id = typeof crypto.randomUUID === 'function' ? crypto.randomUUID() : '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, (c) => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16));
        localStorage.setItem(PLAYER_KEY, id);
      }
      return id;
    } catch (e) { return null; }
  },
  track(event, stationId = null, attempts = null) {
    if (!this.enabled() || new URLSearchParams(window.location.search).has('preview')) return;
    const playerId = this.playerId();
    if (!playerId) return;
    const body = { playerId, event };
    if (stationId) body.stationId = stationId;
    if (attempts !== null) body.attempts = attempts;
    fetch(Api.urls.track, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body), keepalive: true }).catch(() => {});
  },
};
const UI_DEFAULTS = Object.freeze({ suggest: true, legend: true, station_list: true, hint_button: true, reveal_button: true, location_gate: false, station_map: true, completion: true });
let ui = UI_DEFAULTS;
let locationConfig = { radius: 50, maxAccuracy: 50 };
let routeHintText = '';
const isEnabled = (key) => ui[key] === true;
function resolveUi(settings) {
  const resolved = {};
  Object.keys(UI_DEFAULTS).forEach((key) => { resolved[key] = settings && typeof settings[key] === 'boolean' ? settings[key] : UI_DEFAULTS[key]; });
  return Object.freeze(resolved);
}
function routeUrl(st) {
  const dest = st.lat + ',' + st.lon;
  return isIos()
    ? 'https://maps.apple.com/?daddr=' + encodeURIComponent(dest) + '&dirflg=w'
    : 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(dest) + '&travelmode=walking';
}
function routeLinkHtml(st) {
  return '<a class="btn ghost route-btn" href="' + esc(routeUrl(st)) + '" target="_blank" rel="noopener noreferrer">🧭 Route starten</a>';
}
function routeHintHtml() { return routeHintText ? '<p class="route-hint">' + esc(routeHintText) + '</p>' : ''; }
function renderPublicText(settings) {
  routeHintText = typeof settings?.routeHint === 'string' ? settings.routeHint.trim() : '';
  const credits = typeof settings?.credits === 'string' ? settings.credits : '';
  const imprint = typeof settings?.imprint === 'string' ? settings.imprint : '';
  $('#publicCreditsText').innerHTML = credits;
  $('#publicCredits').hidden = credits.trim() === '';
  $('#publicImprintText').innerHTML = imprint;
  $('#publicImprint').hidden = imprint.trim() === '';
}
function applyUiSettings() {
  $$('[data-ui-option]').forEach((el) => { if (!isEnabled(el.dataset.uiOption)) el.remove(); });
  sheet = $('#sheet');
  $('#tabs').style.gridAutoColumns = '1fr';
}

/* ==========================================================
   3. Daten & Fortschritt
   ========================================================== */
let stations = [];
const categoriesById = new Map();
const stationsById = new Map();
const detailsRequests = new Map();
const locationStates = new Map();
const playerLocations = new Map();
const pendingLocationRequests = new Set();
let locationGeneration = 0;

function categoryOf(station) {
  return categoriesById.get(station.category) || { id: station.category, name: 'Unbekannte Kategorie', icon: null, color: '#57534e' };
}
function createStationProgress() { return { solved: false, locationVerified: false, attempts: 0, selectedOptionIndex: null, hintUsed: false, hintVisible: false, revealed: false }; }
function createEmptyProgress() { return { version: PROGRESS_VERSION, stations: {} }; }
function loadProgress() {
  try {
    const stored = JSON.parse(localStorage.getItem(STORAGE_KEY));
    if (stored && stored.version === PROGRESS_VERSION && stored.stations) return stored;
    const legacy = JSON.parse(localStorage.getItem(LEGACY_STORAGE_KEY));
    if (Array.isArray(legacy)) {
      const progress = createEmptyProgress();
      legacy.forEach((id) => { if (typeof id === 'string') progress.stations[id] = { ...createStationProgress(), solved: true }; });
      return progress;
    }
  } catch (error) { /* leerer Fortschritt */ }
  return createEmptyProgress();
}
const Store = {
  progress: loadProgress(),
  detailsErrors: new Set(),
  save() { try { localStorage.setItem(STORAGE_KEY, JSON.stringify(this.progress)); } catch (error) { /* nur Sitzung */ } },
  get(id) {
    const stored = this.progress.stations[id];
    const p = { ...createStationProgress(), ...(stored || {}) };
    p.locationVerified = p.locationVerified === true;
    if (stored && typeof stored.hintVisible !== 'boolean') p.hintVisible = Boolean(stored.hintUsed);
    return p;
  },
  update(id, changes) { this.progress.stations[id] = { ...this.get(id), ...changes }; this.save(); },
  reset() { this.progress = createEmptyProgress(); this.save(); },
};
const isSolved = (id) => Store.get(id).solved;
const countSolved = () => stations.filter((s) => isSolved(s.id)).length;

const U = { selectedId: null, loading: true, active: new Set(), tab: 'map', snap: 'hidden', lastTrigger: null, optionsKey: '', orientKey: '', teaserExpanded: false };

/* ==========================================================
   4. Hilfsfunktionen
   ========================================================== */
const esc = (t) => String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
const hasDetails = (s) => Boolean(s && (s.publicDetailsLoaded || Array.isArray(s.options)));
const formatCoordinates = (s) => s.lat.toFixed(5) + ', ' + s.lon.toFixed(5);
const reducedMotion = () => window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
function distanceBetween(lat1, lon1, lat2, lon2) {
  const rad = (d) => (d * Math.PI) / 180;
  const dLat = rad(lat2 - lat1), dLon = rad(lon2 - lon1);
  const a = Math.min(1, Math.max(0, Math.sin(dLat / 2) ** 2 + Math.cos(rad(lat1)) * Math.cos(rad(lat2)) * Math.sin(dLon / 2) ** 2));
  return 6371000 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}
function announce(message) {
  const el = $('#statusMessage');
  el.textContent = '';
  setTimeout(() => { el.textContent = message; }, 50);
}
function toast(text) {
  const el = $('#toast');
  el.textContent = text;
  el.classList.add('on');
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('on'), 2800);
}
const buzz = (p) => { if (navigator.vibrate) navigator.vibrate(p); };
function externalLink(url, text) {
  return '<a href="' + esc(url) + '" target="_blank" rel="noopener noreferrer">' + text + '<span class="vh"> (öffnet in neuem Tab)</span></a>';
}
function isValidExternalUrl(value) {
  if (typeof value !== 'string') return false;
  try { const u = new URL(value); return u.protocol === 'http:' || u.protocol === 'https:'; } catch (e) { return false; }
}
function categoryTextColor(color) {
  const ch = color.slice(1).match(/.{2}/g).map((c) => parseInt(c, 16) / 255);
  const lum = ch.map((c) => (c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4)).reduce((s, c, i) => s + c * [0.2126, 0.7152, 0.0722][i], 0);
  return 1.05 / (lum + 0.05) >= (lum + 0.05) / 0.05 ? 'var(--color-category-text-light)' : 'var(--color-category-text-dark)';
}
const catImg = (c, size) => (c.icon ? '<img src="' + esc(c.icon) + '" alt="">' : '');
const isValidPin = (p) => p && typeof p.id === 'string' && p.id !== '' && Number.isFinite(p.number) && typeof p.title === 'string' && typeof p.category === 'string' && Number.isFinite(p.lat) && Number.isFinite(p.lon);
const isValidCategory = (c) => c && typeof c.id === 'string' && c.id !== '' && typeof c.name === 'string' && (c.icon === null || typeof c.icon === 'string') && typeof c.color === 'string' && /^#[0-9a-fA-F]{6}$/.test(c.color);
function isValidDetails(d, publicOnly = false) {
  const hasHint = d && Object.prototype.hasOwnProperty.call(d, 'hint');
  const ok = d && typeof d.id === 'string' && typeof d.title === 'string' && Number.isFinite(d.lat) && Number.isFinite(d.lon) && typeof d.taskImage === 'string';
  if (!ok) return false;
  if (typeof d.teaserText !== 'string') return false;
  if (publicOnly) return !hasHint && !Object.prototype.hasOwnProperty.call(d, 'options');
  return typeof d.hint === 'string' && Array.isArray(d.options) && d.options.length >= 2 && Number.isInteger(d.correctOptionIndex) && d.correctOptionIndex >= 0 && d.correctOptionIndex < d.options.length && typeof d.solutionTitle === 'string' && typeof d.info === 'string' && Array.isArray(d.links) && d.links.every((l) => l && typeof l.title === 'string' && isValidExternalUrl(l.url));
}

/* ==========================================================
   5. Karte (Leaflet)
   ========================================================== */
let map = null;
const markers = new Map();
function initMap() {
  if (typeof L === 'undefined') { $('#mapFallback').hidden = false; return; }
  map = L.map('map', { zoomControl: false }).setView(MAP_CENTER, 15);
  L.control.zoom({ position: 'topright', zoomInTitle: 'Vergrößern', zoomOutTitle: 'Verkleinern' }).addTo(map);
  L.tileLayer(MAP_TILE_URL, { maxZoom: 18, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>-Mitwirkende' }).addTo(map);
}
function pinHtml(station) {
  const c = categoryOf(station);
  return '<div class="map-pin' + (c.icon ? '' : ' no-icon') + '" style="--pin:' + esc(c.color) + ';--pin-text:' + categoryTextColor(c.color) + '" aria-hidden="true">' + catImg(c) + '<span class="pin-number">' + station.number + '</span><span class="pin-check">✓</span></div>';
}
function handleMarkerActivate(station, el) {
  openStation(station.id, { trigger: el });
}
function addMarkers() {
  if (!map) return;
  markers.forEach((m) => m.remove());
  markers.clear();
  stations.forEach((station) => {
    const marker = L.marker([station.lat, station.lon], { keyboard: true, icon: L.divIcon({ className: '', iconSize: [46, 46], iconAnchor: [23, 23], html: pinHtml(station) }) }).addTo(map);
    const el = marker.getElement();
    el.setAttribute('role', 'button');
    marker.on('click', () => handleMarkerActivate(station, el));
    el.addEventListener('keydown', (e) => { if (e.key === ' ' || e.key === 'Spacebar') { e.preventDefault(); handleMarkerActivate(station, el); } });
    markers.set(station.id, marker);
  });
  fitMap();
}
function mapPadding() {
  const h = app.clientHeight;
  return { paddingTopLeft: [28, 150], paddingBottomRight: [28, Math.min(PEEK + 36, h * 0.4)] };
}
function fitMap() {
  if (!map) return;
  const list = visibleStations().length ? visibleStations() : stations;
  if (!list.length) return;
  map.invalidateSize();
  map.fitBounds(list.map((s) => [s.lat, s.lon]), { ...mapPadding(), maxZoom: 17, animate: !reducedMotion() });
}
function updateMapPins() {
  markers.forEach((marker, id) => {
    const station = stationsById.get(id), el = marker.getElement(), pin = el.querySelector('.map-pin'), solved = isSolved(id), visible = isStationVisible(station);
    pin.classList.toggle('map-pin--solved', solved);
    pin.classList.toggle('map-pin--hidden', !visible);
    el.style.pointerEvents = visible ? '' : 'none';
    el.tabIndex = visible ? 0 : -1;
    el.setAttribute('aria-label', 'Station ' + station.number + ', ' + categoryOf(station).name + (solved ? ' (gelöst)' : ' (offen)'));
  });
}
const isStationVisible = (s) => !U.active.size || U.active.has(s.category);
const visibleStations = () => (U.active.size ? stations.filter((s) => U.active.has(s.category)) : stations);

/* ==========================================================
   6. Rendering: Chips, Liste, Fortschritt
   ========================================================== */
function renderChips() {
  const html = '<button type="button" class="chip" data-c="" aria-pressed="' + String(!U.active.size) + '">Alle</button>' +
    Array.from(categoriesById.values()).map((c) => '<button type="button" class="chip" data-c="' + esc(c.id) + '" aria-pressed="' + String(U.active.has(c.id)) + '">' + catImg(c) + esc(c.name) + '</button>').join('');
  ['#chipsMap', '#chipsSheet'].forEach((sel) => { const el = $(sel); if (el) el.innerHTML = html; });
}
function setCategoryFilter(id) {
  if (id === null) U.active.clear();
  else if (U.active.has(id)) U.active.delete(id);
  else U.active.add(id);
  renderChips(); updateMapPins(); renderList(); fitMap();
  const n = visibleStations().length;
  announce(n === 1 ? '1 Station sichtbar.' : n + ' Stationen sichtbar.');
}
let listAnimated = false;
function renderList() {
  const list = $('#list');
  if (!list) return;
  if (U.loading) { list.innerHTML = '<li class="row skeleton" aria-hidden="true"></li>'.repeat(3); return; }
  const items = visibleStations();
  const animate = !listAnimated && !reducedMotion();
  if (items.length) listAnimated = true;
  list.innerHTML = items.length ? items.map((s, i) => {
    const c = categoryOf(s), solved = isSolved(s.id);
    return '<li><button type="button" class="row' + (solved ? ' solved' : '') + (animate ? ' enter' : '') + '" style="--i:' + i + '" data-station-id="' + esc(s.id) + '"><span class="thumb" aria-hidden="true" style="--c:' + esc(c.color) + ';--t:' + categoryTextColor(c.color) + '">' + (c.icon ? catImg(c) : s.number) + (c.icon ? '<em>' + s.number + '</em>' : '') + '</span><span><b>Station ' + s.number + '</b><small>' + esc(solved && hasDetails(s) && s.solutionTitle ? s.solutionTitle : c.name) + '</small></span><span class="state">' + (solved ? '✓ gelöst' : 'Los →') + '</span></button></li>';
  }).join('') : '<li class="empty">' + (stations.length ? 'Keine Stationen in den gewählten Kategorien.' : 'Keine Stationen verfügbar.') + '</li>';
  $('#sheetCount').textContent = U.loading ? '' : countSolved() + ' von ' + stations.length + ' gelöst';
}
function computeStats() {
  const stats = stations.map((s) => Store.get(s.id));
  const attempts = stats.reduce((sum, p) => sum + p.attempts, 0);
  const hints = stats.filter((p) => p.hintUsed).length;
  const revealed = stats.filter((p) => p.revealed).length;
  return { attempts, hints, revealed, own: stations.length - revealed };
}
function rankText(st) {
  if (st.revealed === 0 && st.attempts === 0) return '🏅 Münster-Meister:in';
  if (st.revealed === 0) return '🥈 Spürnase';
  return '🧭 Stadtentdecker:in';
}
function statsHtml(st) {
  return '<div><b>' + st.own + '/' + stations.length + '</b><span>selbst gelöst</span></div><div><b>' + st.hints + '</b><span>Hinweise genutzt</span></div><div><b>' + st.attempts + '</b><span>Fehlversuche</span></div>';
}
function renderProgress() {
  const n = countSolved(), N = stations.length, all = !U.loading && N > 0 && n === N;
  $('#ringFg').style.strokeDashoffset = String(113.1 * (1 - (N ? n / N : 0)));
  $('#ringTxt').textContent = U.loading ? '–' : n + '/' + N;
  $('#ring').setAttribute('aria-label', U.loading ? 'Fortschritt wird geladen.' : 'Fortschritt: ' + n + ' von ' + N + ' gelöst. Fortschritt anzeigen');
  $('#bigCount').textContent = U.loading ? '–' : n + '/' + N;
  $('#bigBar').style.width = (N ? Math.round((n / N) * 100) : 0) + '%';
  const track = $('#bigTrack');
  track.setAttribute('aria-valuemax', N); track.setAttribute('aria-valuenow', n); track.setAttribute('aria-valuetext', n + ' von ' + N + ' gelöst');
  const st = computeStats();
  $('#rank').textContent = all ? rankText(st) : n ? 'Weiter so!' : 'Los geht\'s – wähle eine Station.';
  $('#badges').innerHTML = stations.map((s) => {
    const c = categoryOf(s), solved = isSolved(s.id);
    return '<span class="badge' + (solved ? ' got' : '') + '" style="--c:' + esc(c.color) + ';--t:' + categoryTextColor(c.color) + '" title="Station ' + s.number + (solved ? ' (gelöst)' : '') + '">' + (c.icon ? catImg(c) : s.number) + '</span>';
  }).join('');
  $('#stats').innerHTML = statsHtml(st);
  const fin = $('#openFinale'); if (fin) fin.hidden = !all;
}
function renderAll() { updateMapPins(); renderChips(); renderList(); renderProgress(); }

/* ==========================================================
   7. Bottom Sheet & Tabs
   ========================================================== */
// Die Stationsliste ist nur im Tab "Liste" sichtbar ('full'), sonst ausgeblendet ('hidden').
function setSnap(k) {
  if (!sheet) return;
  U.snap = k;
  sheet.dataset.snap = k;
  sheet.style.transform = k === 'full' ? 'translateY(0)' : 'translateY(' + (sheet.offsetHeight + 20) + 'px)';
}
function initSheet() {
  if (!sheet) return;
  app.style.setProperty('--peek', PEEK + 'px');
}
function go(tab, toggleCurrent = true) {
  if (toggleCurrent && tab !== 'map' && U.tab === tab) tab = 'map';
  U.tab = tab;
  $$('.tab').forEach((t) => t.setAttribute('aria-selected', String(t.dataset.t === tab)));
  const mapLike = tab === 'map' || tab === 'list';
  $('#p-progress').classList.toggle('on', tab === 'progress');
  $('#p-more').classList.toggle('on', tab === 'more');
  $('#p-progress').inert = tab !== 'progress';
  $('#p-more').inert = tab !== 'more';
  $('#top').classList.toggle('away', !mapLike);
  const cm = $('#chipsMap'); if (cm) cm.classList.toggle('away', !mapLike);
  if (sheet) {
    setSnap(tab === 'list' ? 'full' : 'hidden');
    sheet.inert = tab !== 'list';
  }
  if (mapLike && map) setTimeout(() => map.invalidateSize(), 60);
}

/* ==========================================================
   8. Station-Ansicht
   ========================================================== */
let stationMap = null, orientPlayerLayer = null;
const nextUnsolved = (fromId) => {
  const i = stations.findIndex((s) => s.id === fromId);
  for (let step = 1; step <= stations.length; step++) { const c = stations[(i + step) % stations.length]; if (!isSolved(c.id)) return c; }
  return null;
};
function updateStationUrl(id, method) {
  const url = new URL(window.location.href);
  if (id) url.searchParams.set('station', id); else url.searchParams.delete('station');
  if (url.href === window.location.href) return;
  window.history[method + 'State'](window.history.state, '', url);
}
function setOrigin(trigger) {
  const st = $('#station'), r = app.getBoundingClientRect();
  if (trigger && document.body.contains(trigger)) {
    const o = trigger.getBoundingClientRect();
    st.style.setProperty('--ox', o.left + o.width / 2 - r.left + 'px');
    st.style.setProperty('--oy', o.top + o.height / 2 - r.top + 'px');
  } else {
    st.style.setProperty('--ox', '50%'); st.style.setProperty('--oy', '70%');
  }
}
function instant(fn) {
  const st = $('#station');
  st.classList.add('instant');
  fn();
  void st.offsetWidth;
  st.classList.remove('instant');
}
function openStation(id, opts = {}) {
  if (!stationsById.has(id)) return;
  const source = opts.source || 'user';
  if (source === 'user') { updateStationUrl(id, 'push'); markIntroSeen(); }
  if (source !== 'history') Stats.track('opened', id);
  U.selectedId = id;
  U.lastTrigger = opts.trigger || null;
  U.optionsKey = ''; U.orientKey = ''; U.teaserExpanded = false;
  destroyOrientMap();
  const orientEl = $('#orient');
  if (orientEl) orientEl.open = !Store.get(id).solved;
  const st = $('#station');
  setOrigin(opts.trigger);
  st.classList.remove('on');
  instant(() => { $('#sScroll').scrollTop = 0; renderStation(); });
  st.classList.add('on');
  $('#tabs').inert = true;
  ensureDetails(id, { passive: source !== 'user' });
  setTimeout(() => { if (U.selectedId === id) $('#sTitle').focus({ preventScroll: true }); }, 380);
}
function closeStation(opts = {}) {
  const prev = U.selectedId;
  if (!prev) return;
  if ($('#imageViewer').classList.contains('on')) closeImageViewer(false);
  if (opts.updateUrl !== false) updateStationUrl(null, 'push');
  U.selectedId = null;
  $('#station').classList.remove('on');
  $('#tabs').inert = false;
  destroyOrientMap();
  renderAll();
  setTimeout(() => {
    const t = U.lastTrigger;
    const m = markers.get(prev);
    const target = t && document.body.contains(t) ? t : (m && m.getElement()) || $('#pageTitle');
    if (target) target.focus({ preventScroll: true });
  }, 320);
}
function setImage(img, src, alt) {
  if (img.dataset.src === (src || '')) return;
  img.dataset.src = src || '';
  img.hidden = !src;
  if (src) {
    img.onerror = () => {
      img.hidden = true;
      $('#heroFb').textContent = '📷 Das Foto konnte nicht geladen werden.';
      updateHeroZoomHint();
    };
    img.alt = alt;
    img.setAttribute('role', 'button');
    img.tabIndex = 0;
    img.setAttribute('aria-label', alt + ', vollständig anzeigen');
    img.setAttribute('aria-haspopup', 'dialog');
    img.setAttribute('aria-controls', 'imageViewer');
    img.src = src;
  } else {
    img.removeAttribute('role');
    img.removeAttribute('tabindex');
    img.removeAttribute('aria-label');
    img.removeAttribute('aria-haspopup');
    img.removeAttribute('aria-controls');
  }
}
function updateHeroZoomHint() {
  $('#heroZoomHint').hidden = ![$('#imgTask'), $('#imgSol')].some((img) => !img.hidden);
}
let imageViewerTrigger = null;
function openImageViewer(img) {
  if (!img || img.hidden || (!img.currentSrc && !img.src)) return;
  const viewer = $('#imageViewer'), src = img.currentSrc || img.src;
  imageViewerTrigger = img;
  $('#imageViewerBg').src = src;
  $('#imageViewerImg').src = src;
  $('#imageViewerImg').alt = img.alt;
  $('#imageViewerCaption').textContent = img.alt;
  viewer.inert = false;
  viewer.setAttribute('aria-hidden', 'false');
  viewer.classList.add('on');
  $('#station').inert = true;
  $('#imageViewerClose').focus({ preventScroll: true });
}
function closeImageViewer(restoreFocus = true) {
  const viewer = $('#imageViewer');
  if (!viewer.classList.contains('on')) return;
  viewer.classList.remove('on');
  viewer.inert = true;
  viewer.setAttribute('aria-hidden', 'true');
  $('#station').inert = false;
  if (restoreFocus) {
    const target = imageViewerTrigger && !imageViewerTrigger.hidden ? imageViewerTrigger : $('#sTitle');
    target.focus({ preventScroll: true });
  }
  imageViewerTrigger = null;
}
function renderStation() {
  const st = stationsById.get(U.selectedId);
  if (!st) return;
  const root = $('#station'), c = categoryOf(st), has = hasDetails(st), p = Store.get(st.id);
  const gate = isEnabled('location_gate');
  const gated = gate && !st.protectedDetailsLoaded;
  const solved = has && p.solved && (!gate || st.protectedDetailsLoaded);
  $('#sCaption').hidden = !solved || !st.solutionImage;
  $('#sCaption').textContent = st.solutionCaption || 'Lösungsfoto';
  root.style.setProperty('--cat', c.color);
  root.style.setProperty('--tag-ink', categoryTextColor(c.color));
  root.classList.toggle('solved', solved);
  $('#sNum').textContent = 'Station ' + st.number;
  $('#sTag').innerHTML = catImg(c) + esc(c.name);
  $('#sTitle').textContent = solved ? st.solutionTitle : has ? st.title : 'Station ' + st.number;
  renderTeaser(st, has);
  $('#heroFb').textContent = has && !st.taskImage ? '📷 Für diese Station liegt noch kein Foto vor.' : '';
  setImage($('#imgTask'), has ? st.taskImage : '', 'Rätselfoto zu Station ' + st.number);
  setImage($('#imgSol'), has && solved ? st.solutionImage : (has && st.solutionImage && p.solved ? st.solutionImage : ''), has ? 'Lösungsfoto: ' + (st.solutionTitle || '') : '');
  updateHeroZoomHint();
  // Lade- und Fehlerzustand
  const status = $('#sStatus'), failed = Store.detailsErrors.has(st.id);
  status.hidden = has;
  $('#sContent').hidden = !has;
  if (!has) {
    status.innerHTML = failed
      ? '<div class="status-card" role="alert"><p>Die Station konnte nicht geladen werden.</p><div class="loc-actions"><button type="button" class="btn" data-act="retry">Erneut versuchen</button></div></div>'
      : '<div class="status-card loading" role="status"><p>Station wird geladen …</p></div>';
  } else {
    renderUnsolved(st, p, gated, solved);
    renderSolved(st, solved);
  }
  renderActionBar(st, p, has, gated, solved);
}
function renderTeaser(st, has) {
  const teaser = $('#sTeaser'), toggle = teaser.querySelector('[data-act="teaser"]');
  const text = has && typeof st.teaserText === 'string' ? st.teaserText : '';
  teaser.hidden = text === '';
  $('#sTeaserText').textContent = text;
  teaser.classList.toggle('expanded', U.teaserExpanded);
  toggle.setAttribute('aria-expanded', String(U.teaserExpanded));
  toggle.textContent = U.teaserExpanded ? 'Weniger anzeigen' : 'Mehr anzeigen';
}
function renderUnsolved(st, p, gated, solved) {
  const hintAvailable = !gated && isEnabled('hint_button') && typeof st.hint === 'string';
  $('#hintText').textContent = hintAvailable ? '💡 ' + st.hint : '';
  $('#hintBox').classList.toggle('is-visible', hintAvailable && p.hintVisible);
  const orient = $('#orient');
  if (orient) {
    orient.hidden = !isEnabled('station_map') || typeof L === 'undefined';
    const player = playerLocations.get(st.id);
    $('#orientDist').textContent = player ? Math.round(distanceBetween(player.lat, player.lon, st.lat, st.lon)) + ' m bis zum Ziel' : 'Der eigene Standort erscheint nach einer Standortaktualisierung.';
    if (!orient.hidden) setupOrientMap(st);
  }
  const key = st.id + (gated ? ':gate' : ':opts');
  const animate = key !== U.optionsKey;
  U.optionsKey = key;
  const box = $('#sOptions');
  if (gated) {
    const s = locationStates.get(st.id) || {};
    const msg = s.message || 'Begib dich zum abgebildeten Ort und aktualisiere anschließend deinen Standort.';
    const icon = s.loading ? '🧭' : s.message ? '🚶' : '📍';
    box.innerHTML = '<div class="loc' + (s.loading ? ' busy' : '') + (s.message && !s.loading ? ' fail' : '') + '"><div class="loc-row"><span class="ic" aria-hidden="true">' + icon + '</span><p role="status" aria-live="polite">' + esc(msg) + '</p></div><div class="loc-actions"><button type="button" class="btn" data-act="verify"' + (s.loading ? ' disabled' : '') + '>Standort aktualisieren</button>' + routeLinkHtml(st) +
      /* DEV-FAKE-POSITION (1/3) */ '<button type="button" class="btn dev" data-act="fake"' + (s.loading ? ' disabled' : '') + '>🛠 Fake Position (Dev)</button>' /* /DEV-FAKE-POSITION */ + '</div></div>' + routeHintHtml();
  } else {
    box.innerHTML = '<div class="options' + (animate ? ' enter' : '') + '">' + st.options.map((o, i) => {
      const wrong = !p.solved && p.selectedOptionIndex === i && i !== st.correctOptionIndex;
      return '<button type="button" class="option' + (wrong ? ' option--wrong' + (U.freshWrong === i ? ' fresh' : '') : '') + '" style="--i:' + i + '" data-act="option" data-i="' + i + '"><span class="option-letter" aria-hidden="true">' + OPTION_LETTERS[i] + '</span><span>' + esc(o) + '</span></button>';
    }).join('') + '</div>';
    U.freshWrong = null;
  }
  const wrongSel = !gated && p.selectedOptionIndex !== null && p.selectedOptionIndex !== st.correctOptionIndex && !p.solved;
  $('#errMsg').hidden = !wrongSel;
}
function renderSolved(st, solved) {
  if (!solved) return;
  $('#sInfo').textContent = st.info;
  $('#sSources').innerHTML = st.links.map((l) => externalLink(l.url, esc(l.title) + ' ↗')).join('');
}
function renderActionBar(st, p, has, gated, solved) {
  const bar = $('#action');
  let html = '';
  if (has && solved) {
    const next = nextUnsolved(st.id);
    html = '<button type="button" class="btn ghost" data-act="map" aria-label="Zurück zum Stadtplan">🗺️</button>';
    if (next) html += '<button type="button" class="btn" data-act="next" data-next-id="' + esc(next.id) + '">Nächste: ' + esc(categoryOf(next).name) + ' – Nr. ' + next.number + ' →</button>';
    else if (isEnabled('completion')) html += '<button type="button" class="btn" data-act="finale">🏆 Zum Finale</button>';
    else html += '<button type="button" class="btn" data-act="map">Zum Stadtplan</button>';
  } else if (has) {
    if (!gated && isEnabled('hint_button')) html += '<button type="button" class="btn ghost" data-act="hint" aria-expanded="' + String(p.hintVisible) + '" aria-controls="hintBox">💡 Hinweis</button>';
    if (!gated && isEnabled('reveal_button')) html += '<button type="button" class="btn ghost" data-act="reveal">Lösung anzeigen</button>';
  }
  if (bar.dataset.html !== html) { bar.innerHTML = html; bar.dataset.html = html; }
  bar.hidden = !html;
  $('#sScroll').style.paddingBottom = html ? '130px' : '40px';
}
function setupOrientMap(st) {
  if (!isEnabled('station_map') || typeof L === 'undefined') return;
  const container = $('#orientMap');
  if (!container) return;
  const orient = $('#orient');
  if (!container.dataset.resizeBound) {
    orient.addEventListener('toggle', () => {
      if (orient.open && stationMap) requestAnimationFrame(() => stationMap && stationMap.invalidateSize());
    });
    container.dataset.resizeBound = 'true';
  }
  if (!stationMap) {
    stationMap = L.map(container).setView([st.lat, st.lon], 17);
    L.tileLayer(MAP_TILE_URL, { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a>' }).addTo(stationMap);
    L.marker([st.lat, st.lon]).addTo(stationMap).bindPopup('Stationsziel');
    orientPlayerLayer = L.layerGroup().addTo(stationMap);
    U.orientKey = '';
    setTimeout(() => { if (stationMap) stationMap.invalidateSize(); }, 700);
  }
  const player = playerLocations.get(st.id);
  const key = player ? player.lat + ',' + player.lon : '';
  if (key === U.orientKey) return;
  U.orientKey = key;
  orientPlayerLayer.clearLayers();
  if (!player) return;
  const me = L.circleMarker([player.lat, player.lon], { radius: 8, color: '#1d4ed8', fillColor: '#3b82f6', fillOpacity: 0.9 }).addTo(orientPlayerLayer).bindPopup('Dein aktueller Standort');
  L.polyline([[player.lat, player.lon], [st.lat, st.lon]], { color: '#1d4ed8', dashArray: '6 6' }).addTo(orientPlayerLayer);
  stationMap.fitBounds([[st.lat, st.lon], me.getLatLng()], { padding: [24, 24], maxZoom: 17 });
}
function destroyOrientMap() { if (stationMap) { stationMap.remove(); stationMap = null; orientPlayerLayer = null; } }

/* ==========================================================
   9. Details laden & Standortprüfung
   ========================================================== */
function ensureDetails(id, options) {
  const station = stationsById.get(id);
  if (!station || detailsRequests.has(id)) return;
  const generation = locationGeneration;
  const needsPublic = !station.publicDetailsLoaded;
  const progress = Store.get(id);
  const passive = Boolean(options && options.passive);
  const needsProtected = !passive && isEnabled('location_gate') && (progress.locationVerified || progress.solved) && !station.protectedDetailsLoaded;
  if (!needsPublic && !needsProtected) return;
  if (needsPublic) { Store.detailsErrors.delete(id); renderIfSelected(id); }
  const request = (async () => {
    if (needsPublic) {
      const details = await Api.loadDetails(id);
      if (!isValidDetails(details, isEnabled('location_gate'))) throw new Error('Ungültige Stationsdetails');
      Object.assign(station, details);
      station.publicDetailsLoaded = true;
      if (!isEnabled('location_gate')) station.protectedDetailsLoaded = true;
    }
    const current = Store.get(id);
    if (generation === locationGeneration && isEnabled('location_gate') && (current.locationVerified || current.solved) && !station.protectedDetailsLoaded) {
      try { await loadProtectedDetails(station, generation); } catch (error) {
        if (current.locationVerified) Store.update(id, { locationVerified: false });
        locationStates.set(id, { message: 'Die Freigabe konnte nicht bestätigt werden. Bitte aktualisiere deinen Standort.' });
      }
    }
  })().catch(() => { if (needsPublic) Store.detailsErrors.add(id); })
    .finally(() => {
      detailsRequests.delete(id);
      if (U.selectedId === id) {
        renderStation();
        announce(Store.detailsErrors.has(id) ? 'Die Station konnte nicht geladen werden.' : 'Station ' + station.number + ' geladen.');
      }
      renderList();
    });
  detailsRequests.set(id, request);
}
function renderIfSelected(id) { if (U.selectedId === id) renderStation(); }
async function loadProtectedDetails(station, generation = locationGeneration) {
  const details = await Api.loadProtectedDetails(station.id);
  if (!isValidDetails(details)) throw new Error('Geschützte Stationsdetails konnten nicht geladen werden.');
  if (generation !== locationGeneration) return;
  Object.assign(station, details);
  station.protectedDetailsLoaded = true;
}
function clearProtectedDetails(station) {
  station.protectedDetailsLoaded = false;
  ['hint', 'options', 'correctOptionIndex', 'solutionTitle', 'solutionCaption', 'info', 'solutionImage', 'links'].forEach((k) => delete station[k]);
}
function locationFailureMessage(error) {
  if (error && error.code === 1) return 'Der Zugriff auf den Standort wurde verweigert.';
  if (error && error.code === 2) return 'Der Standort konnte nicht ermittelt werden.';
  if (error && error.code === 3) return 'Die Standortabfrage dauerte zu lange. Bitte erneut versuchen.';
  return 'Der Standort konnte nicht geprüft werden. Bitte erneut versuchen.';
}
function locationResultMessage(result) {
  if (result.reason === 'poor_accuracy') return 'Die Standortgenauigkeit ist derzeit zu gering (maximal ' + locationConfig.maxAccuracy + ' Meter).';
  if (result.reason === 'too_far') return 'Du bist noch etwa ' + result.distance + ' Meter vom Ziel entfernt (Prüfungsradius: ' + (result.radius || locationConfig.radius) + ' Meter).';
  return 'Der Standort konnte nicht geprüft werden.';
}
// DEV-FAKE-POSITION (2/3): Entwicklerhilfe, vor dem Livegang entfernen (nach "DEV-FAKE-POSITION" suchen)
function devFakePosition(stationId) {
  const station = stationsById.get(stationId);
  return { coords: { latitude: station.lat, longitude: station.lon, accuracy: 5 }, timestamp: Date.now() };
}
// /DEV-FAKE-POSITION
async function verifyStationLocation(stationId, devFake = false) {
  const current = locationStates.get(stationId) || {};
  const generation = locationGeneration;
  if (current.loading) return;
  const refresh = () => renderIfSelected(stationId);
  if (!devFake && !navigator.geolocation) {
    locationStates.set(stationId, { message: 'Die Standortbestimmung wird von diesem Browser nicht unterstützt.' });
    refresh();
    return;
  }
  locationStates.set(stationId, { loading: true, message: 'Standort wird ermittelt …' });
  refresh();
  try {
    const position = devFake
      ? devFakePosition(stationId) /* DEV-FAKE-POSITION (3/3) */
      : await new Promise((resolve, reject) => navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true, maximumAge: 0, timeout: LOCATION_TIMEOUT_MS }));
    const { latitude, longitude, accuracy } = position.coords;
    const timestamp = position.timestamp, now = Date.now();
    if (![latitude, longitude, accuracy, timestamp].every(Number.isFinite) || latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180 || accuracy <= 0 || timestamp < now - LOCATION_MAX_AGE_MS || timestamp > now + 30000) {
      locationStates.set(stationId, { message: 'Der Standort ist veraltet oder ungültig. Bitte erneut versuchen.' });
      return;
    }
    if (generation !== locationGeneration) return;
    playerLocations.set(stationId, { lat: latitude, lon: longitude });
    locationStates.set(stationId, { loading: true, message: 'Standort wird geprüft …' });
    refresh();
    const verification = Api.verifyLocation(stationId, { lat: latitude, lon: longitude, accuracy, timestamp });
    pendingLocationRequests.add(verification);
    let result;
    try { result = await verification; } finally { pendingLocationRequests.delete(verification); }
    if (generation !== locationGeneration) return;
    if (!result.success) { locationStates.set(stationId, { message: locationResultMessage(result) }); return; }
    Stats.track('location_verified', stationId);
    locationStates.set(stationId, { loading: true, message: 'Standort bestätigt. Antworten werden geladen …' });
    Store.update(stationId, { locationVerified: true });
    const station = stationsById.get(stationId);
    await loadProtectedDetails(station, generation);
    if (generation !== locationGeneration) { clearProtectedDetails(station); return; }
    locationStates.set(stationId, { message: 'Standort bestätigt. Die Antworten sind freigegeben.' });
    buzz(20);
    announce('Standort bestätigt. Die Antwortmöglichkeiten sind freigegeben.');
  } catch (error) {
    if (generation !== locationGeneration) return;
    if (Store.get(stationId).locationVerified) Store.update(stationId, { locationVerified: false });
    locationStates.set(stationId, { message: locationFailureMessage(error) });
    announce(locationFailureMessage(error));
  } finally {
    if (generation === locationGeneration) {
      locationStates.set(stationId, { ...(locationStates.get(stationId) || {}), loading: false });
      refresh();
    }
  }
}

/* ==========================================================
   10. Aktionen in der Station
   ========================================================== */
function markSolved(id, selectedOptionIndex) {
  const st = stationsById.get(id);
  Store.update(id, { solved: true, selectedOptionIndex });
  if (selectedOptionIndex !== null) Stats.track('solved', id, Store.get(id).attempts);
  if (countSolved() === stations.length) Stats.track('game_completed');
  if (U.selectedId === id) { renderStation(); renderProgress(); renderList(); updateMapPins(); }
  else renderAll();
  announce('Station ' + st.number + ' gelöst: ' + st.solutionTitle + (countSolved() === stations.length ? '. Alle Aufgaben sind gelöst.' : ''));
  setTimeout(() => { const v = $('.verdict'); if (v && U.selectedId === id) v.scrollIntoView({ behavior: reducedMotion() ? 'auto' : 'smooth', block: 'center' }); }, 450);
}
function selectOption(id, index, btn) {
  const st = stationsById.get(id);
  if (isEnabled('location_gate') && !st.protectedDetailsLoaded) return;
  if (index === st.correctOptionIndex) {
    btn.classList.add('option--right');
    btn.closest('.options').classList.add('locked');
    buzz([30, 40, 30]);
    setTimeout(() => markSolved(id, index), reducedMotion() ? 0 : 650);
    return;
  }
  U.freshWrong = index;
  Store.update(id, { attempts: Store.get(id).attempts + 1, selectedOptionIndex: index });
  Stats.track('wrong_answer', id, Store.get(id).attempts);
  buzz(80);
  renderStation();
  announce('Noch nicht – probier es nochmal oder nutze den Hinweis.');
}
function toggleHint() {
  const id = U.selectedId, station = stationsById.get(id);
  if (isEnabled('location_gate') && (!station || !station.protectedDetailsLoaded)) return;
  const p = Store.get(id), visible = !p.hintVisible;
  if (visible && !p.hintUsed) Stats.track('hint_used', id);
  Store.update(id, { hintUsed: p.hintUsed || visible, hintVisible: visible });
  renderStation();
  renderProgress();
}
function goToNext(nextId) {
  const st = $('#station');
  const dur = reducedMotion() ? 0 : 300;
  st.style.transition = 'transform ' + dur + 'ms var(--ease),opacity ' + dur + 'ms';
  st.style.transform = 'translateX(-40px)'; st.style.opacity = '0';
  setTimeout(() => {
    st.style.transition = 'none'; st.style.transform = 'translateX(40px)';
    void st.offsetWidth;
    updateStationUrl(nextId, 'push');
    openStation(nextId, { source: 'internal', trigger: null });
    st.style.transition = 'transform .45s var(--ease),opacity .3s'; st.style.transform = ''; st.style.opacity = '';
    setTimeout(() => { st.style.transition = ''; }, 500);
  }, dur + 30);
}
function handleStationClick(event) {
  const btn = event.target.closest('[data-act]');
  if (!btn) return;
  const id = U.selectedId;
  switch (btn.dataset.act) {
    case 'teaser':
      U.teaserExpanded = !U.teaserExpanded;
      renderTeaser(stationsById.get(id), hasDetails(stationsById.get(id)));
      break;
    case 'verify': verifyStationLocation(id); break;
    case 'fake': verifyStationLocation(id, true); break; // DEV-FAKE-POSITION
    case 'option': selectOption(id, Number(btn.dataset.i), btn); break;
    case 'hint': toggleHint(); break;
    case 'reveal':
      if (isEnabled('location_gate') && !stationsById.get(id).protectedDetailsLoaded) break;
      Store.update(id, { revealed: true });
      Stats.track('solution_shown', id);
      markSolved(id, null);
      break;
    case 'retry': Store.detailsErrors.delete(id); renderStation(); ensureDetails(id); break;
    case 'next': goToNext(btn.dataset.nextId); break;
    case 'map': closeStation(); break;
    case 'finale': showFinale(); break;
  }
}

/* ==========================================================
   11. Modals, Onboarding, Reset, Finale
   ========================================================== */
let openModalEl = null, modalTrigger = null;
function showModal(sel, trigger) {
  const el = $(sel);
  if (!el) return;
  modalTrigger = trigger || document.activeElement;
  openModalEl = el;
  $('#scrim').classList.add('on');
  el.classList.add('on');
  setTimeout(() => { const h = el.querySelector('h3'); if (h) h.focus({ preventScroll: true }); }, 320);
}
function closeModal(restore = true) {
  if (!openModalEl) return;
  openModalEl.classList.remove('on');
  $('#scrim').classList.remove('on');
  openModalEl = null;
  if (restore && modalTrigger && document.body.contains(modalTrigger)) modalTrigger.focus({ preventScroll: true });
}
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    if ($('#imageViewer').classList.contains('on')) { closeImageViewer(); return; }
    if ($('#finale').classList.contains('on')) { hideFinale(); return; }
    if (openModalEl) { closeModal(); return; }
    if (U.selectedId) closeStation();
  }
  if (e.key === 'Tab' && openModalEl) {
    const f = Array.from(openModalEl.querySelectorAll('button,input,select,textarea,a[href],[tabindex="0"]')).filter((x) => !x.disabled && x.offsetParent !== null);
    if (!f.length) return;
    const first = f[0], last = f[f.length - 1];
    if (e.shiftKey && (document.activeElement === first || !openModalEl.contains(document.activeElement))) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  }
});
$('#scrim').addEventListener('click', () => closeModal());

const HELP = [
  ['📍', 'Pin antippen', 'Wähle eine Station auf der Karte oder in der Liste.'],
  ['🔍', 'Ort finden', 'Suche den Ort auf dem Foto. Nutze die Karte. Sobald du in der Nähe bist, tauchen 3 Fragen/Themen auf.'],
  ['💡', 'Frage beantworten', 'Beantworte die Frage. Bei Bedarf hilft der Hinweis. Die richtige Antwort verrät die Geschichte dahinter.'],
];
let helpIndex = 0;
function renderHelp() {
  const h = HELP[helpIndex];
  $('#hlBody').innerHTML = '<div class="em" aria-hidden="true">' + h[0] + '</div><b style="font-size:20px">' + (helpIndex + 1) + '. ' + h[1] + '</b><p style="color:var(--muted)">' + h[2] + '</p>';
  $('#hlDots').innerHTML = HELP.map((_, i) => '<i class="' + (i <= helpIndex ? 'on' : '') + '"></i>').join('');
  $('#hlNext').textContent = helpIndex === HELP.length - 1 ? 'Los geht\'s' : 'Weiter';
}
function openHelp(trigger) { helpIndex = 0; renderHelp(); showModal('#m-help', trigger); }
function markIntroSeen() { try { localStorage.setItem(INTRO_STATE_KEY, 'closed'); } catch (e) { /* egal */ } }
function shouldShowIntro() { try { return localStorage.getItem(INTRO_STATE_KEY) === null; } catch (e) { return false; } }

function confirmProgressReset() {
  locationGeneration += 1;
  Store.reset();
  Stats.newPlayer();
  playerLocations.clear();
  locationStates.clear();
  if (isEnabled('location_gate')) stations.forEach(clearProtectedDetails);
  Promise.all(Array.from(pendingLocationRequests, (r) => r.catch(() => {}))).then(() => Api.resetLocationAccess()).catch(() => {});
  closeModal(false);
  hideFinale();
  renderAll();
  if (U.selectedId) { U.optionsKey = ''; renderStation(); const id = U.selectedId; ensureDetails(id); }
  announce('Fortschritt zurückgesetzt.');
  toast('Fortschritt zurückgesetzt');
}

function showFinale() {
  if (!isEnabled('completion')) return;
  const st = computeStats();
  $('#finStats').innerHTML = statsHtml(st);
  $('#finRank').textContent = rankText(st);
  const fin = $('#finale');
  fin.classList.add('on');
  const cols = ['#fff', '#ffd166', '#ef476f', '#06d6a0', '#118ab2'];
  if (!reducedMotion()) for (let i = 0; i < 60; i++) {
    const c = document.createElement('i');
    c.className = 'conf';
    c.style.cssText = 'left:' + Math.random() * 100 + '%;background:' + cols[i % 5] + ';animation-duration:' + (2.5 + Math.random() * 2.5) + 's;animation-delay:' + Math.random() * 1.2 + 's';
    fin.appendChild(c);
    setTimeout(() => c.remove(), 6500);
  }
  setTimeout(() => $('#finT').focus({ preventScroll: true }), 500);
}
function hideFinale() { $('#finale').classList.remove('on'); }

/* ==========================================================
   12. Station vorschlagen
   ========================================================== */
const SUGGEST_LIMITS = { name: 100, description: 1000 };
function validateSuggestion(raw) {
  const errors = {};
  const text = (k) => String(raw[k] == null ? '' : raw[k]).trim();
  const name = text('name'), description = text('description');
  if (!name) errors.name = 'Bitte gib einen Ort an.';
  else if (name.length > SUGGEST_LIMITS.name) errors.name = 'Der Ort darf höchstens ' + SUGGEST_LIMITS.name + ' Zeichen lang sein.';
  if (!description) errors.description = 'Bitte gib eine kurze Beschreibung an.';
  else if (description.length > SUGGEST_LIMITS.description) errors.description = 'Die Beschreibung darf höchstens ' + SUGGEST_LIMITS.description + ' Zeichen lang sein.';
  if (Object.keys(errors).length) return { errors, value: null };
  return { errors: {}, value: { version: 1, name, description } };
}
const SG = { submitting: false, errMap: { name: '#sgNameErr', description: '#sgDescErr' }, fieldMap: { name: '#sgName', description: '#sgDesc' } };
const sgRaw = () => Object.fromEntries(new FormData($('#sgForm')));
function sgShowErrors(errors) {
  Object.entries(SG.errMap).forEach(([key, sel]) => {
    const el = $(sel);
    const msg = errors[key] || '';
    el.textContent = msg; el.hidden = !msg;
  });
  [['name', '#sgName'], ['description', '#sgDesc']].forEach(([key, sel]) => $(sel).setAttribute('aria-invalid', errors[key] ? 'true' : 'false'));
}
function sgStatus(kind, msg) { const s = $('#sgStatus'); s.dataset.kind = kind; s.textContent = msg; s.hidden = !msg; }
function sgReset() {
  $('#sgForm').reset(); sgShowErrors({}); sgStatus('', '');
  SG.submitting = false;
  $('#sgForm').hidden = false; $('#sgDone').hidden = true;
}
async function sendSuggestion(payload) {
  document.dispatchEvent(new CustomEvent('ms:stationSuggested', { detail: payload }));
  const preview = new URLSearchParams(window.location.search).get('preview');
  let response;
  try {
    response = await fetch(Api.urls.submit + (preview === null ? '' : '?preview=' + encodeURIComponent(preview)), { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(payload) });
  } catch (error) { throw new Error('Keine Verbindung zum Server. Bitte prüfe deine Internetverbindung und sende den Vorschlag erneut ab.'); }
  let body = {};
  try { body = await response.json(); } catch (e) { body = {}; }
  if (!response.ok) {
    const serverMessage = body.error && body.error.message;
    if (response.status === 422 && body.error && body.error.code === 'validation_failed') throw new Error('Der Vorschlag wurde nicht akzeptiert: ' + serverMessage + ' Bitte korrigiere die Angaben und sende erneut ab.');
    if (response.status === 429) throw new Error('Du hast in kurzer Zeit zu viele Vorschläge gesendet. Bitte warte etwa eine Stunde und versuche es dann erneut.');
    if (response.status === 403 && body.error && body.error.code === 'suggestions_disabled') throw new Error('Vorschläge sind derzeit nicht verfügbar.');
    throw new Error('Der Server konnte den Vorschlag gerade nicht verarbeiten (Fehler ' + response.status + '). Deine Eingaben bleiben erhalten – bitte versuche es in ein paar Minuten erneut.');
  }
  return body.data;
}
async function sgSubmit(event) {
  event.preventDefault();
  if (SG.submitting) return;
  const { errors, value } = validateSuggestion(sgRaw());
  const keys = Object.keys(errors);
  sgShowErrors(errors);
  if (keys.length) {
    sgStatus('error', 'Bitte korrigiere die markierten Felder.');
    $(SG.fieldMap[keys[0]]).focus();
    return;
  }
  SG.submitting = true;
  $('#sgNext').disabled = true;
  sgStatus('', 'Wird gesendet ...');
  try {
    await sendSuggestion(value);
    $('#sgForm').hidden = true; $('#sgDone').hidden = false;
    $('#sgSuccess').focus();
    announce('Vorschlag erfolgreich übermittelt.');
  } catch (error) {
    sgStatus('error', error.message || 'Der Vorschlag konnte nicht verarbeitet werden. Bitte versuche es erneut.');
  } finally {
    SG.submitting = false;
    $('#sgNext').disabled = false;
  }
}

/* ==========================================================
   12b. Installationshinweis (optional, kein Offline-Betrieb)
   ========================================================== */
const INSTALL_HINT_KEY = 'muenster-unvermutet:install-hint';
let deferredInstallPrompt = null;
const isStandalone = () => (window.matchMedia && matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true;
const isIos = () => /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
function installHintDismissed() { try { return localStorage.getItem(INSTALL_HINT_KEY) !== null; } catch (e) { return true; } }
function dismissInstallHint() {
  $('#installHint').hidden = true;
  try { localStorage.setItem(INSTALL_HINT_KEY, 'dismissed'); } catch (e) { /* nur Sitzung */ }
}
function maybeShowInstallHint(attempt = 0) {
  if (isStandalone() || installHintDismissed()) return;
  const ios = isIos();
  if (!ios && !deferredInstallPrompt) return;
  // Nicht über Einwilligung, Einführung oder andere Dialoge legen.
  if (openModalEl) { if (attempt < 30) setTimeout(() => maybeShowInstallHint(attempt + 1), 2000); return; }
  const body = $('#installHintBody');
  body.textContent = '';
  const add = (text) => { const p = document.createElement('p'); p.textContent = text; body.appendChild(p); };
  if (ios) {
    const safari = !/CriOS|FxiOS|EdgiOS|OPiOS/.test(navigator.userAgent);
    add(safari
      ? 'Tippe in Safari auf „Teilen“ und dann auf „Zum Home-Bildschirm“, um Münster unvermutet wie eine App zu starten.'
      : 'Über das Teilen-Menü deines Browsers kannst du Münster unvermutet je nach iOS-Version zum Home-Bildschirm hinzufügen.');
    add('Hinweis: Dein bisheriger Spielstand aus dem Browser wird in der Home-Bildschirm-App möglicherweise nicht übernommen.');
  } else {
    add('Du kannst Münster unvermutet auf deinem Gerät installieren und wie eine App starten.');
  }
  add('Für das Spiel ist weiterhin eine Internetverbindung nötig.');
  $('#installHintGo').hidden = ios || !deferredInstallPrompt;
  $('#installHint').hidden = false;
}
window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredInstallPrompt = e;
  if (initialized) maybeShowInstallHint();
});
window.addEventListener('appinstalled', () => { deferredInstallPrompt = null; dismissInstallHint(); });
$('#installHintClose').addEventListener('click', dismissInstallHint);
$('#installHintGo').addEventListener('click', async () => {
  const prompt = deferredInstallPrompt;
  deferredInstallPrompt = null;
  if (!prompt) { dismissInstallHint(); return; }
  try { await prompt.prompt(); } catch (e) { /* egal */ }
  dismissInstallHint();
});

/* ==========================================================
   13. Look & Barrierefreiheit
   ========================================================== */
function syncThemeColor(look) {
  // Überschreibt die Medienabfragen, wenn der Look manuell gewählt wurde.
  document.querySelectorAll('meta[name="theme-color"]').forEach((m) => m.setAttribute('content', look === 'night' ? '#0b0f17' : '#f2f2f7'));
}
function setLook(look, persist = true) {
  look = look === 'night' ? 'night' : 'clean';
  document.documentElement.dataset.look = look;
  syncThemeColor(look);
  $$('#lookSeg button').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.look === look)));
  if (persist) { try { localStorage.setItem(LOOK_KEY, look); } catch (e) { /* nur Sitzung */ } }
}
function setAccessible(on, persist = true) {
  document.body.classList.toggle('layout-accessible', on);
  $('#setA11y').setAttribute('aria-checked', String(on));
  if (persist) { try { localStorage.setItem(LAYOUT_KEY, on ? 'accessible' : 'classic'); } catch (e) { /* nur Sitzung */ } }
}

/* ==========================================================
   14. Start
   ========================================================== */
function showLoadError(message, canRetry) {
  $('#loadErrorText').textContent = message;
  $('#loadRetry').hidden = !canRetry;
  $('#loadError').hidden = false;
}
function handleStationUrlChange(initial = false) {
  if (U.loading) return;
  const url = new URL(window.location.href), id = url.searchParams.get('station');
  if (!url.searchParams.has('station')) { if (U.selectedId) closeStation({ updateUrl: false }); return; }
  if (!stationsById.has(id)) {
    if (U.selectedId) closeStation({ updateUrl: false });
    toast('Der Link verweist auf eine unbekannte Station.');
    announce('Der Link verweist auf eine unbekannte Station.');
    return;
  }
  if (U.selectedId !== id) openStation(id, { source: initial ? 'direct-link' : 'history' });
}
let initialized = false;
function initializeUi() {
  if (initialized) return;
  initialized = true;
  applyUiSettings();
  initSheet();
  $('#tabs').addEventListener('click', (e) => { const t = e.target.closest('.tab'); if (t) go(t.dataset.t); });
  $('#ring').addEventListener('click', () => go('progress', false));
  document.addEventListener('click', (e) => {
    const chip = e.target.closest('.chip');
    if (chip) setCategoryFilter(chip.dataset.c || null);
  });
  $('#list').addEventListener('click', (e) => { const row = e.target.closest('[data-station-id]'); if (row) openStation(row.dataset.stationId, { trigger: row }); });
  $('#station').addEventListener('click', handleStationClick);
  $('#station').addEventListener('click', (e) => {
    const img = e.target.closest('.hero-img[role="button"]');
    if (img) openImageViewer(img);
  });
  $('#station').addEventListener('keydown', (e) => {
    const img = e.target.closest('.hero-img[role="button"]');
    if (img && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); openImageViewer(img); }
  });
  $('#imageViewerClose').addEventListener('click', () => closeImageViewer());
  $('#imageViewer').addEventListener('click', (e) => { if (e.target === e.currentTarget) closeImageViewer(); });
  $('#back').addEventListener('click', () => closeStation());
  (() => {
    let x0 = 0, y0 = 0;
    const st = $('#station');
    st.addEventListener('touchstart', (e) => { x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; }, { passive: true });
    st.addEventListener('touchend', (e) => { const dx = e.changedTouches[0].clientX - x0, dy = e.changedTouches[0].clientY - y0; if (x0 < 40 && dx > 90 && Math.abs(dy) < 60) closeStation(); }, { passive: true });
  })();
  $('#loadRetry').addEventListener('click', loadStations);
  $('#lookSeg').addEventListener('click', (e) => { const b = e.target.closest('button'); if (b) setLook(b.dataset.look); });
  $('#setA11y').addEventListener('click', () => { const on = $('#setA11y').getAttribute('aria-checked') !== 'true'; setAccessible(on); announce(on ? 'Barrierefrei: größere Pins und Schrift.' : 'Barrierefrei aus.'); });
  const renderStatsSwitch = () => $('#setStats').setAttribute('aria-checked', String(Stats.enabled()));
  renderStatsSwitch();
  $('#setStats').addEventListener('click', () => { const on = !Stats.enabled(); Stats.setConsent(on); renderStatsSwitch(); announce(on ? 'Anonyme Statistik erlaubt.' : 'Anonyme Statistik nicht erlaubt.'); });
  const answerConsent = (granted) => {
    Stats.setConsent(granted);
    renderStatsSwitch();
    closeModal(false);
    if (shouldShowIntro() && !U.selectedId) setTimeout(() => openHelp(null), 400);
  };
  $('#coYes').addEventListener('click', () => answerConsent(true));
  $('#coNo').addEventListener('click', () => answerConsent(false));
  $('#openHelp').addEventListener('click', (e) => openHelp(e.currentTarget));
  $('#openHelpMap').addEventListener('click', (e) => openHelp(e.currentTarget));
  $('#hlNext').addEventListener('click', () => { if (helpIndex === HELP.length - 1) { markIntroSeen(); closeModal(); } else { helpIndex += 1; renderHelp(); } });
  $('#openPrivacy').addEventListener('click', (e) => showModal('#m-privacy', e.currentTarget));
  $('#pvClose').addEventListener('click', () => closeModal());
  $('#openReset').addEventListener('click', (e) => showModal('#m-reset', e.currentTarget));
  $('#rsNo').addEventListener('click', () => closeModal());
  $('#rsYes').addEventListener('click', confirmProgressReset);
  if (isEnabled('completion')) {
    $('#openFinale').addEventListener('click', showFinale);
    $('#finMap').addEventListener('click', () => { hideFinale(); if (U.selectedId) closeStation(); go('map'); });
    $('#finAgain').addEventListener('click', () => showModal('#m-reset', $('#finAgain')));
  }
  if (isEnabled('suggest')) {
    $('#openSuggest').addEventListener('click', (e) => { sgReset(); showModal('#m-suggest', e.currentTarget); });
    $('#sgForm').addEventListener('submit', sgSubmit);
    $('#sgBack').addEventListener('click', () => closeModal());
    $('#sgClose').addEventListener('click', () => closeModal());
    $('#sgAnother').addEventListener('click', () => { sgReset(); $('#sgName').focus(); });
  }
  window.addEventListener('popstate', () => handleStationUrlChange());
  const fitViewport = () => {
    document.documentElement.style.setProperty('--app-h', window.innerHeight + 'px');
    window.scrollTo(0, 0);
    if (sheet) setSnap(U.snap);
    if (map) map.invalidateSize();
  };
  fitViewport();
  window.addEventListener('resize', fitViewport);
  window.addEventListener('orientationchange', fitViewport);
  setLook(document.documentElement.dataset.look, false);
  let accessible = false;
  try { accessible = localStorage.getItem(LAYOUT_KEY) === 'accessible'; } catch (e) { /* Standard */ }
  setAccessible(accessible, false);
  initMap();
  go('map');
  renderChips(); renderList(); renderProgress();
}
async function loadStations() {
  $('#loadError').hidden = true;
  U.loading = true;
  renderList(); renderProgress();
  try {
    const settingsPromise = Api.loadSettings().catch(() => null);
    const stationsPromise = Promise.all([Api.loadCategories(), Api.loadPins()]).then((data) => ({ data }), (error) => ({ error }));
    const [settings, result] = await Promise.all([settingsPromise, stationsPromise]);
    if (!initialized) {
      ui = resolveUi(settings && settings.ui);
      const lc = settings && settings.location;
      locationConfig = { radius: Number.isFinite(lc && lc.radius) ? lc.radius : 50, maxAccuracy: Number.isFinite(lc && lc.maxAccuracy) ? lc.maxAccuracy : 50 };
      renderPublicText(settings && settings.publicText);
      initializeUi();
    }
    if (result.error) throw result.error;
    const [categories, pins] = result.data;
    if (!Array.isArray(categories) || !categories.every(isValidCategory)) throw new Error('Ungültige Kategoriedaten');
    if (!Array.isArray(pins) || !pins.every(isValidPin)) throw new Error('Ungültige Stationsdaten');
    categoriesById.clear();
    categories.forEach((c) => categoriesById.set(c.id, c));
    stations = pins.map((p) => ({ ...p }));
    stationsById.clear();
    stations.forEach((s) => stationsById.set(s.id, s));
    U.loading = false;
    addMarkers();
    renderAll();
    if (!stations.length) showLoadError('Aktuell sind keine Stationen verfügbar.', false);
    handleStationUrlChange(true);
    if (Stats.consent() === null) setTimeout(() => showModal('#m-consent', null), 500);
    else if (!U.selectedId && shouldShowIntro()) setTimeout(() => openHelp(null), 500);
    setTimeout(maybeShowInstallHint, 1500);
  } catch (error) {
    U.loading = false;
    if (!initialized) { initializeUi(); }
    renderAll();
    showLoadError('Kategorien oder Stationen konnten nicht geladen werden. Für das Spiel ist eine Internetverbindung nötig.', true);
  }
}
loadStations();
