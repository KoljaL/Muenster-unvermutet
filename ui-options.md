# Konzept: UI-Optionen aus dem Backend

Ziel: Elemente der [index.html](index.html) werden abhängig von Einstellungen im Backend angezeigt oder gar nicht initialisiert/gerendert. Die Einstellungen gelten **global für alle Besucher**.

## Entscheidungen

| Thema | Entscheidung |
|---|---|
| Geltungsbereich | Global (nicht pro Kategorie/Station) |
| Speicherung | SQLite, Tabelle `settings` (Key/Value) |
| Auslieferung | Öffentlicher Endpunkt `api/settings.php` |
| Ausblenden | Element wird nicht initialisiert und nicht gerendert (kein reines CSS) |
| Standard | Alles sichtbar, wenn Einstellung fehlt oder die API nicht erreichbar ist |
| Zustände je Option | `aus`, `an`, `test` (nur per URL-Parameter sichtbar) |
| Erweiterbarkeit | Zentrale Registry in PHP; neues Element = ein Eintrag + `isEnabled()` im Frontend |
| Testmodus | `?preview=key1,key2` oder `?preview=all`, ohne Token |

## Steuerbare Elemente

| Key | Bereich | Betroffene Stellen in index.html | Abhängigkeiten |
|---|---|---|---|
| `ui.help` | Hilfe-Button + „So funktioniert's“-Box | `#help-toggle`, `#how-to`, `setHowTo()` | – |
| `ui.accessibility_toggle` | Barrierefrei-Button (Liste + große Pins) | `#layout-toggle` | – |
| `ui.theme_toggle` | Dark-Mode-Button | `#theme-toggle`, `initThemeControls()` | System-Theme wird weiter respektiert |
| `ui.progress` | Fortschrittsbalken + Zähler | `.progress`, `#progress-*` | Blendet auch Badges und Reset mit aus |
| `ui.badges` | Mini-Badges | `#mini-badges` | nur sinnvoll mit `ui.progress` |
| `ui.reset` | „Zurücksetzen“ + Dialog | `#reset-button`, `#reset-dialog`, `initResetDialog()` | – |
| `ui.suggest` | „Station vorschlagen“ + Dialog + Karten-Picking | `#suggest-open`, `#suggest-dialog`, `#pick-banner`, `initSuggestion()` | Backend lehnt `submit_station.php` bei `false` mit 403 ab |
| `ui.legend` | Kategorie-Legende/-Filter | `.legend-button`-Liste | – |
| `ui.station_list` | Stationsliste (Barrierefrei-Ansicht) | `elements.stationList` | Ohne Liste bleibt die Karte; Barrierefrei-Toggle entfällt dann |
| `ui.hint_button` | „Hinweis anzeigen“ | `data-action="toggle-hint"` in `renderUnsolvedBody()` | – |
| `ui.reveal_button` | „Lösung anzeigen“ | `data-action="reveal"` | – |
| `ui.location_gate` | Standortabhängige Antwortfreigabe | `api/verify_location.php` und `api/get_details.php` | Serverseitig erzwungen; Testmodus nur mit passendem `preview` |
| `ui.station_map` | OSM-Orientierungskarte | Stationsansicht in `index.html` | unabhängig von `ui.location_gate` |
| `ui.completion` | Abschluss-Screen | `#completion` | – |

Regel für Abhängigkeiten: Sie stehen in der Registry (`requires`), der Server liefert sie mit; das Frontend löst sie in `resolveUi()` auf (z. B. `badges = badges && progress`).

## Registry (Erweiterbarkeit)

Alle Optionen sind an **einer** Stelle definiert, `includes/settings.php`, Konstante `UI_REGISTRY`:

```php
'suggest' => [
    'label'       => 'Station vorschlagen',
    'group'       => 'Karte',
    'description' => 'Button, Dialog und Karten-Picking',
    'default'     => 'on',          // off | on | test
    'requires'    => [],
    'server_enforced' => true,      // API prüft zusätzlich
],
```

Aus der Registry werden erzeugt: Defaults, Whitelist/Validierung, API-Antwort und das Admin-Formular (gruppiert, inkl. Beschreibung).

**Neues Element hinzufügen:**
1. Eintrag in `UI_REGISTRY`.
2. Im Frontend das Element bzw. die `init*()`/`render*()`-Funktion mit `isEnabled('key')` absichern.

Datenbank, API und Admin müssen nicht angepasst werden.

## Datenmodell

```sql
CREATE TABLE IF NOT EXISTS settings (
    key        TEXT PRIMARY KEY,
    value      TEXT NOT NULL,          -- 'off' | 'on' | 'test'
    updated_at TEXT NOT NULL
);
```

- Migration über ein Skript `setup/migrate_settings.php` (analog zu den bestehenden Migrationen) und Eintrag in [setup/schema.sql](setup/schema.sql).
- Fehlende Keys werden mit dem Registry-Default ergänzt; unbekannte Keys und ungültige Werte werden ignoriert.

## API

`GET api/settings.php[?preview=key1,key2|all]` (Muster wie [api/categories.php](api/categories.php))

```json
{"ok": true, "data": { "ui": { "help": true, "suggest": false, "legend": true }, "location": { "enabled": false, "radius": 50, "maxAccuracy": 50 } } }
```

- Die API liefert bereits **aufgelöste Booleans**: `an` → `true`, `aus` → `false`, `test` → `true` nur, wenn der Key in `preview` steht (oder `preview=all`). Den Roh-Zustand `test` sieht das Frontend nicht. Zusätzlich enthält `data.location` die aufgelöste Freigabe sowie Prüfungsradius und maximale GPS-Ungenauigkeit.
- `requires` wird serverseitig angewendet.
- Nur `GET`, nur Registry-Keys, keine sensiblen Daten.
- Antworten mit `preview` werden nicht gecacht (`Cache-Control: no-store`). Ohne `preview` ist `max-age=60` zulässig (Änderungen wirken bis zu 60 s verzögert).
- `ui.location_gate` ist standardmäßig aus. Bei Aktivierung liefert `api/get_details.php` zunächst nur öffentliche Stationsdaten; Optionen und Lösung sind nur über `protected=1` nach einer erfolgreichen serverseitigen Prüfung verfügbar. `api/verify_location.php` nutzt offizielle Stationskoordinaten, verlangt eine gleich-originige POST-Anfrage und speichert nur die Stationsfreigabe in der PHP-Sitzung, keine Positionsdaten.
- Die Meterwerte liegen als `location_radius_m` (Standard 50, gültig 5–5000) und `location_max_accuracy_m` (Standard 50, gültig 1–500) in `settings`. Sie sind Zahlenwerte und keine UI-Schalter.

## Testmodus per URL

- Aufruf z. B. `index.html?preview=suggest,legend` oder `?preview=all`.
- Der Parameter schaltet **nur** Optionen im Zustand `test` ein; `aus` und `an` bleiben unberührt.
- Das Frontend reicht `preview` an `api/settings.php` weiter und sendet ihn bei serverseitig geprüften Aktionen mit (z. B. `submit_station.php`, wenn `server_enforced` gesetzt ist). Die API prüft dort mit derselben Logik, ob die Option für diesen Aufruf aktiv ist.
- Kein Token: Jeder mit der URL sieht die Vorschau. Das ist für Tests akzeptiert; es dürfen darüber keine schützenswerten Daten freigeschaltet werden.
- Die Vorschau gilt nur für den aktuellen Seitenaufruf, es wird nichts gespeichert.

## Admin

- Neue Seite `admin/settings.php` (Admin-Login via `require_admin()`, CSRF wie bei den anderen Formularen).
- Formular wird aus `UI_REGISTRY` erzeugt: je Option eine Auswahl `aus / an / Test` (Radio), gruppiert, mit Hilfetext.
- Hinweis auf der Seite, wie der Testmodus aufgerufen wird (Beispiel-URL mit `?preview=all`).
- Speichern über `admin/settings-save.php` (POST + CSRF, Werte strikt auf `off/on/test` und Registry-Keys validiert).
- Link „Einstellungen“ in der Navigation von `admin_header()` in [admin/_bootstrap.php](admin/_bootstrap.php).

## Frontend

1. **Laden**: Beim Start `StationApiService` um `loadSettings()` erweitern (inkl. Weitergabe von `?preview`). Der Abruf läuft parallel zu `loadStations()`; die UI wird erst nach beiden Antworten initialisiert.
2. **Fallback**: Bei Fehler/Timeout gelten die Defaults (alles sichtbar). Kein Fehlerdialog.
3. **Nicht initialisieren statt verstecken**:
   - Statische Elemente werden beim Start per `element.remove()` entfernt, **bevor** Event-Listener gebunden werden. Alle `document.getElementById(...)`-Zugriffe und `elements`-Einträge in [index.html](index.html) müssen null-sicher sein (`elements.x?.…`).
   - `init*()`-Funktionen (`initResetDialog`, `initThemeControls`, `initSuggestion`) werden nur bei aktivem Flag aufgerufen.
   - Dynamisch gerenderte Teile (Hinweis-/Lösungsbutton, Legende, Liste) prüfen das Flag in der jeweiligen `render*()`-Funktion.
4. **Flash vermeiden**: Betroffene statische Elemente starten mit `hidden`; sichtbar werden sie erst, wenn die Einstellungen geladen sind. Alternativ ein kurzer Ladezustand (es existiert bereits `#progress-loading`).
5. **Zentrale Struktur**: Ein Objekt `ui` (immutable nach dem Laden) plus Hilfsfunktion `isEnabled('suggest')`, damit keine Flags im Code verstreut werden.

## Sicherheit und Konsistenz

- Ausblenden ist **keine** Zugriffskontrolle. Funktionen mit Serverwirkung (`ui.suggest`) werden zusätzlich serverseitig geprüft.
- Admin-Seite nur für angemeldete Admins; CSRF-Token und Eingabevalidierung wie im Bestand ([includes/csrf.php](includes/csrf.php), [includes/validation.php](includes/validation.php)).
- Beim Deaktivieren von `ui.reset` bleibt der gespeicherte Fortschritt im Browser erhalten (nur die Bedienung entfällt).
- Beim Deaktivieren von `ui.progress` wird die Fortschrittslogik (localStorage) weiter geführt, damit `ui.completion` und spätere Reaktivierung funktionieren.

## Umsetzungsschritte

1. `settings`-Tabelle, Migration und `includes/settings.php` (`UI_REGISTRY`, Auflösung inkl. `requires` und `preview`, `save_ui_settings()`).
2. `api/settings.php`.
3. Admin-Seite `settings.php` + `settings-save.php` + Navigationslink.
4. Frontend: `loadSettings()`, `ui`/`isEnabled()`, Null-Sicherheit, bedingte Initialisierung/Rendering je Bereich.
5. `submit_station.php`: 403, wenn `suggest` für diesen Aufruf nicht aktiv ist (inkl. `preview`).
6. Tests (manuell): jede Option einzeln aus, alle aus, API nicht erreichbar, Testmodus (`test` ohne/mit `?preview`, `aus` bleibt aus), Tastatur-/Screenreader-Navigation ohne die entfernten Elemente, Dark-Mode/Barrierefrei-Kombinationen.

## Abgrenzung (entschieden)

- Keine Presets. URL-Parameter nur als Testmodus für Optionen im Zustand `test`, kein allgemeines Überschreiben.
- Der Abschluss-Screen hängt nicht vom Fortschritts-Schalter ab; er wird nur über `ui.completion` gesteuert.

## Offene Punkte

Keine.
