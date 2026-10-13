# Implementierungsplan: Stationen vorschlagen

## Ziel

Das bestehende Feature „Station vorschlagen“ wird so erweitert, dass Nutzer
einen einfachen Basisvorschlag schnell einreichen können und optional
redaktionelle Zusatzinformationen beitragen können.

Der Admin prüft jeden Vorschlag, ergänzt fehlende Inhalte und entscheidet
anschließend über die Veröffentlichung. Es wird kein zweites Vorschlagsmodell
eingeführt; der bestehende Workflow mit `station_suggestions` bleibt erhalten.

## Grundprinzip

```text
Basisdaten des Nutzers
        +
optionale Zusatzinformationen
        ↓
station_suggestions (pending)
        ↓ Prüfung und Ergänzung durch Admin
vollständige Station
        ↓
stations (published)
```

Nutzereingaben gelten immer als unbestätigte Vorschläge. Sie dürfen nicht direkt
als veröffentlichte Stationsdaten verwendet werden.

## 1. Datenvertrag festlegen

### 1.1 Basisdaten

Pflichtfelder:

- `name`
- `description`
- `categoryId`

Optional:

- `location.latitude`
- `location.longitude`

Beispiel:

```json
{
  "version": 1,
  "name": "Beispielstation",
  "description": "Besonderheit am Gebäude",
  "categoryId": "history",
  "location": {
    "latitude": 51.9607,
    "longitude": 7.6261
  }
}
```

`categoryId` verwendet die stabile Kategorie-ID und nicht den sichtbaren
Kategorienamen. Das `location`-Objekt wird nur aufgenommen, wenn beide
Koordinaten vollständig und gültig sind.

### 1.2 Optionale redaktionelle Angaben

Der optionale Abschnitt wird unter `editorial` zusammengefasst:

```json
{
  "editorial": {
    "hint": "Achte auf die Inschrift über dem Eingang.",
    "question": "Aus welchem Jahr stammt das Gebäude?",
    "options": [
      "1890",
      "1920",
      "1950"
    ],
    "correctOptionIndex": 1,
    "info": "Das Gebäude wurde in der Zwischenkriegszeit errichtet."
  }
}
```

Alle Felder in `editorial` sind optional. Sobald ein Teil des Frageblocks
ausgefüllt wird, wird der gesamte Block gemeinsam validiert:

- `question` benötigt mindestens zwei `options`.
- `options` enthält zwei bis vier nichtleere Einträge.
- `correctOptionIndex` muss auf eine vorhandene Option zeigen.
- `hint` und `info` werden getrimmt und auf sinnvolle Maximallängen begrenzt.
- Ein leerer redaktioneller Abschnitt wird nicht übertragen.

## 2. Frontend in `index.html`

### 2.1 Formular erweitern

Datei: `index.html`

Im bestehenden Dialog `#suggest-dialog`:

1. Einen zusammenklappbaren Bereich „Weitere Informationen (optional)“
   ergänzen.
2. Folgende Felder anbieten:
   - Hinweis
   - Frage
   - zwei bis vier Antwortmöglichkeiten
   - Auswahl der richtigen Antwort
   - Hintergrundinformationen
3. Alle optionalen Felder mit verständlichen Labels und Hilfetexten versehen.
4. Den Abschnitt standardmäßig geschlossen lassen.
5. Die vorhandene Standortauswahl und Koordinatenfelder unverändert weiterverwenden.
6. Einen Hinweis ergänzen:

   > Optionale Angaben werden vor einer Veröffentlichung geprüft.

### 2.2 Barrierefreiheit

- Native `details`/`summary`-Semantik für den optionalen Bereich verwenden.
- `aria-expanded` und `aria-controls` nur ergänzend einsetzen, falls die
  gewählte Implementierung dies benötigt.
- Fehler über `aria-describedby` mit den jeweiligen Feldern verbinden.
- Bei Fehlern den Fokus auf das erste fehlerhafte Feld setzen.
- Die Zahl der Fehler über die bestehende Statusregion ankündigen.
- Antwortfelder müssen per Tab erreichbar sein.
- Die richtige Antwort darf nicht ausschließlich durch Farbe markiert werden.

### 2.3 Datenaufbereitung

Die vorhandene Funktion `readSuggestionForm()` wird um die optionalen Felder
erweitert. Die Funktion erzeugt weiterhin ein stabiles Objekt und lässt
unbelegte optionale Bereiche weg.

Die vorhandene Funktion `validateSuggestion()` wird in klar getrennte Schritte
aufgeteilt:

1. Pflichtfelder validieren.
2. Standort validieren.
3. Optionalen redaktionellen Block validieren.
4. Erst bei einem fehlerfreien Ergebnis das JSON-Objekt erzeugen.

`sendSuggestion(payload)` bleibt die einzige Schnittstelle für die spätere
Backend-Übertragung.

## 3. Backend und Datenbank

### 3.1 Bestehende Tabelle weiterverwenden

Die bestehende Tabelle `station_suggestions` bleibt die zentrale Eingangstabelle.
Es wird keine neue Submission-Tabelle eingeführt.

Dateien:

- `setup/schema.sql`
- gegebenenfalls einmaliges Migrationsskript oder lokale Datenbankmigration

### 3.2 Feldzuordnung

| Frontend | `station_suggestions` |
| --- | --- |
| `name` | `title` |
| `description` | `info` |
| `categoryId` | `category` |
| `location.latitude` | `lat` |
| `location.longitude` | `lon` |
| `editorial.hint` | `hint` |
| `editorial.question` | `question` |
| `editorial.options` | `options_json` |
| `editorial.correctOptionIndex` | `correct_option_index` |

### 3.3 Redaktionelle Felder optional machen

Die Felder, die ein Nutzer nicht zwingend ausfüllen muss, dürfen beim Eingang
leer sein:

- `hint`
- `question`
- `options_json`
- `correct_option_index`

`title`, `info`, `category`, `status` und `created_at` bleiben beim Eingang
erforderlich. Koordinaten sind optional, wenn der Nutzer keinen Standort angibt.

Die Datenbankmigration muss bestehende Vorschläge und bestehende Stationen
erhalten. Vor der Änderung ist eine Sicherung von `data/game.sqlite` anzulegen.

## 4. API-Anpassung

Datei: `api/submit_station.php`

### 4.1 Request

Der Endpoint soll zusätzlich den neuen Basis-/Erweiterungspayload akzeptieren.
Bestehende Requests mit dem bisherigen vollständigen Format dürfen nicht
unbeabsichtigt brechen.

Für die neue Variante:

1. JSON- oder Formulardaten einlesen.
2. `version` prüfen; zunächst nur Version `1` akzeptieren.
3. Pflichtfelder serverseitig validieren.
4. `categoryId` gegen die vorhandenen Kategorien prüfen.
5. Koordinaten nur akzeptieren, wenn beide vorhanden und geografisch gültig sind.
6. Den optionalen `editorial`-Block mit denselben Regeln wie im Frontend
   validieren.
7. Eingaben serverseitig trimmen und Längenlimits erzwingen.
8. Den Datensatz mit `status = 'pending'` speichern.
9. Keine direkte Veröffentlichung auslösen.

Clientseitige Validierung verbessert die Bedienung, ersetzt aber niemals die
serverseitige Validierung.

### 4.2 Response

Erfolg:

```json
{
  "data": {
    "received": true,
    "status": "pending"
  }
}
```

Fehler:

```json
{
  "data": null,
  "error": {
    "code": "validation_failed",
    "message": "Die Eingaben konnten nicht verarbeitet werden."
  }
}
```

Rate-Limiting, Honeypot, CSRF-/Origin-Prüfung soweit für den öffentlichen
Endpoint vorgesehen und die bestehende JSON-Fehlerstruktur bleiben erhalten.

## 5. Admin-Workflow

Betroffene Dateien:

- `admin/suggestions.php`
- `admin/station-edit.php`
- `admin/station-save.php`
- `admin/suggestion-action.php`

### 5.1 Vorschlagsliste

In der Übersicht sichtbar machen:

- Basisdaten
- Kategorie
- Standort
- ob optionale redaktionelle Angaben vorhanden sind
- Eingangsdatum

### 5.2 Bearbeitungsmaske

Die Bearbeitungsmaske zeigt Nutzerangaben als vorausgefüllte, aber vollständig
änderbare Werte. Leere redaktionelle Felder sind beim Öffnen erlaubt.

Vor der Freigabe müssen jedoch vorhanden und gültig sein:

- vollständiger Titel
- Hinweis
- Frage
- zwei bis vier Antwortoptionen
- gültiger Index der richtigen Antwort
- Lösungsinformationen gemäß bestehender Stationsvalidierung

### 5.3 Freigabe

Die bestehende Freigabelogik wird weiterverwendet:

1. Admin speichert oder korrigiert die Daten.
2. Server validiert alle Pflichtfelder.
3. Eine vollständige Station wird angelegt.
4. Der Vorschlag erhält `status = 'approved'`.
5. `reviewed_at` wird gesetzt.

Eine Freigabe mit unvollständigen redaktionellen Daten muss serverseitig
verhindert werden.

## 6. Sicherheits- und Datenschutzregeln

- Keine Veröffentlichung ohne Admin-Prüfung.
- Keine Annahme von `categoryId` ohne Abgleich mit der Kategorietabelle.
- Keine ungeprüften URLs oder HTML-Inhalte in Stationsdaten übernehmen.
- Eingaben escapen, bevor sie im Adminbereich ausgegeben werden.
- Rate-Limiting beibehalten.
- Keine zusätzlichen personenbezogenen Daten erfassen.
- Vorschläge nicht im Browser-`localStorage` speichern.
- Hochgeladene Dateien weiterhin über die vorhandene Upload-Validierung
  behandeln; optionale Bilder erst in einer späteren Ausbaustufe ergänzen.

## 7. Umsetzungsreihenfolge

1. Datenvertrag und Feldlängen verbindlich festlegen.
2. Datenbankmigration für optionale redaktionelle Felder vorbereiten.
3. Serverseitige Validierung und Speicherung implementieren.
4. Admin-Ansicht für unvollständige Vorschläge anpassen.
5. Frontend-Formular um den optionalen Abschnitt erweitern.
6. Gemeinsame Payload- und Validierungsfälle testen.
7. `sendSuggestion()` mit dem Backend verbinden.
8. Dokumentation in `README.md` und `Backend-Umsetzung.md` aktualisieren.
9. Datenbankmigration mit Sicherung und Testdaten ausführen.

## 8. Testfälle

### Erfolgreiche Fälle

- Nur Pflichtfelder ohne Standort.
- Pflichtfelder mit gültigem Standort.
- Pflichtfelder plus vollständiger redaktioneller Block.
- Dezimalkomma bei den Koordinaten.
- Nutzerangaben werden im Adminformular vorausgefüllt.
- Admin ergänzt fehlende Angaben und gibt den Vorschlag frei.

### Fehlerfälle

- Fehlender Name, Beschreibung oder Kategorie.
- Nur Breitengrad oder nur Längengrad.
- Koordinaten außerhalb des gültigen Bereichs.
- Frage ohne mindestens zwei Antworten.
- Leere Antwortoption.
- Ungültiger `correctOptionIndex`.
- Zu lange Eingaben.
- Unbekannte `categoryId`.
- Direkter Versuch, einen Vorschlag ohne Adminprüfung zu veröffentlichen.
- Überschreitung des Rate-Limits.

## Definition of Done

- Das einfache Formular funktioniert ohne Ausfüllen des optionalen Bereichs.
- Der optionale Bereich ist vollständig per Tastatur und Screenreader bedienbar.
- Frontend und Backend validieren denselben Datenvertrag.
- Basisvorschläge werden als `pending` gespeichert.
- Unvollständige Vorschläge können im Adminbereich bearbeitet werden.
- Eine Veröffentlichung ist nur mit vollständigen redaktionellen Daten möglich.
- Bestehende Vorschläge, Stationen und der aktuelle Spielbetrieb bleiben
  funktionsfähig.
- README und Backend-Dokumentation beschreiben das endgültige Format.
