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

<details class="how-to">
  <summary>Einstellungen: Funktionen ein- und ausschalten</summary>
  <div>
    <ul>
      <li>Alle Schalter findest du im Menü „Einstellungen“. Nach dem Klick auf „Einstellungen speichern“ (unten auf der Seite) gelten sie sofort für alle Besucher.</li>
      <li><strong>Aus:</strong> Die Funktion ist für niemanden sichtbar. <strong>An:</strong> Alle Besucher sehen sie.</li>
      <li><strong>Test:</strong> Nur du siehst die Funktion, um sie auszuprobieren. Öffne dazu die Webseite und hänge <code>?preview=all</code> an die Adresse an, z. B. <code>https://deine-seite.de/?preview=all</code>. Normale Besucher sehen weiterhin nichts.</li>
      <li><strong>Karte:</strong>
        <ul>
          <li><em>Station vorschlagen</em> – der Knopf „+ Station vorschlagen“ unter der Karte. Die Einsendungen landen bei dir im Menü „Vorschläge“.</li>
          <li><em>Kategorien-Legende</em> – die farbige Liste unter der Karte. Besucher können damit Stationen einer Kategorie ausblenden.</li>
          <li><em>Stationsliste</em> – die Liste „Alle Stationen“ unter der Karte. Sie hilft Menschen, die die Karte nicht gut bedienen können.</li>
          <li><em>OSM-Orientierungskarte</em> – die kleine Karte innerhalb einer Aufgabe, die Ziel und eigenen Standort zeigt.</li>
        </ul>
      </li>
      <li><strong>Aufgaben:</strong>
        <ul>
          <li><em>Hinweis-Schaltfläche</em> – der Knopf „Hinweis anzeigen“ in der Aufgabe. Der Text kommt aus dem Feld „Hinweis“ der Station.</li>
          <li><em>Lösungs-Schaltfläche</em> – der Knopf „Lösung anzeigen“. Wer nicht weiterkommt, sieht damit die Lösung.</li>
          <li><em>Standortabhängige Antwortfreigabe</em> – siehe unten.</li>
          <li><em>Abschluss-Screen</em> – die Seite „Geschafft – Münster entdeckt!“ nach der letzten gelösten Station.</li>
        </ul>
      </li>
    </ul>
  </div>
</details>

<details class="how-to">
  <summary>Standortprüfung (Besucher müssen vor Ort sein)</summary>
  <div>
    <ul>
      <li>Wenn „Standortabhängige Antwortfreigabe“ unter „Einstellungen“ → „Aufgaben“ auf <strong>An</strong> steht, sehen Besucher die Antwortmöglichkeiten und die Lösung erst, wenn sie am Ort der Station sind. Der Browser fragt dazu das Handy nach dem Standort; Besucher müssen das erlauben.</li>
      <li>Ob jemand „vor Ort“ ist, wird mit dem Standort verglichen, den du im Stationsformular (Menü „Stationen“) auf der Karte gesetzt hast. Prüfe deshalb, dass dieser Punkt genau stimmt.</li>
      <li><strong>Prüfungsradius:</strong> Wie viele Meter Besucher höchstens vom Ziel entfernt sein dürfen. Größer = großzügiger (empfohlen: 50 m; in engen Gassen oder bei schlechtem Empfang etwas mehr).</li>
      <li><strong>Maximale GPS-Ungenauigkeit:</strong> Handys wissen ihren Standort nur ungefähr. Ist die Ortung ungenauer als dieser Wert, wird sie abgelehnt und Besucher können über „Standort aktualisieren“ erneut prüfen lassen. Ein zu kleiner Wert führt zu vielen Fehlversuchen.</li>
      <li>Wenn Besucher sich beschweren, dass sie vor Ort nicht weiterkommen: Radius erhöhen oder die Station als „Test“ ausprobieren.</li>
      <li>Ist diese Funktion auf „Aus“, werden die beiden Werte nicht verwendet.</li>
    </ul>
  </div>
</details>
<?php admin_footer();
