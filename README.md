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

### Einrichtung und lokaler Test

Voraussetzung: PHP 8+ mit PDO/SQLite und `fileinfo`.

```bash
php setup/create_admins.php        # legt admin1–admin3 mit den Testpasswörtern an
php setup/migrate_stations.php     # importiert Stationen und Beispielvorschläge (idempotent)
php setup/migrate_settings.php    # ergänzt fehlende globale Feature-Einstellungen (idempotent)
./setup/reset_database.sh          # löscht die lokale DB, fragt Passwörter ab und richtet alles neu ein
php -S 127.0.0.1:8080              # lokaler Server
```

- Spiel: `http://127.0.0.1:8080/index.html`
- Admin: `http://127.0.0.1:8080/admin/login.php`
- `setup/reset_database.sh --yes` überspringt die Löschbestätigung und startet anschließend den PHP-Server. Das Spiel ist dann unter `http://127.0.0.1:8080/index.html`, der Admin-Bereich unter `http://127.0.0.1:8080/admin/login.php` erreichbar. Für automatisierte Aufrufe können `ADMIN_STINA_PASSWORD`, `ADMIN_KOLJA_PASSWORD` und `ADMIN_ADMIN_PASSWORD` als Umgebungsvariablen gesetzt werden.
- Host und Port lassen sich über `PHP_SERVER_HOST` und `PHP_SERVER_PORT` ändern.
- Die Datenbank wird beim ersten Zugriff automatisch aus `setup/schema.sql` angelegt (`data/game.sqlite`).
- Vorhandene SQLite-Datenbanken werden nicht automatisch migriert; die ergänzte Kategoriespalte setzt eine frisch angelegte Datenbank voraus. `setup/migrate_settings.php` ergänzt die Feature-Einstellungstabelle und ihren fehlenden Standardwert.
- Der Import übernimmt vorhandene Fotos `Aufgabe N.jpg` / `Loesung N.jpg` (im Projektordner, `OLD/`, `setup/images/` oder `setup/photos/`) in `uploads/`. Fehlende Fotos können im Admin-Bereich hochgeladen werden.
- Der Migrationslauf legt zusätzlich die drei offenen Beispielvorschläge aus `setup/suggestions_seed.json` an. Bereits vorhandene Vorschläge werden nicht dupliziert; die Beispielbilder werden aus den vorhandenen `Aufgabe N.jpg`-Dateien übernommen.
- Eigenes Passwort setzen: `php setup/create_admins.php <benutzer> <passwort>` (mind. 12 Zeichen).
- Abweichende Einstellungen (z. B. DB-Pfad außerhalb des Webroots) in `includes/config.local.php` als `return [...]` ablegen.

### Struktur

| Pfad                | Inhalt                                                                                                                          |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| `api/`              | Kategorien, Pins und Einstellungen; Stationsdetails, Standortprüfung und Freigabe-Reset; Vorschläge (rate-limitiert, Honeypot) |
| `admin/`            | Login (5 Min. Inaktivitäts-Timeout), Stations- und UI-Einstellungen, Leaflet-Kartenpicker, Archivieren, Vorschlagsmoderation    |
| `includes/`         | Konfiguration, PDO, Auth, CSRF, Validierung, Uploads und UI-Registry                                                            |
| `api/`              | `settings.php`, `map_pins.php`, `get_details.php`, `submit_station.php` (Vorschläge, rate-limitiert, Honeypot)                  |
| `admin/`            | Login (5 Min. Inaktivitäts-Timeout), Stations- und Feature-Einstellungen, Leaflet-Kartenpicker, Archivieren, Vorschlagsmoderation |
| `includes/`         | Konfiguration, PDO, Auth, CSRF, Validierung, Uploads und Feature-Registry                                                       |
| `setup/`            | Schema, Seed-Daten, Import- und Migrationsskripte (nur per CLI)                                                                 |
| `data/`, `uploads/` | SQLite-Datei bzw. Bilder, jeweils per `.htaccess` abgesichert                                                       |

Stationen werden im Admin nur archiviert, nicht gelöscht. Beim Deployment auf Apache/All-Inkl müssen `data/` und `uploads/` für PHP beschreibbar sein; HTTPS wird für das Session-Cookie (`Secure`) vorausgesetzt. Testkonten vor dem Livegang ersetzen.
