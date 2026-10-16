# Design-Implementierungsplan – Münster unvermutet

## Ziel

Die mobile Bedienung der Foto-Schnitzeljagd verbessern, ohne bestehende
Spielregeln, Barrierefreiheit oder Backend-Verhalten zu brechen. Die Karte soll
die Orientierung unterstützen; jede Kartenaktion muss weiterhin eine
gleichwertige, verständliche Alternative außerhalb der Karte haben.

Dieser Plan baut auf dem vorhandenen Stand in [`index.html`](./index.html) auf.
Er ersetzt das ursprüngliche Designbriefing nicht rückwirkend, sondern
unterscheidet ausdrücklich zwischen bereits vorhandenen Funktionen und
geplanten Änderungen.

## Bestand, der erhalten bleibt

Folgende Funktionen sind bereits vorhanden und werden nicht als neue Features
eingeplant:

- Fortschrittsbalken mit gelöster Anzahl sowie Häkchen für gelöste Stationen
- Abschlussansicht mit Statistik und den Aktionen „Zur Karte“ und „Nochmal spielen“
- große Antwortflächen, Falschantwort-Feedback und animierte Hinweisanzeige
- Stationsliste als Alternative zur Karte, Kategorie-Filter und Tastaturbedienung
- Auswahl eines Standorts per Kartenklick im Formular „Station vorschlagen“;
  Koordinaten lassen sich zusätzlich manuell eingeben
- Unterstützung für Dark Mode, Fokusdarstellung und `prefers-reduced-motion`
- Lade-, Fehler- und Wiederholungszustände für Stationsdaten

Diese Bestandteile sollen bei visuellen Anpassungen weiter funktionieren. Ein
großflächiger Austausch der bestehenden Oberfläche oder eine Änderung des
Spielablaufs ist nicht Ziel dieses Plans.

## Prioritäten und Umsetzung

### Phase 1 – Mobile Bedienbarkeit und Karten-Fallback

**Ziel:** Die Kernaufgaben lassen sich auf kleinen Displays gut bedienen; bei
fehlender Karte bleibt die App verständlich und es gibt einen erreichbaren
nächsten Schritt.

**Umsetzung**

1. Auf schmalen Viewports Kopfbereich, Fortschritt, Karte, Filter und
   Stationsliste prüfen und Abstände sowie Umbrüche gezielt verbessern.
2. Touch-Ziele für zentrale Aktionen ausreichend groß halten (mindestens
   44 × 44 CSS-Pixel); Text und Bedienelemente müssen auch bei vergrößerter
   Schrift nutzbar bleiben.
3. Den Karten-Fallback als klar abgegrenzten, kontrastreichen Status gestalten.
   Erklären, ob die Karte oder auch Stationsdaten nicht verfügbar sind, und
   eine passende Aktion anbieten:
   - Wenn Stationsliste und Daten verfügbar sind: zur Stationsliste springen
     und deren Nutzung erklären.
   - Wenn die Karte nur vorübergehend nicht verfügbar ist: eine erneute
     Karteninitialisierung nur anbieten, wenn sie technisch zuverlässig
     implementiert und getestet ist.
   - Bei fehlenden Stationsdaten den vorhandenen Fehler- und
     Wiederholungszustand verwenden.
4. Keinen Download-CTA anzeigen, solange kein tatsächlich bereitgestelltes
   Download-Paket existiert. Ein Link darf nur auf eine reale, unterstützte
   Funktion führen.
5. Den Fallback nicht nur für den Fall fehlender Leaflet-Bibliothek prüfen,
   sondern auch das Verhalten bei nicht ladenden Kartenkacheln untersuchen.
   Kartenkachel-Fehler und Fehler beim Laden der Spieldaten sind unterschiedliche
   Zustände und dürfen nicht verwechselt werden.

**Abnahmekriterien**

- Bei kleinen Smartphone-Viewports ist kein horizontaler Seiten-Scroll nötig.
- Karte, Stationsliste, Filter und primäre Aktionen bleiben erreichbar und
  beschriftet.
- Bei fehlender Kartenbibliothek oder fehlenden Kacheln wird ein verständlicher
  Zustand statt einer leeren Kartenfläche angezeigt.
- Wenn die Stationsliste nutzbar ist, können Stationen auch ohne Karte geöffnet
  werden; andernfalls wird kein funktionsloser CTA angezeigt.
- Ladefehler für Kategorien/Stationen bleiben unterscheidbar vom Kartenfehler.

### Phase 2 – Mobile Stationsansicht verbessern

**Ziel:** Den Wechsel zwischen Karte und Stationsaufgabe auf dem Smartphone
vereinfachen, ohne Inhalte nur durch Gesten zugänglich zu machen.

**Umsetzung**

1. Zunächst die bestehende Stationsansicht auf Smartphone, Tablet und Desktop
   testen und dokumentieren, wo Scrollen oder Zurücknavigation tatsächlich
   stören.
2. Falls die Tests einen klaren Vorteil zeigen, eine mobile
   Bottom-Sheet-Darstellung als Präsentationsvariante der bestehenden
   Stationsansicht prototypisieren. Die Desktopansicht bleibt unverändert.
3. Öffnen, Schließen und Erweitern des Sheets müssen zusätzlich über sichtbare
   Buttons bedienbar sein. Wischen darf eine optionale Ergänzung sein, nie die
   einzige Möglichkeit, Foto, Hinweis, Antwortoptionen oder Lösung zu erreichen.
4. Beim Öffnen Fokus sinnvoll in die Aufgabenansicht führen; beim Zurückkehren
   Fokus zum auslösenden Pin bzw. Listeneintrag zurückgeben. Browser-Zurück,
   Direktlinks und bestehende Stationsnavigation müssen erhalten bleiben.
5. Standortfreigabe und gesperrte Antworten unverändert behandeln. Die
   Präsentation darf keine geschützten Inhalte früher anzeigen.

**Abnahmekriterien**

- Alle Stationsinhalte sind per Touch, Tastatur und Screenreader erreichbar.
- Es gibt sichtbare Bedienelemente für Öffnen, Erweitern und Schließen.
- `Escape`, Browser-Zurück und Stationslinks verhalten sich nachvollziehbar.
- Das Sheet verdeckt keine erforderlichen Informationen dauerhaft und verursacht
  keinen Fokusverlust.
- Ohne nachweisbaren Bedienungsvorteil wird die bestehende Stationsansicht
  beibehalten; das Bottom Sheet ist kein Selbstzweck.

### Phase 3 – Visuelle Hierarchie und gezielte Micro-Interaktionen

**Ziel:** Das Feedback präzisieren und das Erscheinungsbild vereinheitlichen,
ohne dauernde Bewegung oder unnötige Effekte einzuführen.

**Umsetzung**

1. Vorhandene Farben, Systemschrift, Kontrastwerte und Komponenten als Basis
   weiterverwenden. Neue Farben nur ergänzen, wenn ein konkreter Status oder
   Kontrastbedarf besteht; Kategorie-Farben nicht mit Erfolgsfarben verwechseln.
2. Interaktionen nur dort animieren, wo sie Zustandsänderungen verständlicher
   machen: etwa Pin-Auswahl, Lösung und Fortschrittsänderung.
3. Keine pauschale Regel `transition: all` verwenden. Nur benötigte Eigenschaften
   wie `opacity`, `transform` oder `width` animieren, um unerwartete Effekte und
   Layoutverschiebungen zu vermeiden.
4. Keine permanent pulsierenden Pins, kein Ausblenden unbeteiligter Pins und
   keine Blur-Freigabe für Hinweise einführen. Diese Effekte können im Freien
   ablenken oder Lesbarkeit und Orientierung verschlechtern.
5. Erfolgsfeedback kurz und zurückhaltend halten. Konfetti ist optional und
   wird nur nach Nutzertest ergänzt; es darf keine Bedienung blockieren.
6. Alle Bewegungen bei `prefers-reduced-motion: reduce` entfernen oder deutlich
   minimieren. Statusinformationen müssen zusätzlich ohne Farbe und Animation
   verständlich bleiben.

**Abnahmekriterien**

- Bewegungen vermitteln eine konkrete Zustandsänderung und lenken nicht dauerhaft
  von Karte oder Aufgabe ab.
- Bei reduzierter Bewegung bleiben alle Zustände und Rückmeldungen verständlich.
- Hover-Stile sind keine Voraussetzung für Touch-Bedienung.
- Kontrast, sichtbarer Fokus und Statuskennzeichnung bleiben in Light Mode,
  Dark Mode und Forced Colors erhalten.

### Phase 4 – Vorschlagsformular nur bei belegtem Bedarf ändern

**Ziel:** Das Formular für „Station vorschlagen“ vereinfachen, ohne bewährte
Eingabemöglichkeiten zu entfernen.

**Umsetzung**

1. Auf einem Smartphone prüfen, ob das vorhandene Formular mit Pflichtangaben,
   optionalen redaktionellen Angaben und Standortauswahl tatsächlich zu lang
   oder fehleranfällig ist.
2. Falls Tests ein Problem bestätigen, zunächst Feldgruppen, Beschriftungen,
   Hilfetexte und Abstände verbessern.
3. Einen mehrstufigen Wizard nur bei nachgewiesenem Nutzen einführen. Er muss
   Vor-/Zurück-Navigation, sichtbaren Schrittstatus, Fehlerzusammenfassung und
   Erhalt bereits eingegebener Werte unterstützen.
4. Manuelle Koordinateneingabe beibehalten. Die Kartenauswahl ist eine
   zusätzliche Eingabemethode, kein Ersatz – insbesondere für Tastaturbedienung
   und Situationen, in denen die Karte nicht verfügbar ist.
5. Kategorie-Chips nur bei einer überschaubaren Kategorienanzahl und nach
   Prüfung von Tastaturbedienung, Screenreader-Ausgabe und Platzbedarf erwägen.

**Abnahmekriterien**

- Pflichtfeldvalidierung und bestehender JSON-Vertrag zum Backend bleiben
  unverändert.
- Koordinaten können weiterhin manuell eingegeben oder auf der Karte gewählt
  werden.
- Ein Wizard verliert beim Schrittwechsel keine Eingaben und zeigt Fehler am
  passenden Feld an.
- Eine Umstellung auf Chips verbessert die Auswahl nachweisbar und ist ohne Maus
  vollständig bedienbar; andernfalls bleibt das Dropdown.

## Nicht Bestandteil dieses Plans

- Eine dauerhafte Vollbildkarte mit schwebenden Overlays als Ersatz der
  bestehenden Seitenstruktur
- Entfernen der Stationsliste, manuellen Koordinateneingabe oder Tastaturpfade
- Erfinden eines Downloads, Offline-Modus oder einer neuen Backend-Funktion
- Änderung der Spielregeln, Standortprüfung, Antwortfreigabe oder
  Fortschrittsspeicherung
- Neue Animationen allein aus dekorativen Gründen

## Gemeinsame Qualitätssicherung

Nach jeder Phase die betroffenen Ansichten mindestens in schmaler und breiter
Viewportgröße prüfen. Zusätzlich testen:

- Tastaturbedienung inklusive Fokusreihenfolge und Fokus-Rückgabe
- Screenreader-Beschriftungen, Statusmeldungen und Formularfehler
- Dark Mode, Forced Colors und reduzierte Bewegung
- langsame oder fehlgeschlagene Netzwerkanfragen für API, Leaflet und
  Kartenkacheln
- Stationslinks, Browser-Zurück, Filter, Fortschritt und Wiederholungsaktionen
- Verhalten mit und ohne aktive Standortprüfung

## Empfohlene Reihenfolge

1. Phase 1 umsetzen, weil sie reale Fehlerzustände und die Nutzung im Freien
   adressiert.
2. Phase 2 als kleinen Prototyp testen und nur bei messbarer Verbesserung
   übernehmen.
3. Phase 3 gemeinsam mit den umgesetzten Oberflächen gezielt verfeinern.
4. Phase 4 erst nach kurzer Beobachtung oder Nutzertests des bestehenden
   Formulars entscheiden.

Für jede Phase vor der Umsetzung die passenden Selektoren und bestehenden
Event-Handler in `index.html` prüfen. Änderungen sollen die aktuelle
DOM-Struktur, UI-Optionen und Backend-Verträge wiederverwenden und nicht durch
parallele, unverbundene Implementierungen umgehen.
