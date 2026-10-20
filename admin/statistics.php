<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$admin = require_admin();

const EVENT_LABELS = [
    'opened' => 'Station geöffnet',
    'location_verified' => 'Standort erreicht',
    'hint_used' => 'Hinweis genutzt',
    'wrong_answer' => 'Falsche Antwort',
    'solution_shown' => 'Lösung angezeigt',
    'solved' => 'Richtig beantwortet',
    'game_completed' => 'Spiel abgeschlossen',
];

$ranges = [7 => '7 Tage', 30 => '30 Tage', 90 => '90 Tage', 0 => 'Gesamt'];
$days = isset($_GET['days']) && array_key_exists((int) $_GET['days'], $ranges) ? (int) $_GET['days'] : 30;
$from = $days > 0 ? date('Y-m-d', strtotime('-' . ($days - 1) . ' days')) : '0000-00-00';
$day = is_string($_GET['day'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['day']) ? $_GET['day'] : null;

$pdo = db();

function stat_value(PDO $pdo, string $sql, array $params): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

$players = stat_value($pdo, 'SELECT COUNT(DISTINCT player_id) FROM stat_events WHERE substr(created_at, 1, 10) >= ?', [$from]);
$eventCount = stat_value($pdo, 'SELECT COUNT(*) FROM stat_events WHERE substr(created_at, 1, 10) >= ?', [$from]);
$completed = stat_value($pdo, "SELECT COUNT(DISTINCT player_id) FROM stat_events WHERE event = 'game_completed' AND substr(created_at, 1, 10) >= ?", [$from]);
$returning = stat_value(
    $pdo,
    'SELECT COUNT(*) FROM (SELECT player_id FROM stat_events WHERE substr(created_at, 1, 10) >= ? GROUP BY player_id HAVING COUNT(DISTINCT substr(created_at, 1, 10)) > 1)',
    [$from]
);

$columns = '';
foreach (array_keys(EVENT_LABELS) as $event) {
    $columns .= ", SUM(event = '" . $event . "') AS " . $event;
}
$stmt = $pdo->prepare('SELECT substr(created_at, 1, 10) AS day, COUNT(DISTINCT player_id) AS players' . $columns . '
    FROM stat_events WHERE substr(created_at, 1, 10) >= ? GROUP BY day ORDER BY day DESC');
$stmt->execute([$from]);
$daily = $stmt->fetchAll();
$maxPlayers = max(1, ...array_map(static fn(array $r): int => (int) $r['players'], $daily ?: [['players' => 1]]));

$stmt = $pdo->prepare("SELECT s.number, s.title,
        COUNT(DISTINCT CASE WHEN e.event = 'opened' THEN e.player_id END) AS opened,
        COUNT(DISTINCT CASE WHEN e.event = 'location_verified' THEN e.player_id END) AS verified,
        COUNT(DISTINCT CASE WHEN e.event = 'solved' THEN e.player_id END) AS solved,
        COUNT(DISTINCT CASE WHEN e.event = 'solution_shown' THEN e.player_id END) AS shown,
        COUNT(DISTINCT CASE WHEN e.event = 'hint_used' THEN e.player_id END) AS hints,
        COUNT(CASE WHEN e.event = 'wrong_answer' THEN 1 END) AS wrong
    FROM stations s LEFT JOIN stat_events e ON e.station_id = s.id AND substr(e.created_at, 1, 10) >= ?
    GROUP BY s.id ORDER BY s.number");
$stmt->execute([$from]);
$byStation = $stmt->fetchAll();

$dayEvents = [];
if ($day !== null) {
    $stmt = $pdo->prepare('SELECT e.created_at, e.player_id, e.event, e.attempts, s.number, s.title
        FROM stat_events e LEFT JOIN stations s ON s.id = e.station_id
        WHERE substr(e.created_at, 1, 10) = ? ORDER BY e.id');
    $stmt->execute([$day]);
    $dayEvents = $stmt->fetchAll();
}

function stat_date(string $day): string
{
    $time = strtotime($day);
    return $time ? date('d.m.Y', $time) : $day;
}

admin_header('Statistik', $admin);
?>
<h1 class="admin-title">Statistik</h1>
<p class="task-intro">Nur Spielende, die der anonymen Statistik zugestimmt haben. Gespeichert werden eine zufällige Browser-Kennung, Station, Ereignis und Zeitstempel – keine Namen, Standorte oder IP-Adressen. Wer das Fortschritts-Zurücksetzen nutzt, erhält eine neue Kennung.</p>

<nav class="admin-toolbar" aria-label="Zeitraum">
  <?php foreach ($ranges as $value => $label): ?>
    <a class="btn small<?= $value === $days ? '' : ' secondary' ?>" href="statistics.php?days=<?= $value ?>"<?= $value === $days ? ' aria-current="true"' : '' ?>><?= h($label) ?></a>
  <?php endforeach; ?>
</nav>

<div class="stat-kpis">
  <div class="stat-kpi"><b><?= $players ?></b><span>Spielende</span></div>
  <div class="stat-kpi"><b><?= $returning ?></b><span>an mehreren Tagen aktiv</span></div>
  <div class="stat-kpi"><b><?= $completed ?></b><span>Spiel abgeschlossen</span></div>
  <div class="stat-kpi"><b><?= $eventCount ?></b><span>Ereignisse</span></div>
</div>

<?php if ($day !== null): ?>
  <h2>Ereignisse am <?= h(stat_date($day)) ?></h2>
  <p><a href="statistics.php?days=<?= $days ?>">← Zurück zur Tagesübersicht</a></p>
  <?php if (!$dayEvents): ?><p>Keine Ereignisse an diesem Tag.</p><?php else: ?>
  <div class="stat-table-wrap"><table class="stat-table">
    <thead><tr><th>Uhrzeit</th><th>Spielende</th><th>Ereignis</th><th>Station</th><th>Versuche</th></tr></thead>
    <tbody>
    <?php foreach ($dayEvents as $e): ?>
      <tr>
        <td><?= h(substr((string) $e['created_at'], 11, 8)) ?></td>
        <td><code><?= h(substr((string) $e['player_id'], 0, 8)) ?></code></td>
        <td><span class="stat-event stat-event--<?= h($e['event']) ?>"><?= h(EVENT_LABELS[$e['event']] ?? $e['event']) ?></span></td>
        <td><?= $e['title'] !== null ? h('#' . $e['number'] . ' ' . $e['title']) : '–' ?></td>
        <td><?= $e['attempts'] !== null ? (int) $e['attempts'] : '–' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
<?php else: ?>
  <h2>Tagesübersicht</h2>
  <?php if (!$daily): ?><p>Im gewählten Zeitraum gibt es noch keine Daten.</p><?php else: ?>
  <div class="stat-table-wrap"><table class="stat-table">
    <thead><tr>
      <th>Tag</th><th>Spielende</th>
      <?php foreach (EVENT_LABELS as $label): ?><th class="num"><?= h($label) ?></th><?php endforeach; ?>
    </tr></thead>
    <tbody>
    <?php foreach ($daily as $r): ?>
      <tr>
        <td><a href="statistics.php?days=<?= $days ?>&amp;day=<?= h($r['day']) ?>"><?= h(stat_date($r['day'])) ?></a></td>
        <td class="stat-bar-cell"><span class="stat-bar" style="width:<?= (int) round((int) $r['players'] / $maxPlayers * 100) ?>%"></span><b><?= (int) $r['players'] ?></b></td>
        <?php foreach (array_keys(EVENT_LABELS) as $event): ?><td class="num"><?= (int) $r[$event] ?></td><?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <p class="muted">Auf ein Datum klicken, um alle Ereignisse des Tages zu sehen. „Spielende“ = verschiedene Kennungen an diesem Tag.</p>
  <?php endif; ?>

  <h2>Pro Station</h2>
  <p class="muted">Anzahl verschiedener Spielender je Station (Falsche Antworten: Anzahl der Versuche).</p>
  <div class="stat-table-wrap"><table class="stat-table">
    <thead><tr><th>Station</th><th class="num">Geöffnet</th><th class="num">Standort erreicht</th><th class="num">Richtig</th><th class="num">Lösung angezeigt</th><th class="num">Hinweis</th><th class="num">Falsche Antworten</th></tr></thead>
    <tbody>
    <?php foreach ($byStation as $r): ?>
      <tr>
        <td><?= h('#' . $r['number'] . ' ' . $r['title']) ?></td>
        <td class="num"><?= (int) $r['opened'] ?></td><td class="num"><?= (int) $r['verified'] ?></td><td class="num"><?= (int) $r['solved'] ?></td>
        <td class="num"><?= (int) $r['shown'] ?></td><td class="num"><?= (int) $r['hints'] ?></td><td class="num"><?= (int) $r['wrong'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
<?php endif; ?>
<?php admin_footer();
