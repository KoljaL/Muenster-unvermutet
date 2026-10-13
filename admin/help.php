<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
$admin = require_admin();

admin_header('Hilfe', $admin);
?>
<h1 class="admin-title">Hilfe &amp; Dokumentation</h1>

<details class="how-to">
  <summary>Stationen verwalten</summary>
  <div>
    <ul>
      <li><strong>Neue Station:</strong> Unter „Stationen“ auf „+ Neue Station“ klicken und alle Pflichtfelder ausfüllen (Titel, Hinweis, Standort, Antworten, Lösung, Aufgabenfoto).</li>
      <li><strong>Nummer und ID</strong> werden automatisch vergeben, sind unveränderlich und werden daher nicht angezeigt.</li>
      <li><strong>Standort:</strong> Ein Klick auf die Karte setzt die Koordinaten.</li>
      <li><strong>Archivieren</strong> blendet eine Station im Spiel aus; sie kann jederzeit wiederhergestellt werden.</li>
    </ul>
  </div>
</details>

<details class="how-to">
  <summary>Kategorien verwalten</summary>
  <div>
    <ul>
      <li>Unter „Kategorien“ sind alle Kategorien mit Bild, Farbe, Beschreibung und Anzahl der Stationen aufgelistet.</li>
      <li><strong>Neue Kategorie:</strong> „+ Neue Kategorie“ wählen, Name, Beschreibung und Farbe festlegen und optional ein Bild hochladen.</li>
      <li><strong>Bild:</strong> JPEG oder PNG, möglichst quadratisch. Beim Bearbeiten ersetzt ein neues Bild das bisherige; „Symbol entfernen“ löscht es.</li>
      <li><strong>Bearbeiten:</strong> Name, Beschreibung, Farbe und Bild sind änderbar. Die interne Kennung bleibt gleich, bestehende Stationen behalten ihre Kategorie.</li>
      <li><strong>Löschen</strong> ist nur für Kategorien ohne Stationen möglich. Ordne Stationen vorher einer anderen Kategorie zu.</li>
      <li>Die Kategorien stehen öffentlich unter <code>api/categories.php</code> zur Verfügung.</li>
    </ul>
  </div>
</details>

<details class="how-to">
  <summary>Vorschläge</summary>
  <div>
    <ul>
      <li>Neue Einsendungen erscheinen zunächst als <strong>offen</strong> unter „Vorschläge“ und werden nicht automatisch veröffentlicht.</li>
      <li>Ein Klick auf den Titel oder auf „Prüfen &amp; freigeben“ öffnet das Stationsformular mit den vorgeschlagenen Texten, Koordinaten, Antworten und Bildern.</li>
      <li>Prüfe insbesondere Standort, Aufgaben- und Lösungsfoto, richtige Antwort, Kategorie und Pflichtfelder. Ändere die Inhalte direkt im Formular und speichere anschließend.</li>
      <li>Beim Speichern wird der Vorschlag als freigegeben markiert und als neue Station veröffentlicht. Die Stations-ID und Nummer werden automatisch vergeben.</li>
      <li>Mit „Ablehnen“ wird der Vorschlag verworfen und nicht als Station angelegt. Diese Aktion sollte nur nach einer kurzen Prüfung erfolgen.</li>
    </ul>
  </div>
</details>
<?php admin_footer();
