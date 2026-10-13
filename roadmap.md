# Refactoring- und Feature-Roadmap für `index.html`

Diese Roadmap beschreibt die schrittweise Weiterentwicklung der Anwendung. Die Umsetzung erfolgt möglichst inkrementell, damit bestehende Karten-, Listen- und Stationsfunktionen während der Arbeiten erhalten bleiben.

## Ziele

- Unterstützung eines systemintegrierten und manuellen Dark Modes
- Verbesserung der Tastaturbedienung, Screenreader-Unterstützung und visuellen Zugänglichkeit
- Vorbereitung der UI-Logik auf zukünftige Animationen
- Möglichkeit, eigene Stationen strukturiert vorzuschlagen
- Erhalt der bestehenden Funktionalität und des aktuellen Nutzungserlebnisses

## Empfohlene Reihenfolge

1. **Bestandsaufnahme und technische Grundlage**
2. **Dark Mode**
3. **Barrierefreiheit**
4. **Trennung von Zustand, Rendering und Interaktionen**
5. **Animations-Hooks und CSS-Transitions**
6. **Stationen vorschlagen**
7. **Gesamtprüfung und Dokumentation**

Die Reihenfolge reduziert Abhängigkeiten: CSS-Variablen und eine klarere Zustandsstruktur werden zuerst geschaffen, bevor weitere UI-Komponenten darauf aufbauen.

---

## 1. Bestandsaufnahme und technische Grundlage

### Aufgaben

- Bestehende DOM-Struktur, Styles, Event-Listener und Datenhaltung in `index.html` dokumentieren.
- Interaktive Elemente und ihre aktuelle Bedienbarkeit erfassen:
  - Karten-Pins
  - Stationsliste und Listeneinträge
  - Filter
  - Detailansichten
  - vorhandene Buttons und Menüs
- Wiederkehrende Farben, Abstände und Schriftgrößen identifizieren.
- Prüfen, welche Bereiche bereits semantische HTML-Elemente oder ARIA-Attribute verwenden.
- Bestehende Browser- und Kartenabhängigkeiten bei allen Änderungen berücksichtigen.

### Ergebnis

- Eine belastbare Übersicht der bestehenden UI-Zustände und Interaktionen.
- Eine Liste der Stellen, an denen Zustandsänderungen und DOM-Rendering aktuell vermischt sind.
- Keine funktionalen Änderungen in diesem Arbeitsschritt.

---

## 2. Dark Mode: Systemintegration und manueller Toggle

### 2.1 CSS-Architektur

- Farben schrittweise auf CSS Custom Properties umstellen.
- Einen hellen Standardfarbsatz und einen kontrastreichen dunklen Farbsatz definieren.
- Die Darstellung über eine Klasse auf dem `html`-Element steuern, zum Beispiel:

  ```css
  :root {
    --color-background: ...;
    --color-surface: ...;
    --color-text: ...;
    --color-muted-text: ...;
    --color-accent: ...;
    --color-focus: ...;
  }

  :root.dark {
    --color-background: ...;
    --color-surface: ...;
    --color-text: ...;
    --color-muted-text: ...;
    --color-accent: ...;
    --color-focus: ...;
  }
  ```

- Kartenbezogene Farben und Overlays ebenfalls berücksichtigen.
- Ausreichende Kontraste für Light- und Dark-Mode sicherstellen.

### 2.2 Initialisierung und Speicherung

- Die Systemeinstellung über `window.matchMedia('(prefers-color-scheme: dark)')` auslesen.
- Beim Start die gespeicherte manuelle Präferenz aus `localStorage` verwenden.
- Falls keine manuelle Präferenz existiert, auf die Systemeinstellung zurückfallen.
- Fehler beim Zugriff auf `localStorage` dürfen die Anwendung nicht unbrauchbar machen; in diesem Fall wird nur die Systemeinstellung verwendet.
- Änderungen der Systemeinstellung während der Laufzeit berücksichtigen, solange keine manuelle Präferenz gesetzt ist.
- Eine Möglichkeit vorsehen, die manuelle Präferenz zurückzusetzen und wieder der Systemeinstellung zu folgen.

### 2.3 UI-Schalter

- Einen sichtbaren Toggle-Button in Header oder Menü ergänzen.
- Den Button mit einem aussagekräftigen `aria-label`, einem zugänglichen Namen und passenden Zustandsinformationen versehen.
- Icons für hellen und dunklen Modus verwenden, ohne die Information ausschließlich über Farbe zu vermitteln.
- Den aktuellen Modus nach jeder Änderung für die Oberfläche und assistive Technologien nachvollziehbar aktualisieren.

### Abnahmekriterien

- Der erste Seitenaufruf folgt der Systemeinstellung, sofern keine manuelle Auswahl gespeichert ist.
- Eine manuelle Auswahl bleibt nach dem Neuladen erhalten.
- Light- und Dark-Mode sind lesbar und weisen ausreichende Kontraste auf.
- Der Schalter ist per Tastatur erreichbar und bedienbar.
- Bestehende Karten- und Stationsfunktionen bleiben unverändert nutzbar.

---

## 3. Barrierefreiheit (A11y)

### 3.1 Tastaturbedienung und Fokus

- Alle interaktiven Elemente in einer sinnvollen Tab-Reihenfolge erreichbar machen.
- Karten-Pins als echte fokussierbare Bedienelemente ausgeben oder mit gleichwertiger Tastaturinteraktion ausstatten.
- Stationslisteneinträge vollständig per Tastatur bedienbar machen.
- Für `:focus-visible` einen gut erkennbaren Fokus-Ring definieren.
- Fokus nicht durch optische Zustände entfernen.
- Beim Öffnen und Schließen von Dialogen den Fokus sinnvoll setzen und anschließend zurückführen.

### 3.2 Semantik und Screenreader

- Semantische HTML-Elemente wie `button`, `nav`, `main`, `section`, `form` und Überschriften verwenden.
- Aussagekräftige zugängliche Namen für Icons, Karten-Pins und Aktionsschaltflächen ergänzen.
- Dynamische Statusänderungen über eine passende `aria-live`-Region ankündigen, zum Beispiel:
  - Anzahl der sichtbaren Stationen nach einem Filter
  - Laden oder Aktualisieren von Stationsdetails
  - Erfolg oder Fehler bei der Formularvalidierung
- ARIA nur ergänzend verwenden; native HTML-Semantik bevorzugen.
- Fehlermeldungen mit den jeweiligen Formularfeldern verknüpfen.

### 3.3 Kontrast und Skalierung

- Kontrast im Light- und Dark-Mode nach WCAG-Anforderungen prüfen.
- Nicht nur Farbe zur Kennzeichnung von Zuständen verwenden.
- Abstände und Schriftgrößen bevorzugt in `rem` oder `em` definieren.
- Layout bei Browser-Zoom und größeren Textgrößen testen.
- Große und gut erkennbare Karten-Pins beibehalten.

### Abnahmekriterien

- Die zentralen Nutzungspfade sind ausschließlich mit Tastatur ausführbar.
- Fokus ist jederzeit sichtbar.
- Status- und Fehlermeldungen werden von Screenreadern verständlich angekündigt.
- Die Stationsliste bleibt bei Zoom und vergrößerter Schrift nutzbar.
- Kontrastprüfungen bestehen für beide Farbmodi.

---

## 4. Architektur für Zustand, Rendering und Interaktionen

### Ziel

Datenänderungen, DOM-Rendering und Benutzerinteraktionen sollen klar getrennt werden. Dadurch können zukünftige Animationen ergänzt werden, ohne die Fachlogik zu verändern.

### Aufgaben

- Einen zentralen, kleinen UI-Zustand definieren, zum Beispiel für:
  - aktive Filter
  - ausgewählte Station
  - geöffnete Detailansicht
  - Farbmodus
  - Zustand des Vorschlagsformulars
- Funktionen nach Verantwortlichkeit trennen:
  - Zustandsänderung
  - Datenaufbereitung
  - Rendering
  - Event-Behandlung
- Gemeinsame Renderpfade für Karten-Pins, Stationsliste und Detailansichten identifizieren.
- DOM-Änderungen möglichst über gezielte Renderfunktionen durchführen.
- Bestehende Datenformate beibehalten oder nur mit klarer Migration erweitern.

### Lifecycle-Hooks

Für relevante DOM-Änderungen kleine Hooks oder Events vorsehen:

- `beforeRender`
- `afterRender`
- `beforeDetailsOpen`
- `afterDetailsOpen`
- `beforeFilterChange`
- `afterFilterChange`

Die Hooks sollen zunächst keine Animation erzwingen. Sie dienen als stabile Erweiterungspunkte für CSS-Transitions oder die Web Animations API.

---

## 5. Vorbereitung von Animationen

### CSS-Klassen

Wiederverwendbare Klassen im Stylesheet vorsehen, zum Beispiel:

- `.transition-fade`
- `.slide-enter`
- `.slide-exit`
- `.is-entering`
- `.is-exiting`

### Anforderungen

- Animationen dürfen die Bedienbarkeit nicht blockieren.
- `prefers-reduced-motion: reduce` berücksichtigen und Bewegungen dann minimieren oder deaktivieren.
- Statusänderungen nicht ausschließlich durch Animation vermitteln.
- Ein- und Ausblendvorgänge so strukturieren, dass der Fokus nicht verloren geht.
- Die Kernlogik soll auch ohne aktivierte Animationen korrekt funktionieren.

### Geeignete erste Einsatzpunkte

- Öffnen und Schließen von Stationsdetails
- Aktualisierung der gefilterten Stationsliste
- Öffnen und Schließen des Vorschlagsdialogs
- Wechsel zwischen Ansichten oder Seitenbereichen

### Abnahmekriterien

- Jede Animation kann über CSS oder Web Animations API ergänzt werden, ohne die Datenlogik umzubauen.
- Bei reduzierter Bewegung bleibt die vollständige Funktionalität erhalten.
- Es gibt keine Layout-Sprünge, die die Tastaturbedienung oder den Fokus beeinträchtigen.

---

## 6. Feature: Eigene Stationen vorschlagen

### 6.1 UI-Komponente

- Eine Schaltfläche **„Station vorschlagen“** ergänzen.
- Ein zugängliches Modal oder alternativ eine zugängliche Drawer-Ansicht öffnen.
- Beim Öffnen den Fokus in den Dialog setzen.
- Beim Schließen den Fokus auf die auslösende Schaltfläche zurückführen.
- Escape zum Schließen unterstützen.
- Einen sichtbaren Schließen-Button bereitstellen.
- Hintergrundinhalte während des Dialogs für assistive Technologien korrekt behandeln.

### 6.2 Formularfelder

Das Formular soll mindestens folgende Felder enthalten:

- Stationsname — Pflichtfeld
- Beschreibung oder Besonderheiten — Pflichtfeld
- Standort — optional
- Kategorie oder Typ — Pflichtfeld

Für den Standort sind zwei Eingabewege vorzusehen:

- Koordinaten per Eingabefeld
- Positionsübernahme durch Klick auf die Karte, sofern dies mit der bestehenden Kartenlogik vereinbar ist

### 6.3 Validierung

- Pflichtfelder clientseitig validieren.
- Eingaben trimmen und leere Werte ablehnen.
- Koordinaten auf gültige Zahlen und einen zulässigen geografischen Bereich prüfen.
- Fehler direkt am jeweiligen Feld anzeigen.
- Fehler mit `aria-describedby` oder einer gleichwertigen semantischen Verknüpfung ausgeben.
- Eine allgemeine Statusmeldung für Erfolg oder weitere Fehler bereitstellen.
- Keine ungültigen oder unvollständigen Datensätze als Erfolg behandeln.

### 6.4 Datenmodell und Verarbeitung

Die Eingaben in ein stabiles JSON-Objekt überführen, zum Beispiel:

```json
{
  "name": "Beispielstation",
  "description": "Beschreibung und Besonderheiten",
  "location": {
    "latitude": 51.9607,
    "longitude": 7.6261
  },
  "category": "Beispielkategorie"
}
```

- Optionale Standortdaten nur aufnehmen, wenn sie vollständig und gültig sind.
- Das Objekt zunächst für einen späteren Backend-, API- oder Export-Anschluss vorbereiten.
- Eine klare Schnittstelle zwischen Formular und zukünftiger Übertragung definieren.
- Bis zur Backend-Anbindung eine nachvollziehbare lokale Erfolgsbestätigung anzeigen.
- Keine sensiblen Daten lokal oder in externen Diensten speichern, sofern dies nicht ausdrücklich vorgesehen ist.

### Abnahmekriterien

- Der Dialog ist per Maus, Tastatur und Screenreader nutzbar.
- Ungültige Eingaben werden verständlich markiert.
- Gültige Eingaben erzeugen ein korrekt strukturiertes JSON-Objekt.
- Der Standort kann optional über die Karte oder Koordinaten erfasst werden.
- Das Formular kann ohne Backend-Anbindung getestet werden.

---

## 7. Qualitätssicherung

### Funktionale Tests

- Systemeinstellung hell/dunkel
- gespeicherte manuelle Theme-Präferenz
- Rückfall auf die Systemeinstellung
- Filterung und Aktualisierung der Stationsliste
- Öffnen und Schließen von Details
- Öffnen, Validieren und Absenden des Vorschlagsformulars
- Standortübernahme und Koordinatenvalidierung

### A11y-Tests

- Vollständiger Tastaturdurchlauf
- Sichtbarkeit des Fokus
- Screenreader-Prüfung der Hauptabläufe
- Kontrastprüfung in beiden Modi
- Zoom- und Textgrößenprüfung
- Prüfung mit reduzierter Bewegung

### Regressionstests

- Karte wird weiterhin korrekt geladen.
- Alle vorhandenen Pins bleiben sichtbar und bedienbar.
- Stationsdetails und Filter verhalten sich wie zuvor.
- Responsive Darstellung bleibt erhalten.
- Fehler in optionalen neuen Funktionen blockieren die bestehenden Funktionen nicht.

---

## 8. Abschluss und Dokumentation

- Änderungen an der Bedienung und an neuen UI-Elementen dokumentieren.
- Neue Datenfelder und das Stations-JSON-Format festhalten.
- Offene Entscheidungen für die Backend- oder API-Anbindung markieren.
- Browserunterstützung und bekannte Einschränkungen dokumentieren.
- Erst nach erfolgreicher Prüfung die Aufgaben als abgeschlossen betrachten.

## Definition of Done

Die Roadmap gilt für die jeweilige Ausbaustufe als umgesetzt, wenn:

- die Funktion im bestehenden `index.html` integriert ist,
- bestehende Nutzungspfade weiterhin funktionieren,
- Tastatur- und Screenreader-Bedienung berücksichtigt sind,
- Light- und Dark-Mode geprüft wurden,
- relevante Fehlerfälle sichtbar behandelt werden,
- und die Änderung mit den vorhandenen Browsern und Projektkonventionen vereinbar ist.
