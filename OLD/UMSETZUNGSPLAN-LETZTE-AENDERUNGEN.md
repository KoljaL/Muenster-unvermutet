# Umsetzungsplan: Änderungen kurz vor Release

## Einschätzung

Die Änderungen sind grundsätzlich von **einem Agenten** umsetzbar. Ich empfehle aber, sie **nicht als einen undifferenzierten Änderungsschritt**, sondern nacheinander in fachlichen Phasen umzusetzen und jeweils zu testen. Die Arbeit berührt ein gemeinsames Frontend (`index.html`), das Stationsdatenmodell, Admin-Formulare, API-Ausgabe und bestehende Datenbanken. Eine parallele Aufteilung auf mehrere Agenten würde insbesondere bei Frontend-Zustand und Navigation unnötige Integrationskonflikte erzeugen.

Ein Agent kann alle Phasen in einem Arbeitsdurchgang erledigen, sofern jede Phase einen prüfbaren Zwischenstand hat. Vor einer Freigabe sollten Datenmigration, mobile Darstellung und der Wechsel aller Ansichten separat abgenommen werden.

## Abhängigkeiten und wichtige Bestandsbefunde

- Das Stationsschema liegt in `setup/schema.sql`; `includes/database.php` führt dieses Schema beim Öffnen der Datenbank aus. `CREATE TABLE IF NOT EXISTS` ergänzt jedoch **keine Spalte in einer bereits bestehenden Datenbank**. Für `teaser_text` muss daher eine idempotente Migration oder ein gleichwertiger Update-Schritt vorgesehen werden. Seed-/Snapshot-Daten in `setup/snapshot.sql` sind ebenfalls zu berücksichtigen.
- Stationen werden über `admin/station-edit.php` gepflegt, über `includes/validation.php` validiert und in `admin/station-save.php` gespeichert. Der neue Text muss den gesamten Pfad bis `api/get_details.php` und zur UI durchlaufen.
- Die Stationsansicht und Navigation befinden sich weitgehend gemeinsam in `index.html`. Die Menüeinträge rufen bereits `go(tab)` auf; die Logik setzt derzeit den ausgewählten Tab und die Listen-Sichtbarkeit, schließt aber eine bereits aktive Listenansicht nicht durch Umschalten zurück zur Karte.
- `api/get_details.php` liefert abhängig von der Standortfreigabe unterschiedliche Detailfelder. Es ist in der Umsetzung zu beachten, ob der Anreißertext vor oder erst nach der Freigabe angezeigt werden soll; die API-Antwort darf nicht versehentlich die bestehende Freigabelogik umgehen.
- Playwright ist bereits eingerichtet: `tests/game.spec.js` deckt Spielabläufe ab, `tests/admin.spec.js` den Admin-Workflow. Lokale Tests verwenden eine Datenbankkopie und laufen für die Spieltests auf Mobile Safari und Mobile Chrome; `prefers-reduced-motion: reduce` ist in der Konfiguration aktiviert.

## Empfohlene Umsetzungsphasen

### Phase 1 – Anreißertext: Daten, Admin und API

1. Optionales Feld `teaser_text` ergänzen und eine sichere Migration für bereits angelegte Datenbanken festlegen.
2. Eingabefeld in Neu-/Bearbeiten-Formular integrieren; vorhandene Werte, Vorschlags- und Fehler-Rückwege korrekt beibehalten.
3. Eingabe als Klartext validieren und ausgeben. Die Antwort erlaubt variable Länge und keine feste redaktionelle Maximallänge; falls ein technisches Limit nötig ist, sollte es großzügig und separat von der UI-Kürzung festgelegt werden.
4. Das Feld in Stationsdaten/API ergänzen. Dabei prüfen, ob es bei aktivierter Standortfreigabe öffentlich oder geschützt sein soll.
5. Den Anreißer direkt unter dem Titel anzeigen. Zunächst nur wenige Zeilen präsentieren und den vollständigen Text über ein zugängliches „Mehr anzeigen/Weniger anzeigen“ ausklappbar machen.

**Playwright-Abdeckung:** Admin-Workflow: Wert anlegen, speichern, erneut bearbeiten und Persistenz prüfen. Spieltest: Anreißer direkt unter Titel, optionale Leere, gekürzte Vorschau und auf-/zuklappbarer Volltext. Die Varianten mit und ohne Standortfreigabe sollten entsprechend der festgelegten API-Sichtbarkeit geprüft werden.

### Phase 2 – Frageansicht bereinigen

Koordinaten und externe Kartenlinks aus der unbeantworteten Frageansicht entfernen, den Hinweis an die definierte Stelle setzen und die Reihenfolge Titel → Anreißer → Hinweis → Frage/Antworten konsistent umsetzen. Vorhandene Hinweis-Interaktion und Status-/Fehlerzustände müssen weiter funktionieren.

**Playwright-Abdeckung:** Unbeantwortete Station öffnen; Reihenfolge und Sichtbarkeit prüfen; sicherstellen, dass Koordinaten und Kartenlinks in der Frageansicht fehlen; Hinweis ein-/ausklappen und Antwortoptionen weiterhin bedienen. Mindestens ein Test sollte die Standortfreigabe berücksichtigen.

### Phase 3 – Lösungsansicht, Karte und Bewegung

In der gelösten Ansicht Koordinaten und externe Kartenlinks entfernen. Die Orientierungskarte unterhalb der Antworten als optionales, zugängliches Toggle platzieren und mit einem konsistenten Ein-/Ausblenden versehen. Die OSM-Urheberangabe innerhalb einer eingeblendeten Karte muss erhalten bleiben. Lösungsübergang sowie Abstände, Bildunterschrift und mobile Darstellung überarbeiten; den sichtbaren Präfix „Foto: …“ entfernen und die vorhandene Bildunterschrift direkt unter dem Lösungsbild platzieren.

Animationen müssen `prefers-reduced-motion` beachten; hierfür bestehende Muster der App wiederverwenden. Tastaturbedienung, Fokus und Lesbarkeit des Karten-Toggles prüfen.

**Playwright-Abdeckung:** Richtige Antwort löst die Station; gelöste Ansicht enthält keine Koordinaten-/Kartenlinks, der Präfix fehlt, Caption und Infotext sind an der erwarteten Stelle. Karte lässt sich ein- und ausblenden. Bei reduzierter Bewegung darf keine notwendige Information nur durch Animation verfügbar sein; Animationseigenschaften können ergänzend per CSS-/DOM-Prüfung kontrolliert werden. Mobile Viewports auf Überläufe und sichtbare Bedienelemente prüfen.

### Phase 4 – Navigation vereinheitlichen und Zustände absichern

Menüzustand zentral in `go(tab)` oder einer eng verwandten gemeinsamen Funktion verwalten. Beim erneuten Antippen von Liste, Fortschritt oder Mehr zur Kartenansicht wechseln; beim Wechsel zwischen den Ansichten weiterhin genau einen Tab als aktiv markieren und die jeweils richtige Fläche ein-/ausblenden. Übergang der Liste an die übrigen Ansichten angleichen und bestehende Karte-/Listen- sowie Station-Interaktionen erhalten.

**Playwright-Abdeckung:** Für jeden der drei Menüpunkte Wechsel von Karte zu Ansicht, Wechsel zu einer anderen Ansicht und erneuter Klick zurück zur Karte testen; `aria-selected` und sichtbare/verborgene Panels mitprüfen. Zusätzlich Station geöffnet/geschlossen, responsive Layout und reduzierte Bewegung regressiv abdecken.

## Gemeinsame Abnahmekriterien

- Optionaler Anreißer wird korrekt gespeichert, geladen und unter dem Stationstitel dargestellt; längerer Text kann vollständig ausgeklappt werden.
- Frage- und Lösungsansicht zeigen keine dafür entfernten Koordinaten bzw. Kartenlinks.
- Karten-Toggle sitzt unter den Antworten, funktioniert per Tastatur und blendet die Karte verlässlich ein/aus.
- OSM-Urheberangabe bleibt bei sichtbarer Karte erhalten.
- Bildunterschrift steht direkt unter dem Lösungsfoto, ohne „Foto: …“-Präfix.
- Menüwechsel einschließlich erneutem Klick auf Liste/Fortschritt/Mehr führt in den erwarteten Zustand.
- Kein horizontaler Überlauf oder verlorener Fokus auf kleinen Displays; Animationen beeinträchtigen `prefers-reduced-motion` nicht.
- Vorhandene Tests bleiben grün; die ergänzten Playwright-Tests laufen lokal auf Mobile Safari und Mobile Chrome. Admin-Persistenztests verwenden ausschließlich die konfigurierte lokale Testdatenbank.

## Beantwortete Fragen aus `last-Todos.md`

- **Anreißer verpflichtend oder optional?** Optional.
- **Maximale Länge?** Keine feste redaktionelle Länge; der Text darf variabel lang sein. Zunächst werden nur die ersten Zeilen gezeigt, der übrige Text ist bei Bedarf ausklappbar. Ein mögliches technisches Schutzlimit ist davon getrennt festzulegen.
- **Design für Lösungsanimation und Kartenansicht?** An den bereits vorhandenen Animationen und UI-Elementen der App orientieren.
- **Menülogik bereits zentralisiert?** Nein. Sie soll im Rahmen der Änderung vereinheitlicht werden.

## Fazit

**Ein Agent reicht aus**, wenn die Umsetzung sequenziell und in den vier oben beschriebenen Phasen erfolgt. Datenmodell/API/Admin bilden die Voraussetzung für die neue UI; Frage- und Lösungsansicht können danach separat umgesetzt und getestet werden. Die Navigationsänderung sollte als eigener letzter Integrationsschritt folgen, weil sie dieselbe Frontend-Datei und Zustandslogik betrifft. Eine einzelne große Änderung ohne Zwischenprüfungen wäre wegen Migration, Freigabelogik und gemeinsamem UI-Zustand unnötig riskant.
