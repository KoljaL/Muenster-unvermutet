<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$admin = require_admin();
$pdo = db();

$id = isset($_GET['id']) && is_string($_GET['id']) ? $_GET['id'] : null;
$suggestionId = isset($_GET['suggestion']) && ctype_digit((string) $_GET['suggestion']) ? (int) $_GET['suggestion'] : null;

$st = ['id' => '', 'number' => '', 'title' => '', 'hint' => '', 'lat' => '51.9622', 'lon' => '7.6278', 'category' => 'detail',
    'status' => 'published', 'solution_title' => '', 'solution_caption' => '', 'info' => '', 'task_image' => '', 'solution_image' => '',
    'options' => ['', '', '', ''], 'correct' => 0, 'links' => []];

if ($id !== null) {
    $stmt = $pdo->prepare('SELECT * FROM stations WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash('err', 'Station nicht gefunden.');
        redirect('index.php');
    }
    $st = array_merge($st, $row);
    $st['correct'] = (int) $row['correct_option_index'];
    $o = $pdo->prepare('SELECT option_text FROM options WHERE station_id = ? ORDER BY option_index');
    $o->execute([$id]);
    $st['options'] = array_pad($o->fetchAll(PDO::FETCH_COLUMN), 4, '');
    $l = $pdo->prepare('SELECT title, url FROM links WHERE station_id = ? ORDER BY id');
    $l->execute([$id]);
    $st['links'] = $l->fetchAll();
} elseif ($suggestionId !== null) {
    $stmt = $pdo->prepare("SELECT * FROM station_suggestions WHERE id = ? AND status = 'pending'");
    $stmt->execute([$suggestionId]);
    $sg = $stmt->fetch();
    if (!$sg) {
        flash('err', 'Vorschlag nicht gefunden oder bereits bearbeitet.');
        redirect('suggestions.php');
    }
    $st = array_merge($st, [
        'title' => $sg['title'], 'hint' => $sg['hint'], 'lat' => $sg['lat'], 'lon' => $sg['lon'], 'info' => $sg['info'],
        'solution_title' => $sg['title'], 'task_image' => (string) $sg['task_image'], 'solution_image' => (string) $sg['solution_image'],
        'options' => array_pad((array) json_decode($sg['options_json'], true), 4, ''), 'correct' => (int) $sg['correct_option_index'],
    ]);
}

// Nach fehlgeschlagener Validierung die Eingaben wieder einsetzen.
if (!empty($_SESSION['old_input'])) {
    $old = $_SESSION['old_input'];
    unset($_SESSION['old_input']);
    $st = array_merge($st, array_intersect_key($old, $st));
    $st['options'] = array_pad(array_values((array) ($old['options'] ?? [])), 4, '');
    $st['correct'] = (int) ($old['correct'] ?? 0);
    $st['links'] = [];
    foreach ((array) ($old['link_url'] ?? []) as $i => $u) {
        $st['links'][] = ['title' => $old['link_title'][$i] ?? '', 'url' => $u];
    }
}
$st['links'] = array_pad($st['links'], max(3, count($st['links']) + 1), ['title' => '', 'url' => '']);
$isNew = $id === null;

$head = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">';
admin_header($isNew ? 'Neue Station' : 'Station bearbeiten', $admin, $head);
$img = static fn(string $p): string => $p !== '' ? '../' . h($p) : '';
?>
<h1><?= $isNew ? 'Neue Station' : 'Station bearbeiten' ?></h1>
<form method="post" action="station-save.php" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="mode" value="<?= $isNew ? 'create' : 'update' ?>">
  <input type="hidden" name="orig_id" value="<?= h($id ?? '') ?>">
  <input type="hidden" name="suggestion_id" value="<?= h($suggestionId ?? '') ?>">
  <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) config('upload_max_bytes') ?>">

  <fieldset><legend>Stammdaten</legend>
    <div class="row">
      <div><label for="id">ID (stabil, nicht änderbar)</label>
        <input type="text" id="id" name="id" value="<?= h($st['id']) ?>" pattern="[a-z0-9][a-z0-9\-]{0,79}" <?= $isNew ? 'placeholder="leer = automatisch"' : 'readonly' ?>></div>
      <div><label for="number">Nummer</label>
        <input type="number" min="1" id="number" name="number" value="<?= h($st['number']) ?>" placeholder="leer = nächste"></div>
      <div><label for="category">Kategorie</label>
        <select id="category" name="category"><?php foreach (config('categories') as $c): ?>
          <option value="<?= h($c) ?>" <?= $c === $st['category'] ? 'selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?></select></div>
      <div><label for="status">Status</label>
        <select id="status" name="status">
          <option value="published" <?= $st['status'] === 'published' ? 'selected' : '' ?>>veröffentlicht</option>
          <option value="archived" <?= $st['status'] === 'archived' ? 'selected' : '' ?>>archiviert</option></select></div>
    </div>
    <label for="title">Titel (Rätselüberschrift)</label>
    <input type="text" id="title" name="title" required maxlength="200" value="<?= h($st['title']) ?>">
    <label for="hint">Hinweis</label>
    <textarea id="hint" name="hint" required maxlength="1000"><?= h($st['hint']) ?></textarea>
  </fieldset>

  <fieldset><legend>Standort</legend>
    <div class="row">
      <div><label for="lat">Breitengrad</label><input type="text" id="lat" name="lat" required value="<?= h($st['lat']) ?>"></div>
      <div><label for="lon">Längengrad</label><input type="text" id="lon" name="lon" required value="<?= h($st['lon']) ?>"></div>
    </div>
    <p class="muted">Klick auf die Karte setzt die Koordinaten.</p>
    <div id="picker"></div>
  </fieldset>

  <fieldset><legend>Frage &amp; Antworten (2–4 Optionen, leere werden ignoriert)</legend>
    <?php foreach ($st['options'] as $i => $text): ?>
      <div class="opt">
        <input type="radio" name="correct" value="<?= $i ?>" id="c<?= $i ?>" <?= (int) $st['correct'] === $i ? 'checked' : '' ?> aria-label="Richtige Antwort <?= $i + 1 ?>">
        <input type="text" name="options[<?= $i ?>]" maxlength="500" value="<?= h($text) ?>" aria-label="Option <?= $i + 1 ?>">
      </div>
    <?php endforeach; ?>
    <p class="muted">Der Punkt markiert die richtige Antwort.</p>
  </fieldset>

  <fieldset><legend>Lösung</legend>
    <label for="solution_title">Lösungstitel</label>
    <input type="text" id="solution_title" name="solution_title" required maxlength="300" value="<?= h($st['solution_title']) ?>">
    <label for="solution_caption">Bildunterschrift Lösungsfoto (optional)</label>
    <input type="text" id="solution_caption" name="solution_caption" maxlength="300" value="<?= h($st['solution_caption']) ?>">
    <label for="info">Info-Text</label>
    <textarea id="info" name="info" required maxlength="8000" style="min-height:12rem"><?= h($st['info']) ?></textarea>
  </fieldset>

  <fieldset><legend>Bilder (JPEG/PNG, max. <?= (int) (config('upload_max_bytes') / 1048576) ?> MB, <?= (int) config('upload_max_edge') ?> px)</legend>
    <label for="task_image">Aufgabenfoto</label>
    <?php if ($st['task_image'] !== ''): ?><img class="thumb" src="<?= $img($st['task_image']) ?>" alt="Aufgabenfoto"><?php endif; ?>
    <input type="file" id="task_image" name="task_image" accept="image/jpeg,image/png">
    <label for="solution_image">Lösungsfoto</label>
    <?php if ($st['solution_image'] !== ''): ?><img class="thumb" src="<?= $img($st['solution_image']) ?>" alt="Lösungsfoto"><?php endif; ?>
    <input type="file" id="solution_image" name="solution_image" accept="image/jpeg,image/png">
  </fieldset>

  <fieldset><legend>Weiterführende Links (nur http/https)</legend>
    <?php foreach ($st['links'] as $l): ?>
      <div class="row">
        <input type="text" name="link_title[]" maxlength="200" placeholder="Titel" value="<?= h($l['title']) ?>" aria-label="Linktitel">
        <input type="url" name="link_url[]" maxlength="500" placeholder="https://…" value="<?= h($l['url']) ?>" aria-label="Link-URL">
      </div>
    <?php endforeach; ?>
    <p class="muted">Weitere Links: speichern, dann erscheint eine zusätzliche leere Zeile.</p>
  </fieldset>

  <p><button>Speichern</button> <a class="btn secondary" href="index.php">Abbrechen</a></p>
</form>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  (function () {
    var latEl = document.getElementById('lat'), lonEl = document.getElementById('lon');
    function pos() {
      var a = parseFloat(latEl.value.replace(',', '.')), b = parseFloat(lonEl.value.replace(',', '.'));
      return isNaN(a) || isNaN(b) ? [51.9622, 7.6278] : [a, b];
    }
    if (typeof L === 'undefined') return;
    var map = L.map('picker').setView(pos(), 16);
    L.tileLayer('https://tile.openstreetmap.de/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap-Mitwirkende' }).addTo(map);
    var marker = L.marker(pos()).addTo(map);
    map.on('click', function (e) {
      marker.setLatLng(e.latlng);
      latEl.value = e.latlng.lat.toFixed(6);
      lonEl.value = e.latlng.lng.toFixed(6);
    });
    function sync() { marker.setLatLng(pos()); map.panTo(pos()); }
    latEl.addEventListener('change', sync);
    lonEl.addEventListener('change', sync);
  })();
</script>
<?php admin_footer();
