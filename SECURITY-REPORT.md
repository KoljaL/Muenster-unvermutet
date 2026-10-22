# Security Report – Münster unvermutet

Umfang: gesamtes Repository (PHP, SQLite, Vanilla-JS-PWA: `api/`, `admin/`, `includes/`, `app.js`, `index.html`, `setup/`, `data/`, `uploads/`).
Geprüft: SQL-Injection/Prepared Statements, Eingabevalidierung, Sessions/CSRF/Auth, Uploads, XSS/DOM, Fetch-Fehlerbehandlung, JS↔PHP-Schnittstellen, Secrets/Konfiguration, DB-Pfad.

## Übersicht

| # | Priorität | Datei | Zeilen | Problem | Konfidenz |
|---|-----------|-------|--------|---------|-----------|
| 1 | Mittel | `app.js`, `api/verify_location.php`, `api/get_details.php` | app.js 601, 735–760, 859–860; verify_location.php 26–75; get_details.php 25–60 | Standortprüfung umgehbar (Broken Access Control) | 10/10 |

Hoch: keine bestätigten Funde. Niedrig: keine bestätigten Funde.

## Fund 1 – Standortprüfung umgehbar (Mittel)

**Beschreibung:** Ist die Standortsperre aktiv, rendert die App einen Button „Fake Position (Dev)“. `devFakePosition()` sendet die Koordinaten der Station als eigenen Standort an die normale Verifizierung. Die API akzeptiert vom Client gelieferte Koordinaten als Anwesenheitsnachweis und markiert die Station als verifiziert; `get_details.php` gibt danach Antworten/Lösung frei. Auch ohne den Button lassen sich per direktem API-Aufruf beliebige Koordinaten senden.

**Empfehlung:**
1. Fake-Position-Button und zugehörigen Code aus dem Produktivcode entfernen (oder nur in Dev-Builds).
2. Clientseitige GPS-Daten nicht als Beweis behandeln. Für echte Vor-Ort-Prüfung ein serverseitig prüfbares Verfahren nutzen (z. B. QR-Code/Einmal-Token an der Station). Alternativ als bewusst akzeptiertes Risiko dokumentieren (Spiel ohne Sicherheitsrelevanz) und Rate-Limiting ergänzen.

## Positive Beobachtungen / Hinweise

- Im Review wurden keine SQL-Injection-, XSS-, CSRF-, Upload- oder Secret-Funde bestätigt.
- Empfohlen bleibt, regelmäßig zu prüfen: `includes/config.local.php` und `data/game.sqlite` dürfen nicht im Repo/Webroot ausgeliefert werden (Zugriff per Webserver sperren, z. B. `.htaccess`), und `uploads/` darf keine PHP-Ausführung erlauben.
