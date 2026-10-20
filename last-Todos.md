# Letzte Änderungen kurz vor Release


1. Ziel und Gesamtstrategie
- Die Änderungen betreffen vor allem die Stationsansicht und die Navigation in der Menuleiste.
- Es gibt zwei übergeordnete Baustellen:
  - UI/UX-Verbesserungen in der Stationsansicht
  - Konsistente Menü- und Animationslogik
- Gleichzeitig ist ein neuer Anreißertext für jede Station als Datensatz-/UI-Anforderung eingebaut. Das ist technisch ein eigener Workstream, weil es Datenmodell, Formular und Darstellung betrifft.

2. Phase 1: Datenmodell und Backend-Erweiterung
Ziel: Neuer Anreißertext für jede Station.
Umsetzung:
- In der Datenbank ein neues Feld für den Anreißertext ergänzen, z. B. `teaser_text`  
- Dieses Feld in allen Stationen-Formularen als optionales Textfeld ergänzen.
- Die Validierung definieren:
  - optionaler Text
  - maximale Länge festlegen
  - nur saubere Darstellung ohne HTML/unerlaubte Formate, falls gewünscht
- Beim Speichern und Laden der Stationen sicherstellen, dass der Text korrekt persistiert und in der Frontend-Datenstruktur verfügbar ist. 

Ergebnis:
- Jede Station kann einen kurzen, ansprechenden Einstiegstext besitzen.

3. Phase 2: Stationsansicht – Fragebereich
Ziel: Karte, Koordinaten und überflüssige Elemente aus der Frageansicht entfernen.
Umsetzung:
- In der Stationsansicht bei Frage:
  - Koordinaten ausblenden
  - verknüpfte Map-Links entfernen
  - den Hinweisbereich an die passende Stelle verschieben
- Die Reihenfolge der Inhalte prüfen und sauber definieren:
  - Titel
  - Anreißertext
  - Hinweis
  - Frage / Antwortoptionen
- Sicherstellen, dass die visuelle Hierarchie sauber und ohne unklare „floatende“ Inhalte bleibt.

Ergebnis:
- Die Frageansicht wirkt klarer und weniger überladen.

4. Phase 3: Stationsansicht – Antwortbereich
Ziel: Reaktions- und Informationsbereich verbessern.
Umsetzung:
- In der Antwortansicht:
  - Koordinaten entfernen
  - Links zu beiden Karten entfernen
  - „Orientierungskarte - OpenStreetMap“ entfernen
  - die Karte unter den Antworten einbinden
  - die Kartenansicht nur als optionales Toggle-Element darstellen
  - die Karte mit Animation ein-/ausblenden, aber für reduzierte Bewegungen deaktivieren
- Lösungstext-Animation überarbeiten:
  - flüssiger
  - weniger abrupt
  - besser abgestimmt auf die Darstellung des Lösungsinhalts
- Hinweistext / Bildunterschrift sauberer strukturieren:
  - „Foto: …“ entfernen
  - der Lösungstext des Fotos soll direkt unter dem Bild erscheinen
- Zusätzlich prüfen:
  - Abstand zwischen Foto, Bildunterschrift und Infotext
  - mobile Darstellung
  - Fokus und Lesbarkeit

Ergebnis:
- Die Auflösung einer Station wirkt hochwertiger und konsistenter.

5. Phase 4: Menuleiste und Ansichtsschalter
Ziel: Das Verhalten der Menüansichten soll konsistent und erwartet sein.
Umsetzung:
- Beim Klick auf „Liste“:
  - Wenn bereits die Listenansicht aktiv ist, diese schließen
  - danach auf „Karte“ wechseln
- Das gleiche Verhalten auch für die Ansichten „Fortschritt“ und „Mehr“ implementieren
- Die Animation der Liste mit der gleichen Übergangslogik wie die anderen Ansichten vereinheitlichen
- Prüfen, ob die Menüpunkte dieselbe State-Logik verwenden oder ob eine gemeinsame Komponente oder ein zentraler Zustand eingeführt werden muss.

Ergebnis:
- Die Navigation zwischen Ansichten ist intuitiv und folgt einem konsistenten Muster.

6. Phase 5: UX- und Zustandsprüfung
Ziel: Sicherstellen, dass das Verhalten in allen Zuständen stabil bleibt.
Umsetzung:
- Zustände prüfen:
  - Station geöffnet / geschlossen
  - Frage beantwortet / Lösung sichtbar
  - Menüliste geöffnet / geschlossen
  - Karte aktiv / Liste aktiv / Fortschritt aktiv
- Interaktionen validieren:
  - Klick auf Menüeinträge
  - Toggle der Karte
  - Animationen beim Ein-/Ausblenden
  - responsive Darstellung
- CSS/Animationen mit `prefers-reduced-motion` absichern
- Browser- und mobile Verhaltensweisen prüfen

Ergebnis:
- Keine visuelle oder logische Inkonsistenz zwischen den Stationen- und Menüansichten.

7. Phase 6: QA, Test- und Abnahmekriterien
Abnahmekriterien:
- Der Anreißertext wird unter dem Titel jeder Station korrekt angezeigt.
- Die Koordinaten und Map-Links verschwinden in den richtigen Ansichten.
- Die Karte erscheint an der vorgesehenen Stelle und ist ein-/ausblendbar.
- Das Foto hat eine sauber gesetzte Bildunterschrift ohne „Foto: …“-Text.
- Die Lösung-Animation wirkt flüssiger und deutlicher.
- Menüwechsel funktionieren bei Liste / Fortschritt / Mehr konsistent.
- Die Animation für die Liste entspricht der übrigen UI.
- Kein Layout- oder Zustandsfehler bei kleineren Bildschirmen.
 


Fragen zur Umsetzung
- Soll der Anreißertext für alle Stationen verpflichtend oder optional sein?
- Wie lang darf der Text maximal sein, damit das Design sauber bleibt?
- - Antwort: Optional, Länge ist variabel, daher sollten erst nur die ersten paar Zeilen angezeigt werden und der Rest bei Bedarf ausgeklappt werden.
 
  
- Gibt es bereits ein Designkonzept oder Mockup für die überarbeitete Lösungsanimation und die Karten-Ansicht?
- - Antwort: So wie die anderen Animationen und UI-Elemente in der App.


- Ist die Menülogik in der App bereits zentralisiert oder soll sie erst im Rahmen dieser Änderung vereinheitlicht werden?
- - Antwort: Die Menülogik ist noch nicht zentralisiert und soll im Rahmen dieser Änderung vereinheitlicht werden.
  