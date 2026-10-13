# Münster unvermutet

## Spielprinzip

„Münster unvermutet“ ist eine digitale Foto-Schnitzeljagd durch Münster. Auf einer interaktiven Karte sind 14 Stationen in der Innenstadt markiert. Jede Station zeigt ein Foto eines ungewöhnlichen Details an einem Gebäude, Denkmal, Relief oder einer Inschrift.

Die Aufgabe besteht darin, den abgebildeten Ort in Münster zu finden und anschließend eine Multiple-Choice-Frage zur Geschichte oder Bedeutung des Details zu beantworten. Bei einer richtigen Antwort wird die Station gelöst. Danach erscheinen ein Lösungsfoto, der Name des Ortes, Hintergrundinformationen und – sofern vorhanden – weiterführende Links.

Die Stationen können in beliebiger Reihenfolge gespielt werden. Der Fortschritt wird lokal im Browser gespeichert. Gelöste Stationen werden auf der Karte mit einem Häkchen markiert. Sobald alle 14 Stationen gelöst sind, erscheint eine Abschlussansicht mit einer Statistik zu selbst gelösten Aufgaben, verwendeten Hinweisen und Fehlversuchen.

## Kurzanleitung

1. Öffne [`index.html`](./index.html) in einem modernen Webbrowser.
2. Wähle eine Station, indem du einen nummerierten Pin auf der Karte oder eine Station in der Liste anklickst.
3. Sieh dir das Aufgabenfoto an und suche das abgebildete Detail vor Ort.
4. Nutze bei Bedarf den Button **Hint anzeigen**. Die Koordinaten können außerdem direkt in Google Maps geöffnet werden.
5. Lies die Antwortmöglichkeiten und wähle die passende Antwort aus.
6. Bei einer falschen Antwort kannst du es erneut versuchen. Mit **Lösung anzeigen** lässt sich die Station auch direkt auflösen.
7. Nach dem Lösen kannst du die Ortsinformationen und das Lösungsfoto ansehen. Über **Nächste Station** gelangst du zur nächsten noch offenen Aufgabe.
8. Kehre über **Zurück zum Stadtplan** zur Übersicht zurück und spiele weitere Stationen.

## Fortschritt

Der Fortschrittsbalken zeigt an, wie viele Stationen bereits gelöst sind. Der Spielstand wird mit `localStorage` im verwendeten Browser gespeichert und bleibt beim erneuten Öffnen der Seite erhalten.

Mit **Zurücksetzen** kann der gesamte Fortschritt gelöscht und das Spiel neu begonnen werden. Beim vollständigen Durchspielen werden zusätzlich Rang und Spielstatistik angezeigt.

## Voraussetzungen und Dateien

- Für die interaktive Karte und die Kartendaten wird eine Internetverbindung benötigt.
- Die Aufgaben- und Lösungsfotos müssen neben der HTML-Datei liegen und nach diesem Schema benannt sein:
  - `Aufgabe 1.jpg` bis `Aufgabe 14.jpg`
  - `Loesung 1.jpg` bis `Loesung 14.jpg`
- Die Anwendung nutzt Leaflet und OpenStreetMap für die Karte.

## Backend

Das Spiel hat ein schlankes PHP-Backend mit SQLite-Datenbank und einem geschützten Admin-Bereich. Die API-Variante des Frontends ist [`index.html`](./index.html): Sie lädt zunächst Kategorien (`api/categories.php`) und Karten-Pins (`api/map_pins.php`) und erst beim Öffnen einer Station die vollständigen Stationsdaten (`api/get_details.php`, mit Ladezustand, Fehleranzeige und Retry). Namen, hochgeladene Kategoriebilder und Farben stammen aus der Datenbank und werden im Admin-Bereich gepflegt. Der Spielfortschritt bleibt weiterhin lokal im Browser (`localStorage`). Die statische [`index.html`](./index.html) bleibt unverändert als Referenz erhalten.

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
./setup/reset_database.sh          # löscht die lokale DB, fragt Passwörter ab und richtet alles neu ein
php -S 127.0.0.1:8080              # lokaler Server
```

- Spiel: `http://127.0.0.1:8080/index.html`
- Admin: `http://127.0.0.1:8080/admin/login.php`
- `setup/reset_database.sh --yes` überspringt die Löschbestätigung und startet anschließend den PHP-Server. Das Spiel ist dann unter `http://127.0.0.1:8080/index.html`, der Admin-Bereich unter `http://127.0.0.1:8080/admin/login.php` erreichbar. Für automatisierte Aufrufe können `ADMIN_STINA_PASSWORD`, `ADMIN_KOLJA_PASSWORD` und `ADMIN_ADMIN_PASSWORD` als Umgebungsvariablen gesetzt werden.
- Host und Port lassen sich über `PHP_SERVER_HOST` und `PHP_SERVER_PORT` ändern.
- Die Datenbank wird beim ersten Zugriff automatisch aus `setup/schema.sql` angelegt (`data/game.sqlite`).
- Vorhandene SQLite-Datenbanken werden nicht automatisch migriert; die ergänzte Kategoriespalte setzt eine frisch angelegte Datenbank voraus.
- Der Import übernimmt vorhandene Fotos `Aufgabe N.jpg` / `Loesung N.jpg` (im Projektordner, `OLD/`, `setup/images/` oder `setup/photos/`) in `uploads/`. Fehlende Fotos können im Admin-Bereich hochgeladen werden.
- Der Migrationslauf legt zusätzlich die drei offenen Beispielvorschläge aus `setup/suggestions_seed.json` an. Bereits vorhandene Vorschläge werden nicht dupliziert; die Beispielbilder werden aus den vorhandenen `Aufgabe N.jpg`-Dateien übernommen.
- Eigenes Passwort setzen: `php setup/create_admins.php <benutzer> <passwort>` (mind. 12 Zeichen).
- Abweichende Einstellungen (z. B. DB-Pfad außerhalb des Webroots) in `includes/config.local.php` als `return [...]` ablegen.

### Struktur

| Pfad                | Inhalt                                                                                                              |
| ------------------- | ------------------------------------------------------------------------------------------------------------------- |
| `api/`              | `map_pins.php`, `get_details.php`, `submit_station.php` (Vorschläge, rate-limitiert, Honeypot)                      |
| `admin/`            | Login (5 Min. Inaktivitäts-Timeout), Stationsverwaltung mit Leaflet-Kartenpicker, Archivieren, Vorschlagsmoderation |
| `includes/`         | Konfiguration, PDO, Auth, CSRF, Validierung, Uploads                                                                |
| `setup/`            | Schema, Seed-Daten, Import- und Admin-Skripte (nur per CLI)                                                         |
| `data/`, `uploads/` | SQLite-Datei bzw. Bilder, jeweils per `.htaccess` abgesichert                                                       |

Stationen werden im Admin nur archiviert, nicht gelöscht. Beim Deployment auf Apache/All-Inkl müssen `data/` und `uploads/` für PHP beschreibbar sein; HTTPS wird für das Session-Cookie (`Secure`) vorausgesetzt. Testkonten vor dem Livegang ersetzen.
