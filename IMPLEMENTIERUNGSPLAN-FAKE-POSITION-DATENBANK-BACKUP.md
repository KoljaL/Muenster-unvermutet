# Implementierungsplan: Fake Position und Datenbank-Backup

## Ziel

Zwei Funktionen ergänzen:

1. Die Schaltfläche **„Fake Position (Dev)“** über die Admin-Einstellung
   **Aus / An / Test** steuerbar machen.
2. Eine Datenbankkopie über eine geschützte URL erstellen, die ein
   All-Inkl-Cronjob aufrufen kann. Die SQLite-Datei wird unter
   `data/backups/` abgelegt und erhält Datum und Uhrzeit im Dateinamen.

## 1. Fake-Position-Schaltfläche

### Verhalten

- Die Funktion wird in der vorhandenen UI-Einstellungsverwaltung als
  `ui.fake_position` registriert und der Gruppe **Aufgaben** zugeordnet.
- Der Standardwert ist **Aus**. Bestehende Datenbanken und Snapshots, in denen
  dieser Schlüssel fehlt, lassen die Funktion dadurch ausgeschaltet.
- **An** zeigt die Schaltfläche für alle Besucher, **Aus** für niemanden und
  **Test** nur mit dem vorhandenen Vorschau-Mechanismus:
  `?preview=fake_position` oder `?preview=all`.
- Die Schaltfläche wird im Stationsdialog nur gerendert, wenn die geladene
  UI-Einstellung aktiv ist. Die tatsächliche Fake-Position-Funktion bleibt
  ansonsten unverändert.
- Der Admin-Eintrag erhält eine verständliche Beschreibung und erscheint in
  der bestehenden Einstellungsseite mit den Auswahlmöglichkeiten Aus, An und
  Test. Die Admin-Hilfe und die README werden entsprechend aktualisiert.

### Sicherheitshinweis

Der `preview`-Parameter ist öffentlich und keine Entwickler-Authentifizierung.
Im Zustand **Test** kann daher jeder, der `?preview=fake_position` kennt, die
Schaltfläche sehen und verwenden. Der Schalter steuert die Sichtbarkeit im
Frontend, ist aber keine Sicherheitsgrenze für die Standortprüfung: Die API
prüft übermittelte Koordinaten, kann jedoch nicht feststellen, ob diese vom
echten GPS stammen. Deshalb im Livebetrieb **Aus** verwenden und die
Standortprüfung nicht als Schutz vor absichtlich gefälschten Browserdaten
betrachten.

## 2. Cronjob-Backup über URL

### Aufruf und Geheimnis

- Einen dedizierten GET-Endpunkt vorsehen, beispielsweise
  `api/backup.php?token=<langes-zufälliges-geheimnis>`. Der genaue Pfad wird bei
  der Implementierung mit den übrigen API-Konventionen abgestimmt.
- Das Geheimnis wird ausschließlich auf dem Webspace in
  `includes/config.local.php` konfiguriert, niemals im Repository, in der
  Datenbank oder in einer öffentlich erreichbaren Datei.
- Den übergebenen Token mit `hash_equals()` gegen den konfigurierten Wert
  prüfen. Fehlender oder falscher Token führt zu HTTP 403; bei nicht
  konfiguriertem Token bleibt der Endpunkt geschlossen. Nur HTTPS-Aufrufe
  zulassen.
- Die Antwort enthält nur einen knappen Erfolgsstatus und keine Datenbankdaten,
  absoluten Dateipfade oder Token. Antworten mit `Cache-Control: no-store`
  versehen; Fehler intern protokollieren, ohne das Geheimnis zu loggen.
- Ein Token in einer URL kann in Zugriffsprotokollen auftauchen. Deshalb ein
  langes, zufälliges Geheimnis verwenden, die Cronjob-URL vertraulich
  behandeln, Logs entsprechend schützen und das Geheimnis bei Bedarf rotieren.

### Erstellen und schützen der Sicherung

- Verzeichnis `data/backups/` bei Bedarf anlegen und so berechtigen, dass der
  PHP-Prozess schreiben kann, aber sonst niemand mehr Zugriff als nötig hat.
- Direkten HTTP-Zugriff und Verzeichnisauflistung für `data/backups/` sperren
  (auf All-Inkl/Apache zum Beispiel über eine passende `.htaccess`). Die
  erzeugte Datei darf ausschließlich durch einen authentifizierten Admin-Export
  oder direkten Serverzugriff erreichbar sein.
- Die Sicherung mit der SQLite-Backup-API erstellen, analog zum bestehenden
  Export in `admin/database-transfer.php`; keine laufende Datenbankdatei
  unkoordiniert kopieren.
- Einen Zeitstempel im Namen verwenden, zum Beispiel
  `game-20261031-171304.sqlite`. Eine Sicherung zunächst temporär im
  Zielverzeichnis erzeugen, die Integrität prüfen und erst danach atomar auf
  den endgültigen Namen verschieben. Namenskollisionen dürfen keine ältere
  Sicherung überschreiben.
- Fehlerzustände müssen einen Fehlerstatus liefern und dürfen nicht als
  erfolgreicher Backup-Lauf erscheinen. Der Endpunkt löscht keine alten
  Sicherungen; Aufbewahrungsdauer und automatisches Löschen werden erst nach
  einer gesonderten Festlegung ergänzt.

### Einrichtung bei All-Inkl

1. Ein langes zufälliges Token generieren und ausschließlich in
   `includes/config.local.php` auf dem Webspace eintragen.
2. `data/backups/` und die Zugriffssperre bereitstellen und Schreibrechte des
   PHP-Prozesses prüfen.
3. Die HTTPS-URL mit Token als Cronjob hinterlegen und zunächst manuell testen.
4. Prüfen, dass eine neue, valide SQLite-Datei entsteht, der Endpunkt keine
   Dateiinhalte ausliefert und ein direkter HTTP-Abruf der Datei blockiert wird.

## 3. Datenbankänderungen und Deployment

Für beide oben beschriebenen Funktionen ist nach derzeitigem Entwurf **keine
Änderung des Datenbankschemas erforderlich**:

- `ui.fake_position` verwendet die bereits vorhandene Tabelle `settings` und
  dieselbe Aus-/An-/Test-Logik wie die anderen UI-Optionen. Ein fehlender
  Schlüssel wird über den Default **Aus** behandelt.
- Der Backup-Endpunkt erstellt nur eine Datei und verändert keine Datenbank.
- Daher sind für diese Umsetzung keine produktiven Schemaänderungen und keine
  Datenbank-Migrationsdatei notwendig.

Falls die Implementierung wider Erwarten doch eine Datenbankänderung erfordert,
gilt folgender verpflichtender Ablauf:

1. Die neue Tabellen-/Indexstruktur und nötige Standardwerte idempotent in
   `setup/schema.sql` ergänzen.
2. `setup/snapshot.sql` so aktualisieren, dass ein lokaler Reset dieselbe
   Struktur und die dafür nötigen Startwerte enthält. Ein Snapshot darf nicht
   als Update der produktiven Datenbank verwendet werden.
3. Eine separate PHP-Migrationsdatei für den Webspace bereitstellen. Sie muss
   idempotent und additiv sein, vorhandene Datensätze erhalten und vor jeder
   Änderung eine Sicherung voraussetzen. Zulässig sind beispielsweise
   fehlende Spalten, Tabellen oder Indizes anzulegen sowie fehlende
   Standardwerte einzufügen; bestehende Tabellen oder Daten dürfen nicht
   gelöscht oder überschrieben werden.
4. Die Migration vor dem Produktiv-Deployment mit einer Kopie der bestehenden
   Datenbank testen. Nach Ausführung Schema und Datenbestand prüfen und die
   Migrationsdatei anschließend vom Webspace entfernen oder anderweitig
   deaktivieren.
5. In der Deployment-Dokumentation exakt angeben, welche Datei in welcher
   Reihenfolge lokal und auf dem Webspace auszuführen ist.

## 4. Betroffene Bereiche bei der Implementierung

- `includes/settings.php`: UI-Option registrieren, Default **Aus**.
- `admin/settings.php` und `admin/help.php`: Einstellung und Erklärung
  anzeigen.
- `app.js`: Fake-Position-Button nur bei aktivierter UI-Option rendern.
- `api/settings.php`: Bestehende Auflösung der UI-Einstellungen wiederverwenden.
- `api/backup.php` (vorgeschlagen): Token prüfen und Backup erstellen.
- `includes/config.php`: Nur falls erforderlich den optionalen Konfigurationswert
  für das Backup-Geheimnis mit sicherem Default ergänzen.
- `data/backups/.htaccess` (oder gleichwertige Webserver-Regel): direkten
  Dateizugriff unterbinden.
- `tests/` und `README.md`: Verhalten, Sicherheit und Einrichtung dokumentieren.
- `setup/schema.sql`, `setup/snapshot.sql` und eine PHP-Migration nur dann
  ändern oder ergänzen, wenn bei der Umsetzung tatsächlich eine
  Schemaänderung erforderlich wird.

## 5. Abnahmekriterien

### Fake Position

- Admin kann zwischen **Aus**, **An** und **Test** wählen und speichern.
- **Aus** blendet den Button aus, auch bei `?preview=all`.
- **An** zeigt den Button ohne Preview-Parameter.
- **Test** blendet ihn ohne passenden Preview-Parameter aus und zeigt ihn mit
  `?preview=fake_position` sowie `?preview=all`.
- Andere UI-Optionen, Standortprüfung und Vorschauparameter funktionieren
  unverändert.

### Backup

- Fehlender/falscher Token und fehlende Konfiguration erzeugen kein Backup.
- Ein gültiger HTTPS-Cronjob-Aufruf legt eine zeitgestempelte, valide SQLite-
  Sicherung in `data/backups/` an, ohne die Live-Datenbank zu verändern.
- Ein zweiter Aufruf überschreibt keine vorherige Sicherung.
- Direkter HTTP-Zugriff auf Backup-Dateien ist gesperrt; die HTTP-Antwort des
  Endpunkts enthält keine Datenbankinhalte oder Geheimnisse.
- Fehler beim Erstellen oder Prüfen der Sicherung werden als Fehler gemeldet
  und nachvollziehbar protokolliert.
