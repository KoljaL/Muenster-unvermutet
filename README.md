# Münster unvermutet

## Spielprinzip

„Münster unvermutet“ ist eine digitale Foto-Schnitzeljagd durch Münster. Auf einer interaktiven Karte sind 14 Stationen in der Innenstadt markiert. Jede Station zeigt ein Foto eines ungewöhnlichen Details an einem Gebäude, Denkmal, Relief oder einer Inschrift.

Die Aufgabe besteht darin, den abgebildeten Ort in Münster zu finden und anschließend eine Multiple-Choice-Frage zur Geschichte oder Bedeutung des Details zu beantworten. Bei einer richtigen Antwort wird die Station gelöst. Danach erscheinen ein Lösungsfoto, der Name des Ortes, Hintergrundinformationen und – sofern vorhanden – weiterführende Links.

Die Stationen können in beliebiger Reihenfolge gespielt werden. Der Fortschritt wird lokal im Browser gespeichert. Gelöste Stationen werden auf der Karte mit einem Häkchen markiert. Sobald alle 14 Stationen gelöst sind, erscheint eine Abschlussansicht mit einer Statistik zu selbst gelösten Aufgaben, verwendeten Hinweisen und Fehlversuchen.

## Kurzanleitung

1. Öffne [`index.html`](./index.html) in einem modernen Webbrowser.
2. Wähle eine Station, indem du einen nummerierten Pin auf der Karte oder eine Station in der Liste anklickst.
3. Sieh dir das Aufgabenfoto an und suche das abgebildete Detail vor Ort.
4. Nutze bei Bedarf den Button **Hinweis anzeigen**. Die Koordinaten können außerdem direkt in Google Maps geöffnet werden.
5. Wenn die standortabhängige Freigabe aktiv ist, aktualisiere am Ziel deinen Standort. Erst nach erfolgreicher Prüfung werden die Antwortmöglichkeiten freigegeben.
6. Lies die Antwortmöglichkeiten und wähle die passende Antwort aus.
7. Bei einer falschen Antwort kannst du es erneut versuchen. Mit **Lösung anzeigen** lässt sich die Station auch direkt auflösen, sofern die Standortfreigabe nicht aktiv ist oder bereits erteilt wurde.
8. Nach dem Lösen kannst du die Ortsinformationen und das Lösungsfoto ansehen. Über **Nächste Station** gelangst du zur nächsten noch offenen Aufgabe.
9. Kehre über **Zurück zum Stadtplan** zur Übersicht zurück und spiele weitere Stationen.

## Stationslinks

Eine Station lässt sich direkt über einen Link mit ihrer stabilen ID öffnen:
`?station=STATIONS-ID`. Die ID bleibt auch dann verwendbar, wenn sich die
Stationsnummer oder der Titel ändert. Beim Öffnen und Schließen einer Station
wird die URL aktualisiert; Browser-Zurück und -Vorwärts wechseln zwischen den
zuvor besuchten Ansichten. Ist die ID ungültig oder die Station nicht mehr
verfügbar, bleibt die Übersicht geöffnet und es erscheint ein Hinweis.

Ein Stationslink öffnet nur die Ansicht. Er startet das Spiel nicht, verändert
keinen gespeicherten Fortschritt und erteilt oder erneuert keine
Standortfreigabe. Bei aktiver Standortprüfung bleiben geschützte Antworten
gesperrt, bis die Freigabe im Spiel regulär erfolgt.

## Fortschritt

Der Fortschrittsbalken zeigt an, wie viele Stationen bereits gelöst sind. Der Spielstand wird mit `localStorage` im verwendeten Browser gespeichert und bleibt beim erneuten Öffnen der Seite erhalten.

Mit **Zurücksetzen** kann der gesamte Fortschritt gelöscht und das Spiel neu begonnen werden. Beim vollständigen Durchspielen werden zusätzlich Rang und Spielstatistik angezeigt.

## Bedienung und Barrierefreiheit

Die Oberfläche kann vollständig mit der Tastatur bedient werden:

- Mit **Tab** werden Skip-Link, Theme-Schalter, Kategorie-Filter, Stationsvorschlag,
  Stationsliste, Karten-Pins und die jeweilige Detailansicht erreicht.
- Karten-Pins reagieren auf **Enter** und **Leertaste**. Die Stationsliste ist eine
  gleichwertige Alternative zur Karte.
- Der sichtbare Fokus-Ring bleibt auch bei aktivem Dark Mode erhalten.
- Der Dialog **Station vorschlagen** setzt den Fokus beim Öffnen in das Formular,
  unterstützt **Escape** und führt den Fokus beim Schließen zum auslösenden Button zurück.
- Pflichtfelder und Koordinatenfehler werden direkt am jeweiligen Feld angezeigt und
  per `aria-describedby` mit dem Feld verknüpft. Dynamische Statusmeldungen werden
  über eine `aria-live`-Region angekündigt.

### Farbmodus

Beim ersten Aufruf folgt die Anwendung `prefers-color-scheme`. Der Schalter
**Dunkler Modus** speichert eine manuelle Auswahl unter
`muenster-unvermutet:color-mode` in `localStorage`. Mit **Systemeinstellung verwenden**
wird die manuelle Auswahl entfernt; danach werden Änderungen der Systemeinstellung
wieder übernommen. Ist `localStorage` nicht verfügbar, funktioniert der Farbmodus
nur für die aktuelle Sitzung.

### Filter und Animations-Hooks

Die Kategorien unter der Karte sind Filter-Schalter. Ein leerer Filter zeigt alle
Stationen. Der UI-Zustand liegt in `uiState`; Rendering und Interaktionen sind getrennt.
Für zukünftige Animationen oder Erweiterungen stellt `index.html` folgende
`CustomEvent`s auf `document` bereit:

`ms:beforeRender`, `ms:afterRender`, `ms:beforeDetailsOpen`,
`ms:afterDetailsOpen`, `ms:beforeDetailsClose`, `ms:afterDetailsClose`,
`ms:beforeFilterChange`, `ms:afterFilterChange`, `ms:beforeDialogOpen`,
`ms:afterDialogOpen`, `ms:beforeDialogClose`, `ms:afterDialogClose`.

Beispiel:

```js
document.addEventListener('ms:afterDetailsOpen', (event) => {
  console.log('Station geöffnet:', event.detail.stationId);
});
```

Animationen dürfen keine Information voraussetzen und müssen bei
`prefers-reduced-motion: reduce` entfallen oder minimiert werden.

## Station vorschlagen

Der Button **Station vorschlagen** öffnet einen zugänglichen Dialog. Das Formular
verlangt:

- `name`: Stationsname, getrimmt, maximal 100 Zeichen
- `description`: Beschreibung oder Besonderheiten, getrimmt, maximal 1000 Zeichen
- `categoryId`: stabile ID der ausgewählten Kategorie
- optional `editorial`: Hinweis, Frage, zwei bis vier Antwortoptionen, richtige
  Antwort und Hintergrundinformationen

Der Standort ist optional. Werden Koordinaten eingetragen, müssen beide Werte
vorhanden und gültig sein:

- `latitude`: Zahl zwischen `-90` und `90`
- `longitude`: Zahl zwischen `-180` und `180`

Dezimal-Kommas werden akzeptiert und in Dezimalpunkte normalisiert. Alternativ
kann der Standort per Kartenklick übernommen werden. Unvollständige oder ungültige
Eingaben werden nicht als Erfolg behandelt.

### Frontend-Datenformat

Nach erfolgreicher Validierung erzeugt das Frontend dieses stabile Objekt:

```json
{
  "version": 1,
  "name": "Beispielstation",
  "description": "Beschreibung und Besonderheiten",
  "categoryId": "history",
  "location": {
    "latitude": 51.9607,
    "longitude": 7.6261
  },
  "editorial": {
    "hint": "Achte auf das Detail über dem Eingang.",
    "question": "Was ist hier zu sehen?",
    "options": ["Ein Wappen", "Eine Uhr"],
    "correctOptionIndex": 0,
    "info": "Zusätzlicher Hintergrund"
  }
}
```

Das Feld `location` wird nur bei vollständig ausgefüllten und gültigen
Koordinaten aufgenommen. Ohne Standort enthält es kein `location`-Feld. Der
`editorial`-Block ist ebenfalls optional; sobald Frage oder Antworten erfasst
werden, muss er vollständig und mit zwei bis vier Antworten ausgefüllt sein.
Die Übertragung erfolgt als JSON an `api/submit_station.php`; zusätzlich wird
ein `ms:stationSuggested`-Event ausgelöst:

```js
document.addEventListener('ms:stationSuggested', (event) => {
  const suggestion = event.detail;
  // Erweiterungen können den validierten Payload beobachten.
});
```

### Backend-Anbindung

Der Endpoint akzeptiert den neuen JSON-Vertrag und bleibt für das bisherige
vollständige Formularformat rückwärtskompatibel. Basisdaten werden direkt als
`pending`-Vorschlag gespeichert; fehlende redaktionelle Angaben und ein fehlender
Standort sind erlaubt. Die Redaktion ergänzt und validiert die vollständigen
Stationsdaten im Admin-Bereich, bevor ein Vorschlag veröffentlicht wird.

## Anonyme Statistik

Beim ersten Start fragt das Spiel nach der Einwilligung zur anonymen Statistik.
Die Auswahl steht unter `muenster-unvermutet:stats-consent` (`granted` oder `denied`)
im `localStorage` und lässt sich in den Einstellungen („Anonyme Statistik“) ändern.

- **Ohne Zustimmung** wird nichts gesendet und keine Kennung angelegt; gespeichert
  wird nur lokal, dass nicht zugestimmt wurde.
- **Mit Zustimmung** erzeugt der Browser eine zufällige UUID
  (`muenster-unvermutet:player-id`) und sendet an `api/track.php` die Ereignisse
  `opened`, `location_verified`, `hint_used`, `wrong_answer`, `solution_shown`,
  `solved` und `game_completed`, bei `wrong_answer` und `solved` mit der Zahl der
  Versuche. Der Server ergänzt den Zeitstempel und speichert alles in `stat_events`.
  Koordinaten, IP-Adresse, User-Agent und Name werden nicht gespeichert.
- `location_verified`, `solution_shown`, `solved` und `game_completed` werden pro
  Kennung und Station nur einmal erfasst.
- Ein Widerruf stoppt das Senden und entfernt die Kennung im Browser; bereits
  gespeicherte Ereignisse bleiben erhalten, Rohdaten werden nicht automatisch gelöscht.
  „Fortschritt zurücksetzen“ vergibt eine neue Kennung.
- Mit `?preview=…` wird nichts gesendet, damit Tests der Redaktion die Zahlen nicht verfälschen.
- Das Rate-Limit von `api/track.php` arbeitet pro Kennung statt pro IP-Adresse.

Die Auswertung steht im Admin-Bereich unter **Statistik**: Kennzahlen, Tagesübersicht
mit allen Ereignistypen, Detailliste je Tag und Zahlen pro Station.

## Browserunterstützung und bekannte Einschränkungen

- Benötigt werden JavaScript, `fetch`, `localStorage`, `window.matchMedia`,
  `CustomEvent` und das native HTML-Element `<dialog>`.
- Für die Kartenansicht werden Leaflet und OpenStreetMap-Kacheln aus dem Internet
  geladen. Ohne Netzwerkverbindung bleibt die Stationslogik nutzbar, die Karte
  kann jedoch nicht dargestellt werden.
- Die Standortprüfung fragt den Standort nur nach Klick auf **Standort aktualisieren**
  ab und benötigt einen sicheren Kontext (HTTPS oder localhost).
- Die Standortübernahme per Karte ist eine Maus-/Touch-Interaktion. Per Tastatur
  stehen die Koordinatenfelder als gleichwertiger Eingabeweg zur Verfügung.
- `prefers-reduced-motion` deaktiviert bzw. reduziert Übergänge und Animationen.
- Bei nicht erreichbarem Backend zeigt das Formular den Fehler an und behandelt
  den Vorschlag nicht als erfolgreich übermittelt.

## Geplante Verbesserungen (zukünftige Updates)

### Persistente serverseitige Standortfreigabe

**Problem:** Die Freigabe einer Station wird an zwei Stellen gespeichert: als
`locationVerified` im lokalen Spielstand (`localStorage`) und als Eintrag in der
PHP-Sitzung auf dem Server (`$_SESSION['verified_stations']`). Geschützte Details
(Antworten, Lösung, Lösungsfoto, Info-Text) liefert `api/get_details.php?protected=1`
nur, wenn die Sitzung die Freigabe kennt. Geht die Sitzung verloren (Cookie gelöscht,
Browserwechsel, Ablauf der Sitzung, Aufräumen durch den Server), verweigert der Server
den Abruf, obwohl der lokale Spielstand die Station als freigeschaltet oder gelöst
führt. Die Station erscheint dann wieder gesperrt und verlangt eine erneute
Standortprüfung. Das widerspricht der Anforderung, dass gelöste Stationen ohne erneute
Prüfung zugänglich bleiben.

**Lösungsvorschlag:**

1. Nach erfolgreicher Standortprüfung erzeugt der Server eine kryptografisch zufällige,
   nicht erratbare Kennung (z. B. `random_bytes(32)`) und setzt sie als langlebiges
   Cookie (`HttpOnly`, `Secure`, `SameSite=Lax`, begrenzte Laufzeit).
2. In der Datenbank wird nicht die Kennung selbst, sondern ihr Hash zusammen mit den
   freigeschalteten Stations-IDs und einem Ablaufzeitpunkt gespeichert. Positionsdaten
   werden weiterhin nicht gespeichert.
3. `api/get_details.php?protected=1` prüft Cookie-Hash, Station und Ablaufzeitpunkt in
   der Datenbank und liefert nur dann die geschützten Details aus. Der lokale
   `localStorage`-Wert allein reicht weiterhin nicht aus.
4. `api/reset_location.php` löscht die zugehörigen Freigaben in der Datenbank und das
   Cookie. Abgelaufene Datensätze werden regelmäßig entfernt.
5. Das Frontend behandelt einen abgelehnten Abruf geschützter Details als „Freigabe
   nicht mehr verfügbar“ und hält Freigabestatus und Fortschrittsanzeige konsistent.

**Datenschutz:** Die zufällige Kennung ist eine pseudonyme Zuordnung und kann als
personenbezogenes Datum gelten. Zu klären und zu dokumentieren sind Zweck,
Speicherdauer, Löschkonzept, Hinweis in der Datenschutzerklärung und die Einstufung des
Cookies als technisch erforderlich nach § 25 TDDDG. IP-Adressen dürfen nicht mit den
Freigaben verknüpft werden.

**Alternative:** Ein Spielkonto mit serverseitigem Spielstand würde auch geräteübergreifende
Freigaben ermöglichen, erfordert aber deutlich mehr Aufwand und zusätzliche
Datenschutzanforderungen.

## Installation als App (PWA)

Das Spiel lässt sich zum Startbildschirm hinzufügen und öffnet sich dann
ohne Browser-Adressleiste. **Es bleibt netzabhängig:** Es gibt keinen Service
Worker und keinen Offline-Cache; Stationsdaten, Karte und Standortprüfung
brauchen weiterhin eine Internetverbindung.

- **iPhone/iPad (iOS 15+):** In Safari auf „Teilen“ → „Zum Home-Bildschirm“.
  Die Home-Bildschirm-App kann einen eigenen Datenspeicher haben; ein vorher
  im Browser gespeicherter Spielstand wird dann nicht automatisch übernommen.
- **Android (Chrome, ab Android 12) und Desktop (Chrome/Edge):** Über das
  Browsermenü „App installieren“ bzw. die Schaltfläche im Installationshinweis.
- Beim ersten Besuch erscheint ein dezenter, schließbarer Hinweis
  (`muenster-unvermutet:install-hint` in `localStorage`). In der installierten
  App wird er nicht angezeigt.
- Dateien: `manifest.webmanifest`, `assets/icons/`, `.htaccess` (MIME-Typ des
  Manifests). `start_url` und `scope` sind relativ und passen zu
  `https://stina-ms.de/muenster-unvermutet/`.
- Die Symbole sind eine **vorläufige** Nachzeichnung von `MS_Rathhaus_Logo.jpg`
  (`python3 tools/make-icons.py`, benötigt Pillow). Finale PNGs erstellt und
  gibt die Designabteilung frei; sie ersetzen die Dateien in `assets/icons/`
  (192, 512, 512-maskable, apple-touch-icon 180, favicon 32).

## Voraussetzungen und Dateien

- Für die interaktive Karte und die Kartendaten wird eine Internetverbindung benötigt.
- Die Aufgaben- und Lösungsfotos müssen neben der HTML-Datei liegen und nach diesem Schema benannt sein:
  - `Aufgabe 1.jpg` bis `Aufgabe 14.jpg`
  - `Loesung 1.jpg` bis `Loesung 14.jpg`
- Die Anwendung nutzt Leaflet und OpenStreetMap für die Karte.

## Backend

Das Spiel hat ein schlankes PHP-Backend mit SQLite-Datenbank und einem geschützten Admin-Bereich. Die API-Variante des Frontends ist [`index.html`](./index.html): Sie lädt Kategorien (`api/categories.php`), Karten-Pins (`api/map_pins.php`) und globale UI-Einstellungen (`api/settings.php`) parallel und erst beim Öffnen einer Station die Stationsdaten (`api/get_details.php`, mit Ladezustand, Fehleranzeige und Retry). Ist die standortabhängige Antwortfreigabe eingeschaltet, liefert dieser Abruf zunächst keine Antworten oder Lösungsdaten. `api/verify_location.php` prüft die ausdrücklich abgefragte Browserposition serverseitig anhand der Stationskoordinaten; geschützte Details werden danach separat freigegeben. Standardradius und maximale GPS-Ungenauigkeit betragen jeweils 50 Meter und sind im Admin-Bereich konfigurierbar. Die optionale OSM-Orientierungskarte ist unabhängig davon schaltbar. Die erfolgreiche Freigabe wird lokal als Spielstand gespeichert; die genaue Position wird weder lokal noch serverseitig dauerhaft gespeichert. UI-Optionen im Testmodus lassen sich über `?preview=key1,key2` oder `?preview=all` anzeigen.

Die vollständige technische Planung steht in [`Backend-Umsetzung.md`](./Backend-Umsetzung.md). Das Backend verwendet:

- Vanilla PHP mit PDO und SQLite
- JSON-API für Kategorien, Pins und Stationsdetails; Kategorienamen, hochgeladene Bilder und Farben werden zentral in der Datenbank gepflegt
- Apache und HTTPS auf dem All-Inkl-Webspace
- PHP-Sessions für drei Administratoren
- einen fünfminütigen Admin-Session-Timeout
- einen relativen Upload-Ordner für Aufgaben- und Lösungsfotos
- Leaflet/OpenStreetMap als Kartenpicker im Admin-Bereich

### Testzugänge für die Entwicklung

Die folgenden Zugangsdaten sind ausschließlich für eine Testinstallation vorgesehen:

| Benutzername | Testpasswort            |
| ------------ | ----------------------- |
| `admin1`     | `Muenster-Test-2026-1!` |
| `admin2`     | `Muenster-Test-2026-2!` |
| `admin3`     | `Muenster-Test-2026-3!` |

**Wichtig:** Diese Testpasswörter dürfen nicht unverändert produktiv verwendet werden. Vor dem öffentlichen Deployment müssen neue Zugangsdaten gesetzt werden. In der SQLite-Datenbank werden nur Passwort-Hashes gespeichert.

### Dev-Button „Fake Position“ (vor dem Release entfernen)

Auf jeder Stationsseite gibt es den Button „🛠 Fake Position (Dev)“. Er verwendet die Stationskoordinaten als eigene Position und löst die Standortfreigabe aus, sodass man nicht zu jeder Station fahren muss. Der Server prüft dabei normal. Vor dem Livegang **muss er entfernt werden**, da sonst jeder die Standortprüfung umgehen kann.

Alle Stellen in `index.html` sind mit `DEV-FAKE-POSITION` markiert (nach dem Begriff suchen):

1. Block mit `devFakePositionButton` und `devFakePosition` vor `renderLocationStatus` komplett löschen.
2. In `renderLocationStatus` den Aufruf `+ devFakePositionButton(state)` entfernen.
3. In `verifyStationLocation` den Parameter `devFake`, die Bedingung `!devFake &&` und den Zweig `devFake ? devFakePosition(stationId) :` entfernen.
4. In `handleTaskListClick` den `case 'fake-position'` löschen.

Kontrolle: Die Suche nach `DEV-FAKE-POSITION`, `devFake` und `fake-position` darf in `index.html` keine Treffer mehr liefern.

### Einrichtung und lokaler Test

Voraussetzung: PHP 8+ mit PDO/SQLite und `fileinfo`.

```bash
./setup/reset_database.sh          # löscht die lokale DB, spielt setup/snapshot.sql ein und startet den PHP-Server
```

- Spiel: `http://127.0.0.1:8080/index.html`
- Admin: `http://127.0.0.1:8080/admin/login.php`
- Der Reset ist ein reiner SQL-Import: `setup/snapshot.sql` enthält Schema und alle Daten (Admins samt Passwort-Hashes, Stationen, Einstellungen, Vorschläge). Es werden keine Passwörter mehr abgefragt; die Zugänge entsprechen dem Snapshot. Voraussetzung: `sqlite3` und PHP 8+ (PDO/SQLite, `fileinfo`).
- Aktuellen Stand als neuen Reset-Stand speichern: `sqlite3 data/game.sqlite .dump > setup/snapshot.sql`
- Host und Port lassen sich über `PHP_SERVER_HOST` und `PHP_SERVER_PORT` ändern.
- Die Bilder in `uploads/` werden vom Reset nicht angefasst.
- Die Import- und Migrationsskripte (`create_admins.php`, `migrate_*.php`), Seed-JSONs und Beispielbilder liegen archiviert in `setup/OLD/`. Ein einzelnes Admin-Passwort lässt sich weiterhin mit `php setup/OLD/create_admins.php <benutzer> <passwort>` setzen (mind. 12 Zeichen).
- Ohne Reset wird die Datenbank beim ersten Zugriff automatisch aus `setup/schema.sql` angelegt (`data/game.sqlite`); additive Schemaänderungen wie `teaser_text` werden bei bestehenden Datenbanken idempotent nachgezogen.
- Abweichende Einstellungen (z. B. DB-Pfad außerhalb des Webroots) in `includes/config.local.php` als `return [...]` ablegen.

### Automatisierte Tests (Playwright)

```bash
pnpm exec playwright install chromium webkit   # einmalig
pnpm test              # lokal: startet PHP-Server auf 127.0.0.1:8123 mit Kopie der DB (.playwright-data/)
pnpm test:live         # gegen die Live-URL aus playwright.config.js (TEST_ENV=live)
pnpm test:ui           # interaktiver Modus
```

Getestet wird mit mobilen Profilen (iPhone 13 / WebKit und Pixel 7 / Chromium). Die Tests verändern `data/game.sqlite` nicht, da der Server über `MS_DB_PATH` eine Kopie verwendet. Das Live-Setup erwartet, dass `index.html` und die API unter der dort eingetragenen URL erreichbar sind.

### Struktur

| Pfad                | Inhalt                                                                                                                          |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| `api/`              | Kategorien, Pins und Einstellungen; Stationsdetails, Standortprüfung und Freigabe-Reset; Vorschläge (rate-limitiert, Honeypot) |
| `admin/`            | Login (5 Min. Inaktivitäts-Timeout), Stations- und UI-Einstellungen, Leaflet-Kartenpicker, Archivieren, Vorschlagsmoderation    |
| `includes/`         | Konfiguration, PDO, Auth, CSRF, Validierung, Uploads und UI-Registry                                                            |
| `api/`              | `settings.php`, `map_pins.php`, `get_details.php`, `submit_station.php` (Vorschläge, rate-limitiert, Honeypot)                  |
| `admin/`            | Login (5 Min. Inaktivitäts-Timeout), Stations- und Feature-Einstellungen, Leaflet-Kartenpicker, Archivieren, Vorschlagsmoderation |
| `includes/`         | Konfiguration, PDO, Auth, CSRF, Validierung, Uploads und Feature-Registry                                                       |
| `setup/`            | Schema, DB-Snapshot und Reset-Skript; alte Import-/Migrationsskripte in `OLD/`                                                                     |
| `data/`, `uploads/` | SQLite-Datei bzw. Bilder, jeweils per `.htaccess` abgesichert                                                       |

Stationen werden im Admin nur archiviert, nicht gelöscht. Beim Deployment auf Apache/All-Inkl müssen `data/` und `uploads/` für PHP beschreibbar sein; HTTPS wird für das Session-Cookie (`Secure`) vorausgesetzt. Testkonten vor dem Livegang ersetzen.
