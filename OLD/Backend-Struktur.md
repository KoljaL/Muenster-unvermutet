# Architektur-Spezifikation: PHP-Backend für „Münster unvermutet“

## 1. Kernprinzipien & Tech-Stack

* **Backend:** Reines PHP (OOP oder prozedural, sauber getrennt), **keine externen Bibliotheken/Frameworks** (kein Composer nötig, maximale Leichtgewichtigkeit).
* **Datenbank:** MySQL oder SQLite, angesprochen über **PDO** mit Prepared Statements.
* **Schnittstelle:** JSON-basierte API für das Frontend, HTML/JS für den Admin-Bereich.
* **Spiellogik im Frontend:** Die Validierung der Antwort und die Statistik/Fortschritts-Speicherung verbleiben im Browser (`localStorage`). Das Backend liefert Fragedaten **inklusive** der Lösung (`correctOptionIndex`) aus, sobald eine Station angeklickt wird.

---

## 2. Datenbank-Schema (Tabellenstruktur)

### A. `stations` (Zentrale Tabelle)

* `id` (VARCHAR, Primary Key – z. B. `'jan-van-leiden'`)
* `number` (INT)
* `title` (VARCHAR)
* `hint` (TEXT)
* `lat` (DECIMAL/FLOAT)
* `lon` (DECIMAL/FLOAT)
* `correct_option_index` (INT)
* `solution_title` (VARCHAR)
* `solution_caption` (VARCHAR)
* `info` (TEXT)
* `task_image` (VARCHAR – Dateiname/Pfad)
* `solution_image` (VARCHAR – Dateiname/Pfad)
* `category` (VARCHAR – z. B. `'history'`)
* `status` (ENUM: `'published'`, `'pending'`) *— für offizielle Stationen vs. Spielervorschläge*
* `suggested_by_name` (VARCHAR, NULLable)
* `suggested_by_email` (VARCHAR, NULLable)

### B. `options` (Antwortmöglichkeiten)

* `id` (INT, Primary Key, Auto Increment)
* `station_id` (VARCHAR, Foreign Key -> `stations.id`, ON DELETE CASCADE)
* `option_index` (INT – 0 bis 3)
* `option_text` (TEXT)

### C. `links` (Weiterführende Links)

* `id` (INT, Primary Key, Auto Increment)
* `station_id` (VARCHAR, Foreign Key -> `stations.id`, ON DELETE CASCADE)
* `title` (VARCHAR)
* `url` (TEXT)

### D. `admins` (Zugang für die 3 Administratoren)

* `id` (INT, Primary Key, Auto Increment)
* `username` (VARCHAR)
* `password_hash` (VARCHAR – via `password_hash()`)

---

## 3. API-Endpunkte (Öffentlich für das Frontend)

### `GET /api/map_pins.php`

* **Zweck:** Lädt alle aktiven Pins für die Startkarte und die Stationsliste.
* **Logik:** Query filtert nur Stationen mit `status = 'published'`.
* **Rückgabe (JSON):** Array mit `id`, `number`, `title`, `lat`, `lon`, `category`. Keine Fragen oder Lösungen!

### `GET /api/get_details.php?id={station_id}`

* **Zweck:** Liefert das komplette Datenpaket, sobald der Spieler auf eine Station klickt.
* **Rückgabe (JSON):**
* `hint`, `correctOptionIndex`, `solutionTitle`, `solutionCaption`, `info`, `task_image`, `solution_image`
* Array `options`
* Array `links` (`title`, `url`)



### `POST /api/submit_station.php` (Zukunftssicher für Spielervorschläge)

* **Zweck:** Nimmt von Spielern eingereichte Stationsvorschläge entgegen.
* **Input:** Formulardaten (Titel, Koordinaten, Frage, Optionen etc.), freiwilliger Name/E-Mail und Bilduploads.
* **Logik:** Speichert den Datensatz mit `status = 'pending'`, sichert die Bilder im Upload-Ordner.

---

## 4. Admin-Bereich & Funktionen (Geschützt für 3 Admins)

* **Authentifizierung:** Klassischer PHP-Session-Login (`session_start()`), geschützt durch `password_verify()`. Keine Rollen-Matrix nötig (alle 3 Admins haben Vollzugriff).
* **Stations-Verwaltung (CRUD):**
* Erstellen, Bearbeiten und Löschen von Stationen.
* **OSM-Kartenpicker:** Im Admin-Formular ist eine Leaflet-Karte integriert. Ein Klick darauf setzt automatisch die `lat`- und `lon`-Werte in die Eingabefelder.


* **Moderations-Queue:**
* Ein eigener Reiter zeigt eingereichte Spielervorschläge (`status = 'pending'`).
* Admin kann prüfen, Daten anpassen und per Klick auf **"Freigeben"** den Status auf `'published'` setzen, wodurch die Station automatisch auf der Live-Karte erscheint.


* **Bilder-Upload-Handler:**
* Validierung des MIME-Typs (nur JPG/PNG).
* Generierung sicherer, eindeutiger Dateinamen (z. B. `task_[station-id]_[timestamp].jpg`).
* Speicherung in einem zentralen `/uploads/`-Ordner.
* Rückgabe des Dateipfads an das Admin-Formular zur weiteren Verarbeitung.