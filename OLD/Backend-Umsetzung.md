# Plan zur Backend-Umsetzung

## 1. Ziel und verbindliche Rahmenbedingungen

Das Backend ergänzt die bestehende browserbasierte Anwendung „Münster unvermutet“ um eine pflegbare Datenhaltung und einen geschützten Admin-Bereich.

Festgelegt:

- **Backend:** Vanilla PHP, ohne Framework und ohne Composer-Abhängigkeiten
- **Datenbank:** SQLite über PDO
- **Hosting:** All-Inkl-Webspace mit aktuellem PHP, Apache und aktiviertem PDO/SQLite
- **Transport:** HTTPS
- **Anwendung:** wahrscheinlich in einem Unterverzeichnis der Domain
- **Frontend:** bestehende Single-Page-App in `index.html`
- **Spielstand:** weiterhin ausschließlich im Browser-`localStorage`
- **Antwortprüfung:** weiterhin im Client-JavaScript; Manipulation ist für dieses Spiel ohne praktische Bedeutung
- **Admins:** drei individuelle Admin-Konten mit derselben Admin-Berechtigung
- **Admin-Session:** fünf Minuten Inaktivitäts-Timeout
- **Kein 2FA, kein Passwort-Reset und keine fachlichen Änderungslogs** in der ersten Version
- **Bilder:** weiterhin als Dateien in einem zur Anwendung relativen Upload-Ordner
- **Migration:** keine Altversion; die 14 aktuellen Stationen werden einmalig aus dem bestehenden JavaScript importiert

Das Backend wird so strukturiert, dass später MySQL oder zusätzliche Funktionen ergänzt werden können, ohne die öffentliche API grundlegend zu ändern.

## 2. Zielstruktur

Vorgesehene Struktur:

```text
/
├── index.html
├── api/
│   ├── bootstrap.php
│   ├── map_pins.php
│   ├── categories.php
│   ├── get_details.php
│   └── submit_station.php
├── admin/
│   ├── _bootstrap.php
│   ├── login.php
│   ├── logout.php
│   ├── admin.css
│   ├── index.php
│   ├── categories.php
│   ├── category-edit.php
│   ├── category-save.php
│   ├── category-delete.php
│   ├── help.php
│   ├── station-edit.php
│   ├── station-save.php
│   ├── station-delete.php
│   ├── suggestions.php
│   └── suggestion-action.php
├── includes/
│   ├── config.php
│   ├── database.php
│   ├── auth.php
│   ├── csrf.php
│   ├── json.php
│   ├── validation.php
│   └── uploads.php
├── data/
│   └── game.sqlite
├── uploads/
│   ├── .htaccess
│   └── ...
├── setup/
│   ├── schema.sql
│   ├── migrate_stations.php
│   └── create_admins.php
└── README.md
```

`data/` sollte möglichst außerhalb des öffentlich erreichbaren Webroots liegen. Falls dies beim All-Inkl-Webspace nicht möglich ist, wird der direkte Zugriff per `.htaccess` gesperrt.

Die Datei `config.php` enthält keine Zugangsdaten im Repository. Datenbankpfad, Session-Parameter und Upload-Konfiguration werden dort zentral verwaltet. Der SQLite-Pfad wird relativ zur Serverdatei und nicht relativ zur URL aufgelöst.

## 3. Datenbankschema

### 3.1 `stations`

```sql
CREATE TABLE stations (
    id TEXT PRIMARY KEY,
    number INTEGER NOT NULL,
    title TEXT NOT NULL,
    hint TEXT NOT NULL,
    lat NUMERIC NOT NULL,
    lon NUMERIC NOT NULL,
    correct_option_index INTEGER NOT NULL,
    solution_title TEXT NOT NULL,
    solution_caption TEXT,
    info TEXT NOT NULL,
    task_image TEXT NOT NULL,
    solution_image TEXT,
    category TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'published'
        CHECK (status IN ('published', 'archived')),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE UNIQUE INDEX stations_number_unique
    ON stations(number);

CREATE INDEX stations_status_index
    ON stations(status);
```

Es gibt zunächst nur `published` und `archived`. Vorschläge werden nicht als unfertige Stationen in dieser Tabelle gespeichert, sondern separat moderiert.

### 3.2 `options`

```sql
CREATE TABLE options (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    station_id TEXT NOT NULL,
    option_index INTEGER NOT NULL,
    option_text TEXT NOT NULL,
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE,
    UNIQUE (station_id, option_index)
);
```

Eine Station muss zwei bis vier Optionen besitzen. `correct_option_index` muss beim Speichern auf eine tatsächlich vorhandene Option zeigen.

### 3.3 `links`

```sql
CREATE TABLE links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    station_id TEXT NOT NULL,
    title TEXT NOT NULL,
    url TEXT NOT NULL,
    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE
);
```

Akzeptiert werden nur `https://`-URLs und – falls fachlich erforderlich – `http://`-URLs. Schemes wie `javascript:` oder `data:` werden abgelehnt.

### 3.4 `admins`

```sql
CREATE TABLE admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL
);
```

Passwörter werden ausschließlich mit `password_hash()` gespeichert und mit `password_verify()` geprüft.

### 3.5 `station_suggestions`

Vorschläge werden getrennt von offiziellen Stationen gespeichert:

```sql
CREATE TABLE station_suggestions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    hint TEXT,
    lat NUMERIC,
    lon NUMERIC,
    question TEXT,
    options_json TEXT,
    correct_option_index INTEGER,
    info TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'detail',
    suggested_by_name TEXT,
    suggested_by_email TEXT,
    task_image TEXT,
    solution_image TEXT,
    status TEXT NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'approved', 'rejected')),
    created_at TEXT NOT NULL,
    reviewed_at TEXT
);
```

So bleiben untrusted Benutzereingaben von den veröffentlichten Stationen getrennt.
Basisdaten reichen für eine Einreichung aus; redaktionelle Felder und Standort
dürfen fehlen und werden vor der Veröffentlichung im Admin-Bereich ergänzt.

## 4. API-Design

Alle API-Endpunkte liefern `Content-Type: application/json; charset=utf-8` und eine einheitliche Antwortstruktur.

Erfolg:

```json
{
  "data": {}
}
```

Fehler:

```json
{
  "data": null,
  "error": {
    "code": "station_not_found",
    "message": "Station nicht gefunden."
  }
}
```

### 4.1 `GET /api/map_pins.php`

Liefert ausschließlich veröffentlichte Stationen:

```json
{
  "data": [
    {
      "id": "jan-van-leiden",
      "number": 1,
      "title": "Der Denker mit dem Strich durch die Rechnung",
      "lat": 51.963083,
      "lon": 7.626028,
      "category": "history"
    }
  ]
}
```

Die Antwort enthält keine Antwortoptionen, Lösungstexte oder `correct_option_index`.

### 4.2 `GET /api/get_details.php?id=...`

Die ID wird ausschließlich über einen Prepared Statement-Parameter abgefragt. Nur `published`-Stationen werden ausgeliefert.

Die Antwort enthält die Pin-Grunddaten erneut sowie:

- Hinweis
- Antwortoptionen
- `correctOptionIndex`
- Lösungsdaten
- Bildpfade
- weiterführende Links

Die Auslieferung der richtigen Antwort ist bewusst akzeptiert, weil es keine Wertung, Rangliste oder Wettbewerbssituation gibt.

### 4.3 `POST /api/submit_station.php`

Der Endpunkt akzeptiert JSON mit `version: 1`, `name`, `description`,
`categoryId` und optionalem `location`-/`editorial`-Block. Der optionale
Frageblock wird nur vollständig akzeptiert (zwei bis vier Optionen und gültiger
Index). Der Eintrag wird immer als `pending` gespeichert und nie automatisch
veröffentlicht. Das bisherige vollständige Formulardatenformat bleibt für
bestehende Integrationen verfügbar.

Pflichtmaßnahmen:

- ausschließlich `POST`
- Größen- und Feldlimits
- Koordinaten- und Kategorie-Validierung
- Rate-Limiting
- Honeypot gegen einfache Bots
- serverseitige Upload-Prüfung für das alte Formularformat
- Speicherung als `pending`

## 5. Frontend-Umstellung

Das statische `STATIONS`-Array in [index-without-api.html](./OLD/index-without-api.html) wird durch eine Laufzeitstruktur ersetzt:

```js
let stations = [];
const stationsById = new Map();
const stationDetailsCache = new Map();
```

### Startablauf

1. `map_pins.php` per `fetch()` laden.
2. JSON und Stationsstruktur validieren.
3. `stationsById` aufbauen.
4. Karte und Marker erzeugen.
5. Stationsliste und Fortschritt rendern.

### Beim Öffnen einer Station

1. Pin-Grunddaten aus `stationsById` verwenden.
2. Detaildaten aus `stationDetailsCache` verwenden, falls vorhanden.
3. Sonst `get_details.php` laden.
4. Einen sichtbaren Ladezustand anzeigen.
5. Bei Fehlern eine Retry-Möglichkeit anzeigen.
6. Detaildaten mit den Pin-Daten zusammenführen.

Anzupassen sind insbesondere:

- `initMap()`
- `updateMapPins()`
- `renderStationList()`
- `render()`
- `openStation()`
- `renderTaskPhoto()`
- `renderSolvedBody()`
- `nextUnsolvedStation()`
- `renderProgress()`
- `renderCompletion()`

Die bestehende `localStorage`-Logik bleibt erhalten. Die stabilen Stations-IDs werden unverändert aus dem bisherigen Array übernommen.

## 6. Admin-Bereich

### Login

- `admin/login.php` zeigt das Loginformular.
- Erfolgreiches Login regeneriert die Session-ID.
- Session-Cookie wird mit `HttpOnly`, `Secure` und `SameSite=Lax` gesetzt.
- Nach fünf Minuten Inaktivität wird die Session ungültig.
- Jede Admin-Seite prüft die Session selbst.
- Fehlgeschlagene Logins erhalten eine allgemeine Fehlermeldung.

### Stationsverwaltung

`admin/index.php` zeigt veröffentlichte und archivierte Stationen. Das Formular für Erstellung und Bearbeitung enthält:

- Stammdaten
- Kategorie
- Koordinaten
- Kartenpicker mit Leaflet und OpenStreetMap
- Antwortoptionen
- korrekte Option
- Bilder
- weiterführende Links

Das Speichern einer Station, ihrer Optionen und ihrer Links erfolgt in einer SQLite-Transaktion.

### Löschen und Archivieren

Stationen werden standardmäßig archiviert, nicht physisch gelöscht. Ein physisches Löschen wird zunächst nicht über die Oberfläche angeboten. Dadurch bleiben versehentliche Datenverluste vermeidbar und bestehende lokale Spielstände verursachen keine unerwarteten Fehler.

### Vorschlagsmoderation

Die Moderationsansicht zeigt `pending`-Vorschläge. Ein Admin kann:

- Vorschlag prüfen,
- Inhalte bearbeiten,
- Bilder ersetzen,
- als neue Station veröffentlichen,
- ablehnen.

Bei der Veröffentlichung wird eine neue stabile Stations-ID vergeben oder die vom Admin bestätigte ID verwendet.

## 7. Bild-Uploads

Die Bilder werden im relativen Upload-Ordner gespeichert. Der Upload-Handler:

1. prüft den PHP-Uploadstatus,
2. begrenzt die Dateigröße,
3. prüft den Inhalt mit `finfo_file()` und `getimagesize()`,
4. erlaubt nur JPEG und PNG,
5. erzeugt einen zufälligen Dateinamen mit `random_bytes()`,
6. übernimmt niemals den Originaldateinamen,
7. verhindert Überschreiben bestehender Dateien,
8. speichert nur den kontrollierten relativen Pfad,
9. ersetzt Bilder beim Aktualisieren erst nach erfolgreicher Datenbankspeicherung,
10. verhindert PHP-Ausführung im Upload-Ordner per `.htaccess`.

Maximalwerte werden zentral konfiguriert. Als Startwerte gelten 8 MB pro Bild und maximal 4000 Pixel Kantenlänge.

## 8. Sicherheitsmaßnahmen

Verbindlich:

- Prepared Statements für jede Datenbankabfrage
- keine SQL-Stringverkettung mit Nutzereingaben
- HTML-Escaping in Admin- und Frontend-Ausgaben
- URL-Whitelist für externe Links
- CSRF-Token für alle Admin-POST-Formulare
- Session-Regeneration nach Login
- fünf Minuten Inaktivitäts-Timeout
- Rate-Limiting für Login und öffentliche Vorschläge
- Upload-Validierung auf Serverebene
- keine internen Pfade oder Stacktraces in API-Antworten
- SQLite-Datei vor direktem Webzugriff schützen
- Konfigurationsdateien nicht öffentlich ausliefern

Da Frontend und API voraussichtlich unter derselben Origin laufen, wird zunächst kein offenes CORS eingerichtet.

## 9. Umsetzungsphasen

### Phase 1: Grundgerüst

- Verzeichnisse anlegen.
- zentrale Konfiguration erstellen.
- PDO-Verbindung und JSON-Helfer implementieren.
- SQLite-Schema erstellen.
- Zugriffsschutz für `data/` und `uploads/` ergänzen.

### Phase 2: Datenimport

- `STATIONS` aus [index-without-api.html](./OLD/index-without-api.html) in die Datenbank übertragen.
- Optionen und Links normalisieren.
- vorhandene Bilder in den Upload-Ordner übernehmen.
- Importergebnis mit den 14 Stationen vergleichen.

### Phase 3: Öffentliche API

- `map_pins.php` implementieren.
- `get_details.php` implementieren.
- Validierung, Statusfilter und Fehlerantworten ergänzen.
- API mit gültigen, fehlenden und ungültigen IDs testen.

### Phase 4: Frontend-Anbindung

- dynamisches Laden der Pins.
- Detaildaten beim Öffnen nachladen.
- Lade-, Fehler- und Retry-Zustände ergänzen.
- `STATIONS`-Abhängigkeiten entfernen.
- localStorage-Fortschritt mit stabilen IDs prüfen.

### Phase 5: Authentifizierung und Admin-CRUD

- Admin-Tabelle und Testkonten anlegen.
- Login und Session-Timeout implementieren.
- CSRF-Schutz ergänzen.
- Stationsliste, Bearbeitung, Archivierung und Speicherung erstellen.

### Phase 6: Kartenpicker und Uploads

- Leaflet-Kartenpicker im Adminformular integrieren.
- Uploadvalidierung implementieren.
- Bildpfade in Stationen speichern.
- Test mit gültigen, zu großen und manipulierten Dateien durchführen.

### Phase 7: Vorschläge

- `station_suggestions` und Moderationsansicht implementieren.
- öffentliche Vorschlagsannahme erst danach freischalten.
- Rate-Limiting und Spam-Schutz testen.

### Phase 8: Deployment und Abnahme

- HTTPS und Unterverzeichnisbetrieb prüfen.
- PHP- und Apache-Konfiguration prüfen.
- Schreibrechte für SQLite und Uploads testen.
- Backup und Wiederherstellung testen.
- Produktionszugänge und Testdaten trennen.

## 10. Test- und Abnahmekriterien

Das Backend gilt als bereit, wenn:

- alle 14 Stationen über die API erscheinen,
- keine `pending`- oder archivierten Stationen öffentlich sichtbar sind,
- Detaildaten korrekt geladen und gecacht werden,
- fehlende Stationen einen kontrollierten 404-JSON-Fehler liefern,
- der Fortschritt nach API-Umstellung erhalten bleibt,
- alle drei Admins sich anmelden können,
- Sessions nach fünf Minuten Inaktivität ablaufen,
- nicht eingeloggte Nutzer keine Admin-Seiten sehen,
- CSRF-geschützte Formulare funktionieren,
- Stationen einschließlich Optionen und Links transaktional gespeichert werden,
- Uploads nur mit erlaubtem Bildtyp und innerhalb der Größenlimits akzeptiert werden,
- die SQLite-Datei nicht über den Browser abrufbar ist,
- das Spiel im Unterverzeichnis der Domain funktioniert,
- ein Backup erfolgreich zurückgesichert werden kann.

## 11. Testzugänge für die Entwicklung

Diese Zugangsdaten sind ausschließlich für die lokale beziehungsweise erste Testinstallation vorgesehen und dürfen nicht unverändert produktiv verwendet werden:

| Benutzername | Testpasswort |
|---|---|
| `admin1` | `Muenster-Test-2026-1!` |
| `admin2` | `Muenster-Test-2026-2!` |
| `admin3` | `Muenster-Test-2026-3!` |

Beim produktiven Deployment müssen die Passwörter vor dem ersten öffentlichen Zugriff geändert beziehungsweise die Testkonten durch neu erzeugte Zugangsdaten ersetzt werden. Die Passwörter werden nicht im Klartext in der Datenbank gespeichert.

## 12. Bewusst nicht enthaltene Funktionen

In der ersten Version werden nicht umgesetzt:

- serverseitige Antwortbewertung,
- Benutzerkonten für Spieler,
- serverseitige Spielstände,
- Bestenlisten oder Wettbewerb,
- Zwei-Faktor-Authentifizierung,
- Passwort-Reset,
- detaillierte Änderungs- oder Audit-Logs,
- automatische Bildoptimierung über externe Dienste,
- offene CORS-Freigaben,
- MySQL-Unterstützung als parallele Laufzeitdatenbank.

## 13. Plan: Kategorienverwaltung, Admin-Redesign und Dark Mode

### 13.1 Ziele

- Kategorien im Admin-Bereich erstellen, bearbeiten und (wenn unbenutzt) löschen.
- Je Kategorie: Name, kurze Beschreibung und ein vom Admin hochgeladenes Symbol (Icon).
- Unveränderliche, automatisch vergebene Stammdaten (ID, Nummer) werden im Admin nicht mehr angezeigt oder abgefragt.
- Moderneres Admin-Design auf Basis der Farben und Klassen von `index.html`.
- Automatischer Dark Mode (Farbschema One Dark) über `prefers-color-scheme`.
- Dokumentation der neuen Funktionen im Admin-Bereich.

### 13.2 Datenmodell

Neue Tabelle `categories` in `setup/schema.sql` (idempotent, wird bei jedem DB-Zugriff ausgeführt):

| Feld | Beschreibung |
|---|---|
| `id` | stabile, aus dem Namen erzeugte Kennung (Slug), unveränderlich, entspricht `stations.category` |
| `name` | Anzeigename, eindeutig (ohne Beachtung der Groß-/Kleinschreibung) |
| `description` | kurze Beschreibung, max. 300 Zeichen |
| `icon` | Pfad zum Upload (`uploads/<hash>.jpg/png`) oder `NULL` |
| `created_at`, `updated_at` | Zeitstempel |

Die sechs bisherigen Kategorien (`history`, `art`, `legend`, `faith`, `detail`, `culture`) werden per `INSERT OR IGNORE` übernommen, sodass bestehende Stationen gültig bleiben. Die feste Liste `categories` in `config.php` entfällt; `validate_station_input()` prüft gegen die Tabelle.

### 13.3 Umsetzungsschritte

1. Schema erweitern und Hilfsfunktionen `category_exists()` und `all_categories()` ergänzen.
2. Admin-Seiten `categories.php`, `category-edit.php`, `category-save.php`, `category-delete.php`:
   - Erstellen/Bearbeiten mit CSRF-Schutz, Validierung und Upload über `store_upload()` (JPEG/PNG, Größen- und Kantenlängenprüfung).
   - Ersetztes oder entferntes Symbol wird erst nach erfolgreichem Speichern gelöscht; bei Fehlern wird der neue Upload verworfen.
   - Löschen nur ohne zugeordnete Stationen.
3. Stationsformular: Kategorie-Auswahl aus der Tabelle (Anzeige des Namens), Felder ID und Nummer entfernt. ID (Slug aus Titel) und Nummer (nächste freie) werden beim Anlegen automatisch vergeben und danach nie geändert. Die Stationsliste zeigt Titel, Kategorie mit Symbol und Status.
4. Öffentliche API `GET api/categories.php` liefert `id`, `name`, `description`, `icon`. Das Frontend kann Legende, Chips und Pins mittelfristig damit statt der fest codierten `CATEGORIES` aufbauen (Farben bleiben bis dahin im Frontend).
5. Design: neue Datei `admin/admin.css` ersetzt die Inline-Styles. Sie übernimmt Variablennamen (`--green`, `--green-dark`, `--green-soft`, `--ink`, `--muted`, `--cat`, `--cat-soft`) und Klassen des Frontends (`.page`, `.hero`, `.task-card`, `.task-body`, `.station-list`, `.station-item`, `.ico`, `.state`, `.category-chip`, `.legend`, `.badge`, `.back-button`, `.next-button`, `.hint-box`, `.error-message`, `.how-to`, `.visually-hidden`). Admin-spezifisches trägt das Präfix `admin-`. Dadurch kann später eine gemeinsame CSS-Datei entstehen.
6. Dark Mode: alle Farben liegen in CSS-Variablen; ein `@media (prefers-color-scheme: dark)`-Block überschreibt sie mit One-Dark-Werten (Hintergrund `#282c34`/`#21252b`, Text `#abb2bf`, Rahmen `#3e4451`, Akzente `#98c379`, `#61afef`, `#e5c07b`, Fehler `#e06c75`). Kein Schalter, Umschaltung automatisch nach Browser-/Systemeinstellung (`color-scheme: light dark`).
7. Dokumentation: neue Seite `admin/help.php` (Navigationspunkt „Hilfe“) beschreibt Stationen, Kategorien (inkl. Symbol-Upload und Löschregel), Dark Mode und Vorschläge.

### 13.4 Abnahmekriterien

- Kategorie anlegen, Name/Beschreibung ändern, Symbol hochladen, ersetzen und entfernen funktioniert; ungültige Dateien werden abgelehnt.
- Eine Kategorie mit Stationen lässt sich nicht löschen; eine unbenutzte schon (inkl. Entfernen der Bilddatei).
- Neue Stationen erhalten automatisch eindeutige ID und Nummer; im Formular und in der Liste erscheinen beide nicht.
- Bestehende Stationen sind nach der Migration weiterhin einer gültigen Kategorie zugeordnet.
- Admin-Oberfläche ist mit hellen und dunklen System-/Browsereinstellungen lesbar (Kontrast, Fokusmarkierung, Mobilansicht).
- `api/categories.php` liefert die Kategorien; `map_pins.php` und `get_details.php` bleiben unverändert kompatibel.
- Hilfeseite erklärt Kategorien und Dark Mode.
