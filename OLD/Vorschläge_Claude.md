# Prompt: Weitere Verbesserungen an index.html (Muenster unvermutet)

## Rolle und Regeln

Du ueberarbeitest eine Single-Page-Anwendung (eine Datei, HTML/CSS/JS, Leaflet als einzige Bibliothek). Die Arbeit erfolgt streng phasenweise.

1. Generiere NOCH KEINEN CODE, bis ich eine Phase starte. Bestaetige zuerst kurz den Plan.
2. Wenn ich eine Phase starte, liefere IMMER vollstaendige, kopierfaehige Codebloecke ohne Platzhalter (kein "// ..." oder "Rest unveraendert").
3. Die App muss nach jeder Phase voll funktionsfaehig sein. Kein DOM-Rewrite, kein Framework, keine neuen Abhaengigkeiten ohne Rueckfrage.
4. Aendere kein bestehendes Verhalten still. Jede Abweichung vom Auftrag nennst du am Ende der Phase in einer kurzen Liste.
5. Wenn du etwas nicht sicher weisst (zum Beispiel einen Hash, ein Browserverhalten), sag es. Nichts raten, nichts erfinden.
6. Sachlich und kritisch antworten. Kein Lob, keine Floskeln.

## Ausgangslage (Stand nach Phase 1 bis 5)

Bereits umgesetzt, nicht erneut anfassen, nur darauf aufbauen:

- **CSS:** Es gibt nur noch Abschnitt 7 (Komponenten, Layout) und Abschnitt 8 (Farbmodi). Alle Farben, Verlaeufe und Schatten laufen ausschliesslich ueber Variablen (`--color-*`, `--gradient-*`, `--shadow-*`), definiert in `:root` und `:root.dark`. Ausserhalb dieser Bloecke darf kein Farbliteral stehen.
- **A11y:** Kategorie-Icons haben `alt=""`. In der Stationsliste steht ein `span.station-sub` statt `small` im Button. Pflichtfelder im Vorschlagsformular haben `aria-required="true"`.
- **Formular:** `readSuggestionForm()` liefert `Object.fromEntries(new FormData(form))`. `validateSuggestion(raw)` ist eine Pure Function ohne DOM-Zugriff. Schluessel: `name`, `category`, `description`, `hint`, `question`, `option-0` bis `option-3`, `correct-option`, `editorial-info`, `latitude`, `longitude`.
- **View Transitions:** `renderTaskView` nutzt `document.startViewTransition` mit Fallback. Ein Guard (`renderedTaskMarkup`) verhindert Transitions bei unveraendertem Markup. `focusTaskTitle()` wartet auf `taskViewUpdate` (die Transition), sonst ginge der Fokus verloren.
- **API und Events:** `StationApiService` kapselt `urls` (`pins`, `details`, `categories`, `submit`), `detailsErrors`, `fetchApi`, `loadCategories`, `loadPins`, `loadDetails`. Zustandsaenderungen rufen `notifyStateChanged(reason)` auf, das `ms:stateChanged` feuert. `render()` wird nur vom zentralen Listener aufgerufen. `showView()` wird in `render()` aus `uiState.detailsOpen` gesetzt.
- **Bekannt, bewusst nicht geaendert:** Die Oberkante von `.task-card` ist grau statt in der Kategoriefarbe, weil `border-color` die 6px-Oberkante ueberschreibt. Siehe Phase D.

## Phase A: Sicherheit und Robustheit

**A1. Link-URLs aus der API validieren**
- Problem: `externalLink(url, ...)` setzt die URL nur HTML-escaped in `href`. Ein Wert wie `javascript:...` wuerde durchgehen. `isValidDetails` prueft bei `links` nur, ob es ein Array ist.
- Auftrag: In der Detail-Validierung jeden Link auf `title` (String) und `url` (String) pruefen. Erlaubt sind nur `http:` und `https:`, geparst mit `new URL(...)`. Ungueltige Details gelten als Fehler (bestehender Fehlerpfad mit "Erneut versuchen").
- Abnahme: Ein Link mit `javascript:` oder ohne gueltige URL fuehrt zum Ladefehler der Station, normale Links funktionieren unveraendert.

**A2. Leaflet mit Integritaetspruefung oder lokal**
- Problem: `leaflet.css` und `leaflet.js` kommen von unpkg ohne SRI-Attribute.
- Auftrag: Entweder `integrity` und `crossorigin="anonymous"` ergaenzen oder die Dateien lokal einbinden und das erklaeren. Den Hash NICHT raten: Er muss aus der tatsaechlich ausgelieferten Version 1.9.4 berechnet werden. Wenn du ihn nicht berechnen kannst, schreib genau das und liefere stattdessen die Anleitung zum Berechnen.
- Abnahme: Karte laedt weiterhin. Bei manipulierter Datei wuerde der Browser das Laden verweigern.

**A3. Doppelversand im Vorschlagsformular verhindern**
- Problem: `handleSuggestSubmit` hat keinen Busy-Zustand. Ein Doppelklick sendet zwei Anfragen.
- Auftrag: Waehrend der Anfrage Absenden-Button sperren, Status "Wird gesendet ..." (`role="status"`) anzeigen, `aria-busy` auf dem Formular setzen. Nach Erfolg oder Fehler zuruecksetzen. Eingaben bleiben bei Fehlern erhalten (wie bisher).
- Abnahme: Zwei schnelle Klicks erzeugen genau eine Anfrage. Nach einem Fehler ist erneutes Absenden moeglich.

**A4. Fetch mit Timeout**
- Problem: `fetchApi` hat keinen Timeout. Im schlechten Mobilnetz haengt die Anfrage unbegrenzt.
- Auftrag: In `StationApiService.fetchApi` einen Timeout (z. B. 15 Sekunden) per `AbortController` einbauen. Ein Timeout wird wie jeder andere Ladefehler behandelt. Den Wert als benannte Konstante im Service fuehren.
- Abnahme: Bei haengender Anfrage erscheint nach dem Timeout der bekannte Fehlerzustand mit Retry.

## Phase B: Darstellung und UX

**B1. Pin-Kontrast**
- Problem: Die Nummer auf dem Pin ist immer weiss, der Hintergrund ist die Kategoriefarbe aus der API. Validiert wird nur das Hex-Format. Bei hellen Farben ist die Nummer schlecht lesbar.
- Auftrag: Textfarbe auf dem Pin aus der relativen Helligkeit der Kategoriefarbe ableiten (dunkel oder hell, Ziel WCAG-Kontrast mindestens 4.5:1 fuer die Nummer, soweit machbar). Berechnung in einer kleinen Pure Function. Die beiden Textfarben als Variablen in `:root` und `:root.dark` definieren, keine Literale im CSS.
- Abnahme: Mit einer hellen Testfarbe (z. B. Gelb) ist die Nummer lesbar. Das Layout der Pins bleibt gleich. Gleiche Behandlung fuer `.legend-icon`, `.ico` und `.mini`, falls dort Text auf Kategoriefarbe steht.

**B2. View Transition auf die Aufgabenkarte begrenzen**
- Problem: Ohne `view-transition-name` ueberblendet der Browser die ganze Seite, nicht nur die Karte. Waehrend der Transition ist die Seite kurz nicht bedienbar.
- Auftrag: Der Container `#task-list` bekommt einen `view-transition-name`. Die Root-Animation wird abgeschaltet, nur die Karte blendet ueber. `prefers-reduced-motion` bleibt vollstaendig wirksam. Das Verhalten ist aus der Spezifikation abgeleitet, nicht im Browser geprueft: Weise darauf hin und nenne die Browser, in denen du es nicht beurteilen kannst.
- Abnahme: Beim Wechsel zwischen Stationen blendet nur der Aufgabenbereich, Kopfbereich und Fortschritt bleiben ruhig.

**B3. Ladezustand**
- Problem: Vor der API-Antwort zeigt der Fortschritt "0 von 0 geloest", einen Ladehinweis gibt es nicht. Nur der Fehlerfall ist abgedeckt. Im statischen HTML steht ausserdem noch der Platzhalter "14".
- Auftrag: Einen Ladezustand einfuehren (`uiState.loading`), der ueber `ms:stateChanged` gerendert wird: kurzer Hinweis "Stationen werden geladen ..." (`role="status"`), Fortschritt zeigt waehrend des Ladens keine falsche Zahl. Die hartcodierte 14 im HTML entfernen oder neutralisieren.
- Abnahme: Beim ersten Aufruf ist erkennbar, dass geladen wird. Danach, und bei Fehlern, verhalten sich Fortschritt und Fehlerbox wie bisher.

## Phase C: Struktur und Absicherung

**C1. Gespeicherten Zustand tatsaechlich anwenden oder entfernen**
- Problem: `selectedOptionIndex` und `hintUsed` werden im LocalStorage gespeichert, beim erneuten Oeffnen einer offenen Station aber nicht angewendet (kein sichtbarer Hint, keine Markierung der falschen Antwort).
- Auftrag: Erst entscheiden und begruenden: anwenden (Hint bleibt offen, falsche Antwort bleibt markiert) oder die Felder streichen. Bei Anwendung muss der Zustand ueber das Rendering laufen, nicht ueber direkte DOM-Manipulation in `toggleHint` und `selectOption`. Die Progress-Version und die Migration alter Daten (`LEGACY_STORAGE_KEY`) duerfen nicht brechen.
- Abnahme: Bestehende gespeicherte Fortschritte laden weiterhin fehlerfrei.

**C2. Zustand zentralisieren**
- Problem: `progress`, `StationApiService.detailsErrors`, `gameStarted`, `renderedTaskMarkup` und `taskViewUpdate` liegen ausserhalb von `uiState`. Mehrere Funktionen schreiben direkt.
- Auftrag: Einen kleinen Store mit einheitlichem Schreibzugriff einfuehren, der intern `notifyStateChanged` ausloest. Kein Framework. Reine Render-Hilfsvariablen (`renderedTaskMarkup`, `taskViewUpdate`) duerfen bewusst ausserhalb bleiben, wenn du das kurz begruendest.
- Abnahme: Ausser dem Store aendert keine Funktion Zustand direkt. Das sichtbare Verhalten ist unveraendert. 

## Phase D: Optional, niedrige Prioritaet

- **Kategoriefarbe an der Karten-Oberkante:** `.task-card` soll die Oberkante in der Kategoriefarbe zeigen. Aktuell ueberschreibt `border-color` die Farbe. Vor der Aenderung kurz bestaetigen lassen, ob das gewollt ist (Optikaenderung).
- **Offline-Faehigkeit:** Service Worker fuer App-Shell und bereits geladene Stationsdaten. Die Karten-Kacheln bleiben online. Nutzungsbedingungen des Tile-Servers vorher pruefen und das Ergebnis nennen, nicht vermuten.
- **`confirm()` beim Zuruecksetzen** durch einen eigenen, barrierefreien Dialog ersetzen (natives `<dialog>` wie beim Vorschlagsformular).
- **Doppelte Datenhaltung:** Details stehen sowohl im Stationsobjekt (`Object.assign`) als auch in `stationDetailsCache`. Auf eine Quelle reduzieren.
- **Toter Code:** Ungenutzte Teile entfernen (zum Beispiel `isManual` und `reset` im ThemeController, falls nirgends genutzt). Vorher per Suche belegen, dass sie ungenutzt sind.
- **Sprache:** "Hint anzeigen" im Button, "Hinweis" im Formular. Einheitlich machen.
- **Windows-Hochkontrastmodus (`forced-colors`):** Pins und Zustaende, die nur ueber Hintergrundfarben unterscheidbar sind, pruefen und bei Bedarf ergaenzen.

## Empfohlene Reihenfolge

A1, A2, A3 zuerst (klein, abgegrenzt). Danach A4, B1, B2, B3. Dann C1 bis C2. Phase D nur nach Bedarf.

## Format der Antworten

- Zu Beginn: kurze Bestaetigung von Plan und Regeln, noch kein Code.
- Pro gestarteter Phase: eine Übersicht Umsetzungen, der Abweichungen und der offenen Punkte.
- Am Ende jeder Phase: knappe Pruefliste, was ich im Browser kontrollieren muss.
