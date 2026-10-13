# Report: „Münster unvermutet“

**Analysierte Datei:** [muenster-unvermutet-k1.html](/Users/lasarkolja/Code/Bootstrap6-Tests/muenster-unvermutet-k1.html)

## 1. Kurzfazit

„Münster unvermutet“ ist eine browserbasierte Foto-Schnitzeljagd durch Münster. Die Anwendung kombiniert eine OpenStreetMap-Karte mit 14 lokal gespeicherten Foto-Rätseln zu historischen, kulturellen und ungewöhnlichen Orten der Stadt.

Die technische Umsetzung ist für ein einzelnes HTML-Dokument erstaunlich vollständig: Es gibt eine interaktive Karte, Fortschrittsanzeige, Aufgabenansicht, Antwortprüfung, Hinweise, Lösungstexte, externe Kartenlinks und eine lokale Speicherung des Spielfortschritts.

**Gesamtbewertung: 7,8 von 10 Punkten**

Besonders gelungen sind die klare Nutzerführung, die durchgängige Gestaltung und die Verbindung von Stadtbesichtigung mit historischen Informationen. Schwächen liegen vor allem in der Wartbarkeit des monolithischen Dokuments, in der starken Abhängigkeit von externen Diensten und in einigen ungenutzten beziehungsweise unvollständigen Codebestandteilen.

## 2. Spielprinzip

### Grundidee

Die Anwendung ist eine digitale Stadtrallye beziehungsweise Foto-Schnitzeljagd:

1. Auf der Startseite wird eine Karte von Münster angezeigt.
2. Auf der Karte befinden sich 14 nummerierte Stationen.
3. Der Spieler wählt eine Station über einen Karten-Pin aus.
4. Zu jeder Station wird ein lokales Aufgabenfoto gezeigt.
5. Das Foto zeigt ein Detail eines Gebäudes, Denkmals, Reliefs oder einer Inschrift.
6. Der Spieler sucht den Ort vor Ort oder nutzt die angegebenen Koordinaten.
7. Anschließend beantwortet er eine Multiple-Choice-Frage.
8. Bei richtiger Antwort oder über „Lösung anzeigen“ wird die Station als gelöst markiert.
9. Danach werden Lösungsfoto, Ortsname, Hintergrundinformationen und weiterführende Links angezeigt.
10. Der Fortschritt wird gespeichert und auf der Karte durch ein Häkchen gekennzeichnet.

Die Stationsdaten sind zentral im Array `STATIONS` organisiert und enthalten jeweils Titel, Fotos, Koordinaten, Hinweise, Antwortmöglichkeiten, Lösung und Hintergrundtext ([muenster-unvermutet-k1.html:452](/Users/lasarkolja/Code/Bootstrap6-Tests/muenster-unvermutet-k1.html:452)).

### Stationen

Die 14 Stationen behandeln unter anderem:

- Jan van Leiden an der Kreuzigungsgruppe am Dom
- Schleifkerben im Sandstein
- den Kranich am Dom
- das Haus der Niederlande
- den Ägidii-Inschriftenstein
- das Bispingkreuz
- den Lortzingsaal
- die Jakobsmuschel an St. Lamberti
- die Kinderhand an der Ludgerikirche
- ein Johannes-Nepomuk-Denkmal
- den Friedensreiter
- den „Lesenden“
- den münsterschen Bierkrieg
- den Club „Der Elefant“

### Fortschritt und Motivation

Der Fortschritt wird prominent als „0 von 14 gelöst“ und als grüner Balken dargestellt. Gelöste Stationen erhalten ein Häkchen auf dem Karten-Pin, den Status „gelöst“, das Lösungsfoto sowie Hintergrundinformationen. Bei vollständiger Lösung erscheint ein Gewinnhinweis. Die Speicherung erfolgt über `localStorage` im Browser ([muenster-unvermutet-k1.html:909](/Users/lasarkolja/Code/Bootstrap6-Tests/muenster-unvermutet-k1.html:909)).

### Bewertung des Spielprinzips

#### Stärken

- Die Karte eignet sich sehr gut als zentrale Spieloberfläche.
- Die Aufgaben motivieren dazu, die Stadt tatsächlich zu erkunden.
- Foto-Detail und Ortsgeschichte ergänzen sich sinnvoll.
- Die Multiple-Choice-Fragen machen die Lösung niedrigschwellig.
- Die umfangreichen Lösungstexte erzeugen einen deutlichen Lern- und Entdeckungseffekt.
- Hinweise verhindern, dass Spieler vollständig steckenbleiben.
- Die Möglichkeit, eine Lösung direkt anzuzeigen, macht das Spiel auch für weniger geduldige Nutzer zugänglich.

#### Mögliche Schwächen

- Die korrekte Antwort kann ohne tatsächlichen Besuch des Ortes mit Hilfe der Koordinaten und Suchmaschinen ermittelt werden.
- Es gibt keine echte Standortprüfung oder GPS-basierte Aufgabenlogik.
- Die Lösung kann jederzeit sofort angezeigt werden. Das ist benutzerfreundlich, reduziert aber den Rätselcharakter.
- Alle Stationen sind von Anfang an sichtbar. Ein freischaltbares Fortschrittssystem oder eine empfohlene Reihenfolge könnte die Dramaturgie verstärken.
- Die Aufgaben sind aktuell ausschließlich Multiple-Choice-Aufgaben, obwohl der Code grundsätzlich auch freie Texteingaben unterstützt.

## 3. Bewertung des Codes

### 3.1 Architektur und Struktur

Die Anwendung besteht aus einer einzigen HTML-Datei mit HTML-Struktur, CSS, Stationsdaten, Kartenlogik, Rendering, Antwortprüfung und Speicherung. Die Oberfläche wird teilweise über HTML-Strings in `cardHTML()` erzeugt ([muenster-unvermutet-k1.html:826](/Users/lasarkolja/Code/Bootstrap6-Tests/muenster-unvermutet-k1.html:826)).

#### Positiv

- Für ein kleines, lokal verteilbares Spiel ist eine einzelne HTML-Datei praktisch.
- Die Stationsdaten sind relativ übersichtlich und konsistent aufgebaut.
- Die Funktionen sind nach Verantwortlichkeiten getrennt: Karteninitialisierung, Rendering, Antwortprüfung, Hinweisverwaltung und Fortschrittsspeicherung.
- Neue Stationen lassen sich grundsätzlich durch einen weiteren Eintrag im `STATIONS`-Array hinzufügen.

#### Negativ

- HTML, CSS, Daten und JavaScript sind vollständig vermischt.
- Globale Variablen wie `STATIONS`, `solved`, `osmMap` und `picks` erschweren spätere Erweiterungen.
- Inline-Eventhandler wie `onclick="openTask(...)"` koppeln Daten, Markup und Logik eng miteinander.
- Das manuelle Zusammenbauen langer HTML-Strings ist fehleranfällig und schwer lesbar.
- Es gibt keine Komponentenstruktur und keine Module.
- Die Datei ist mit fast 1.000 Zeilen bereits relativ groß und würde bei weiteren Funktionen schnell unübersichtlich.

**Bewertung Architektur: 7/10**

Für ein eigenständiges Mini-Projekt ist die Lösung angemessen. Für eine langfristige Weiterentwicklung sollte die Datei in mindestens `index.html`, `styles.css`, `stations.js` und `app.js` aufgeteilt werden.

### 3.2 Funktionalität

Die zentrale Funktionalität ist gut umgesetzt:

- Karteninitialisierung mit Leaflet
- Marker für alle Stationen
- automatische Kartenausrichtung über die Stationskoordinaten
- Aufgabenansicht
- Fortschrittsbalken
- Antwortauswertung
- Lösungserkennung
- Speicherung im Browser
- Zurücksetzen des Fortschritts

Die Kartenlogik befindet sich im Bereich der Funktionen `initMap()` und `mapFallback()` ([muenster-unvermutet-k1.html:747](/Users/lasarkolja/Code/Bootstrap6-Tests/muenster-unvermutet-k1.html:747)).

Die Antwortprüfung der Multiple-Choice-Aufgaben erfolgt über die gespeicherten Indexwerte `richtig` und die Funktionen `pick()` beziehungsweise `checkMulti()` ([muenster-unvermutet-k1.html:929](/Users/lasarkolja/Code/Bootstrap6-Tests/muenster-unvermutet-k1.html:929)).

#### Auffälligkeiten und Verbesserungspotenzial

1. **Externe Abhängigkeiten:** Leaflet und die OpenStreetMap-Kacheln werden über externe URLs geladen ([muenster-unvermutet-k1.html:423](/Users/lasarkolja/Code/Bootstrap6-Tests/muenster-unvermutet-k1.html:423)). Ohne Internetverbindung funktioniert die Karte nicht vollständig.

2. **Unvollständiger Karten-Fallback:** Der Fallback reagiert primär darauf, ob Leaflet geladen wurde. Wenn Leaflet zwar geladen wird, die Kartenkacheln aber nicht erreichbar sind, erscheint möglicherweise eine leere Kartenfläche.

3. **Ungenutzte Variablen:** Mehrere Bestandteile sind vorbereitet, aber derzeit nicht aktiv, darunter `TEXT3_FOTO`, `gRoute`, `.navbox` und teilweise `photoLink`.

4. **Freie Texteingabe ist aktuell praktisch ungenutzt:** Die Funktionen `check()` und `isCorrect()` unterstützen freie Antworten. Alle derzeit sichtbaren Stationen besitzen jedoch `optionen`.

5. **Antwortprüfung ist tolerant bis teilweise großzügig:** `isCorrect()` akzeptiert auch Teilstrings. Das ist bei freien Eingaben komfortabel, kann aber zu unerwarteten korrekten Antworten führen.

6. **Fehlerbehandlung:** Fehler beim Zugriff auf `localStorage` werden still ignoriert ([muenster-unvermutet-k1.html:810](/Users/lasarkolja/Code/Bootstrap6-Tests/muenster-unvermutet-k1.html:810)). Für ein robustes Produkt wäre zumindest eine sichtbare Rückmeldung sinnvoll, wenn der Fortschritt nicht gespeichert werden kann.

7. **Keine automatisierten Tests:** Es gibt keine erkennbaren Tests für Antwortprüfung, Fortschrittsspeicherung, Rendering, Karteninitialisierung oder Reset-Funktion.

**Bewertung Funktionalität: 8/10**

### 3.3 Sicherheit

Der Code verwendet HTML-String-Konstruktion, schützt aber die meisten redaktionellen Texte mit `esc()` ([muenster-unvermutet-k1.html:823](/Users/lasarkolja/Code/Bootstrap6-Tests/muenster-unvermutet-k1.html:823)).

#### Positiv

- Nutzerantworten werden nicht direkt in das DOM zurückgeschrieben.
- Stationstexte werden überwiegend escaped.
- Externe Links verwenden `rel="noreferrer"`.

#### Einschränkung

Einige Werte, beispielsweise Bildnamen und URLs, werden direkt in HTML-Strings eingesetzt. Das ist aktuell vertretbar, weil die Daten fest im Quellcode stehen. Sobald Stationsdaten aus externen Quellen oder Eingabemasken kommen, müsste die Ausgabe konsequenter abgesichert werden.

**Bewertung Sicherheit im aktuellen Einsatz: 7/10**

### 3.4 Wartbarkeit

Die Wartbarkeit ist der schwächste technische Bereich.

#### Probleme

- Keine Trennung von Daten und Programmcode
- viele globale Variablen
- Inline-CSS direkt im erzeugten HTML
- wiederholte Dateinamenvariablen für jede Station
- gemischte deutsche und englische Benennung, beispielsweise `loesungPhoto`, `photoQuelle`, `currentTask` und `osmMarkers`
- Kommentare, die teilweise auf frühere Zustände oder zukünftige Vorhaben verweisen

#### Positiv

- Die Stationsobjekte folgen weitgehend einem einheitlichen Schema.
- Funktionsnamen sind verständlich.
- Der Ablauf der Anwendung ist auch ohne Framework nachvollziehbar.

**Bewertung Wartbarkeit: 6/10**

### 3.5 Barrierefreiheit und Bedienbarkeit

Die Bedienung ist grundsätzlich einfach: Es gibt große Buttons, klare Karten-Pins, eine gut sichtbare Fortschrittsanzeige, eine verständliche Rücknavigation und Tastaturunterstützung für die Texteingabe über Enter.

Es fehlen jedoch einige wichtige Details:

- keine sichtbaren `label`-Elemente für Formulareingaben
- keine expliziten ARIA-Beschriftungen für zentrale Bereiche
- Karten-Pins sind visuell verständlich, aber nicht optimal für Screenreader
- Fehlertexte werden nicht über `aria-live` angekündigt
- Statusänderungen des Fortschritts sind nicht speziell für assistive Technologien ausgezeichnet
- Farbkontraste sollten insbesondere bei hellgrauen Texten geprüft werden

**Bewertung Barrierefreiheit: 6/10**

## 4. Design und visuelle Gestaltung

### 4.1 Farbkonzept

Das Design verwendet eine warme, zurückhaltende Farbpalette:

| Funktion        | Farbe / Beispiel     | Wirkung                                      |
| --------------- | -------------------- | -------------------------------------------- |
| Hintergrund     | `#f5f5f4`            | heller, steinartiger Neutralton              |
| Kartenflächen   | `#ffffff`            | Klarheit und Lesbarkeit                      |
| Haupttext       | `#292524`, `#1c1917` | dunkles Braun-Schwarz statt hartem Schwarz   |
| Primärfarbe     | `#047857`            | zentrale Interaktions- und Fortschrittsfarbe |
| Dunkler Grünton | `#065f46`            | Hover- und Hervorhebungszustand              |
| Hinweisbox      | `#fffbeb`, `#fde68a` | warmer Hinweischarakter                      |
| Fehlerfarbe     | `#dc2626`            | klassische rote Fehlermeldung                |
| Sekundärtext    | `#78716c`, `#a8a29e` | dezente Metadaten und Nebeninformationen     |

Die Farbwahl passt thematisch gut zu einer Stadtrallye im historischen Umfeld. Das Grün vermittelt Orientierung und Natur, während die sand- und steinfarbenen Neutralwerte die Architektur und historischen Materialien der beschriebenen Orte aufgreifen.

### 4.2 Layout

Das Layout ist bewusst kompakt gehalten:

- maximale Breite von 720 Pixeln
- zentrierter Inhaltsbereich
- großzügige Innenabstände
- Karte und Aufgaben als eigenständige Blöcke
- vertikale Anordnung auf mobilen Geräten

Die zentrale Gestaltung basiert auf Kartenflächen mit weißem Hintergrund, dünnem grauem Rahmen, abgerundeten Ecken und dezenten Schatten. Dadurch wirkt die Anwendung modern, ruhig und übersichtlich.

### 4.3 Typografie

Verwendet wird eine systemnahe Sans-Serif-Schrift:

```css
font-family:
  system-ui,
  -apple-system,
  'Segoe UI',
  sans-serif;
```

Das ist eine gute Entscheidung für eine webbasierte Anwendung: Die Schrift wird schnell dargestellt, ist auf unterschiedlichen Geräten gut lesbar und benötigt keine zusätzliche Schriftdatei.

Die typografische Hierarchie ist klar:

- große Überschrift
- kleinere Beschreibung
- mittelgroße Stationstitel
- kompakte Metadaten
- hervorgehobene Buttons und Statusanzeigen

### 4.4 Karten- und Buttondesign

Die Karten-Pins sind grüne runde Marker mit weißer Nummer. Nach dem Lösen wechseln sie zu einem weißen Marker mit grünem Häkchen. Das ist eine sehr verständliche visuelle Zustandsanzeige.

Die Buttons verwenden grüne Primärbuttons für Aktionen, weiße Ghost-Buttons für sekundäre Aktionen, gelbe Hinweisboxen und rote Fehlermeldungen. Die Zustände `hover`, `selected` und `done` sind erkennbar ausgearbeitet.

### 4.5 Bildgestaltung

Aufgaben- und Lösungsbilder werden innerhalb einer begrenzten Höhe von maximal 320 Pixeln dargestellt. Durch `object-fit: contain` bleiben die Bildproportionen erhalten. Das ist für Foto-Rätsel sinnvoll, weil keine wichtigen Bildbereiche abgeschnitten werden und unterschiedliche Bildformate funktionieren.

Der Nachteil ist, dass bei Bildern mit stark unterschiedlichen Seitenverhältnissen unterschiedlich große freie Flächen entstehen können.

### 4.6 Designbewertung

#### Stärken

- harmonische, thematisch passende Farbpalette
- klare visuelle Hierarchie
- moderne Kartenoptik
- gute mobile Grundstruktur
- verständliche Statusdarstellung
- ruhiges und nicht überladenes Erscheinungsbild

#### Verbesserungspotenzial

- Die Karte nimmt mit 520 Pixeln relativ viel vertikalen Raum ein.
- Die Startseite könnte mehr spielerische Einführung oder Anleitung enthalten.
- Die Stationen könnten zusätzlich mit unterschiedlichen Symbolen oder Kategorien versehen werden.
- Die Gewinnansicht könnte stärker ausgearbeitet werden.
- Für den Outdoor-Einsatz wären größere Touch-Ziele und stärkere Kontraste hilfreich.

**Bewertung visuelles Design: 8,5/10**

## 5. Zusammenfassende Bewertung

| Bereich          |  Bewertung |
| ---------------- | ---------: |
| Spielidee        |     8,5/10 |
| Nutzerführung    |       8/10 |
| Funktionalität   |       8/10 |
| Visuelles Design |     8,5/10 |
| Code-Struktur    |       7/10 |
| Wartbarkeit      |       6/10 |
| Robustheit       |     6,5/10 |
| Barrierefreiheit |       6/10 |
| Erweiterbarkeit  |       6/10 |
| **Gesamt**       | **7,8/10** |

## 6. Wichtigste Stärken

1. Sehr passende Verbindung aus Stadtbesichtigung, Fotografie und Lokalgeschichte.
2. Klarer Ablauf von Karte über Aufgabe bis zur Lösung.
3. Gute optische Gestaltung mit konsistentem Farbsystem.
4. Sinnvoller Einsatz von Fortschrittsspeicherung und visuellen Zuständen.
5. Umfangreiche und inhaltlich interessante Informationen zu den Stationen.
6. Keine Framework-Abhängigkeit außer Leaflet; dadurch einfach lokal startbar.

## 7. Wichtigste Verbesserungen

1. HTML, CSS, Daten und JavaScript in getrennte Dateien aufteilen.
2. Ungenutzte Variablen und CSS-Klassen entfernen oder fertig implementieren.
3. Eine robustere Offline- beziehungsweise Kartenfehlerbehandlung ergänzen.
4. Zugänglichkeit verbessern: Labels, ARIA-Attribute, Tastaturbedienung und Statusmeldungen.
5. Antwortprüfung bei Freitexten weniger großzügig und eindeutiger gestalten.
6. Automatisierte Tests für Antworten, Speicherung und Fortschritt ergänzen.
7. Den Einstieg in das Spiel mit einer kurzen Anleitung und Spielregeln verbessern.
8. Optional eine Reihenfolge, Freischaltungen oder GPS-basierte Verifikation einführen.

## 8. Schlussurteil

Das Projekt ist als interaktive lokale Stadtrallye bereits gut nutzbar und besitzt ein überzeugendes Verhältnis aus Informationsgehalt, Rätselcharakter und Bedienkomfort.

Der größte Nachholbedarf liegt nicht in der Grundidee oder der visuellen Gestaltung, sondern in der technischen Struktur und langfristigen Wartbarkeit. Für einen privaten oder prototypischen Einsatz ist die aktuelle Umsetzung gut geeignet. Für eine Veröffentlichung mit vielen Nutzern wären Modularisierung, Tests, Barrierefreiheit und eine robustere Offline-Strategie die wichtigsten nächsten Schritte.

## Refactoring
Rolle: Du bist ein erfahrener Frontend-Entwickler, spezialisiert auf Clean Code und Barrierefreiheit.

Aufgabe: Refactore den folgenden Code, um die Wartbarkeit für zukünftige LLM-Entwicklungen zu maximieren. 

Halte dich strikt an folgende Vorgaben:

1. Architektur & Dateistruktur:
- Belasse den gesamten Code (HTML, CSS, JS) in dieser EINEN HTML-Datei.
- Gehe davon aus, dass eine Internetverbindung besteht (externe Skripte dürfen geladen bleiben/werden).
- Bereite die Javascript-Datenstruktur so vor, dass sie zukünftig problemlos mit LocalStorage erweitert werden kann, um Fortschritt und Antworten zu speichern.

2. Bereinigung & Code-Qualität:
- Entferne ungenutzte Variablen, Funktionen und CSS-Klassen.
- Entferne alle Elemente und Logiken, die zu den veralteten "freien Texteingaben" gehören.
- Benenne Variablen, Funktionen und CSS-Klassen konsistent, selbsterklärend und auf [Deutsch/Englisch - *deine Präferenz eintragen*].
- Optimiere die Lesbarkeit (klare Strukturierung, logische Reihenfolge, keine Redundanzen).

3. Barrierefreiheit (Quick Wins):
- Integriere leicht umsetzbare Accessibility-Features: Korrekte <label>-Verknüpfungen, grundlegende ARIA-Attribute (wo nötig), vollständige Tastaturbedienbarkeit (Tab-Index, Fokus-Sichtbarkeit) und semantische Statusmeldungen.

4. Design & Rest-Funktionalität:
- Abgesehen von den entfernten Texteingaben und den Barrierefreiheits-Optimierungen müssen das visuelle Design, das Layout und die Kernfunktionalität absolut unverändert bleiben.
