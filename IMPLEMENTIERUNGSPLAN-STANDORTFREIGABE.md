# Implementierungsplan: Standortabhängige Freigabe der Antwortmöglichkeiten

## Ziel

Die Antwortmöglichkeiten einer Station sollen erst sichtbar und auswählbar
werden, wenn der Spieler den gesuchten Ort tatsächlich erreicht hat. Die
Standortprüfung soll dabei verständlich, datensparsam und auch bei typischen
Ungenauigkeiten der mobilen GPS-Ortung zuverlässig funktionieren.

Zusätzlich soll die Stationsansicht eine Orientierungskarte auf Basis von
OpenStreetMap enthalten, ohne dass der Spieler die Spielseite verlassen muss.

## Festgelegte Anforderungen

- Der Standardradius für alle Stationen beträgt zunächst **50 Meter**.
- Die gemeldete GPS-Genauigkeit darf höchstens **50 Meter** betragen.
- Vor der Freigabe bleiben sichtbar:
  - Aufgabenfoto
  - Hinweis
  - Koordinaten
  - Orientierungskarte
- Vor der Freigabe bleiben verborgen oder gesperrt:
  - Antwortmöglichkeiten
  - Schaltfläche „Lösung anzeigen“
- Bei verweigerter Standortberechtigung, fehlendem Standort oder zu großer
  Ungenauigkeit bleiben die Antworten gesperrt.
- Der Spieler kann die Prüfung über **„Standort aktualisieren“** erneut starten.
- Nach erfolgreicher Prüfung bleibt die Station im lokalen Spielstand
  freigeschaltet.
- Die genaue Position des Spielers wird nicht dauerhaft gespeichert.
- Der Server prüft die vom Browser übermittelten Standortdaten.
- Die Prüfung soll als Spielregel dienen, kann aber keine vollständig
  manipulationssichere Standortkontrolle garantieren.
- Die Karte zeigt zunächst:
  - den Stationsstandort
  - die aktuelle Spielerposition, sofern vorhanden
  - die Entfernung zwischen Spieler und Station
  - optional eine direkte Verbindungslinie
- Eine vollständige Turn-by-Turn-Navigation ist nicht Bestandteil der ersten
  Umsetzung.
- Die Standortabfrage erfolgt nur auf ausdrückliche Aktion des Spielers und
  nicht dauerhaft im Hintergrund.

## Backend-Einstellungen

Die Funktion soll über die vorhandene Einstellungs-Infrastruktur im
Admin-Bereich steuerbar sein. Die zentrale Einstellung wird als zusammenhängendes
Feature behandelt:

### Standortabhängige Antwortfreigabe

Diese Hauptschaltung steuert gemeinsam:

- die Standortprüfung,
- die Freigabe der Antwortmöglichkeiten,
- die Freigabe von „Lösung anzeigen“,
- die serverseitige Durchsetzung beim Abruf geschützter Stationsdetails.

Vorgesehene Zustände sind:

- **Aus:** Antwortmöglichkeiten und Lösung werden wie bisher direkt
  bereitgestellt. Der bestehende Spielablauf bleibt erhalten.
- **Ein:** Antwortmöglichkeiten und Lösung werden erst nach erfolgreicher
  Standortprüfung freigegeben.
- **Test:** Die Funktion kann für definierte Vorschau- oder Testaufrufe
  aktiviert werden, ohne die reguläre Einstellung zu überschreiben.

Das Ausblenden von Bedienelementen im Frontend ist keine Zugriffskontrolle.
Wenn die Einstellung **Ein** ist, muss der Server den geschützten Abruf
ebenfalls erzwingen. Wenn sie **Aus** ist, muss der normale Abruf ohne
Standortprüfung weiterhin funktionieren.

### OSM-Orientierungskarte

Die Orientierungskarte wird als separate optionale Einstellung vorgesehen:

- **Ein:** Karte mit Stationsziel und, sofern vorhanden, Spielerposition
  anzeigen.
- **Aus:** Standortfreigabe kann weiterhin aktiv sein, die Karte wird aber
  nicht angezeigt.

Die Karte darf nicht automatisch als Voraussetzung für die Standortprüfung
behandelt werden. Dadurch bleiben Standortfreigabe und Orientierungskarte
unabhängig test- und abschaltbar.

### Prüfgrenzwerte

Radius und Genauigkeitsgrenze werden als konfigurierbare Werte hinterlegt,
nicht als zusätzliche Feature-Schalter:

- **Prüfungsradius:** Standardwert 50 Meter
- **Maximale GPS-Ungenauigkeit:** Standardwert 50 Meter

Die Werte müssen serverseitig validiert und angewendet werden. Eine spätere
Erweiterung um individuelle Radiuswerte pro Station bleibt möglich, ist aber
nicht Bestandteil der ersten Umsetzung. Zu kleine, negative oder anderweitig
ungültige Werte dürfen nicht gespeichert werden.

Die Einstellungen sollen an die bestehende Registry und die vorhandenen
Admin-/API-Muster angebunden werden, wie sie in
[`ui-options.md`](./ui-options.md) beschrieben sind.

## Aktueller Ausgangspunkt

Die bestehende Anwendung in [`index.html`](./index.html) verwendet bereits:

- Stationskoordinaten für die Kartenmarker und Links zu Kartenanbietern
- eine Leaflet-Karte mit OpenStreetMap-Kacheln
- eine Detailansicht pro Station
- nachgeladenen Stationsdetails
- lokal gespeicherten Stationsfortschritt
- eine API-Struktur unter `api/`
- PHP- und SQLite-Unterstützung im Backend

Die Stationsdetails werden derzeit beim Öffnen der Station nachgeladen. Die
Antwortoptionen sind Bestandteil dieser Details. Für die Standortfreigabe muss
deshalb verhindert werden, dass die Antwortoptionen vor der erfolgreichen
Prüfung in der Oberfläche oder in einer ungeschützten Antwort verfügbar sind.

## Vorgeschlagener Ablauf

### 1. Station öffnen

Beim Öffnen einer Station werden Aufgabenfoto, Hinweis, Koordinaten und die
Orientierungskarte angezeigt. Statt der Antwortoptionen erscheint ein klarer
Statusbereich, zum Beispiel:

> Begib dich zum abgebildeten Ort und aktualisiere anschließend deinen Standort.

Daneben wird die Schaltfläche **„Standort aktualisieren“** angezeigt.

### 2. Standort prüfen

Nach der Aktion fragt der Browser die aktuelle Position ab. Die Anwendung
berücksichtigt mindestens:

- Breitengrad
- Längengrad
- Genauigkeit in Metern
- Zeitpunkt der Positionsbestimmung

Die Anwendung sollte eine veraltete oder offensichtlich unbrauchbare Position
nicht als aktuelle Prüfung behandeln.

### 3. Serverprüfung

Die Anwendung übermittelt die für die Prüfung erforderlichen Daten an einen
dedizierten Backend-Endpunkt. Der Server:

1. validiert Datentypen und Wertebereiche,
2. lädt die offiziellen Koordinaten der angeforderten Station aus der
   Datenbank,
3. berechnet die Entfernung zwischen Spieler und Station,
4. prüft die gemeldete Genauigkeit,
5. entscheidet anhand von 50 Metern Radius und 50 Metern Genauigkeitsgrenze,
6. liefert nur das Prüfergebnis und eine geeignete Statusmeldung zurück.

Die vom Client übermittelten Stationskoordinaten dürfen nicht als
Vertrauensquelle verwendet werden. Maßgeblich sind die serverseitig
gespeicherten Stationsdaten.

### 4. Freigabe

Bei erfolgreicher Prüfung wird eine lokale Freigabe für die konkrete Station
gespeichert. Danach rendert die Anwendung die Antwortmöglichkeiten und
„Lösung anzeigen“ wie bisher.

Bei nicht erfolgreicher Prüfung bleiben die Antworten gesperrt. Die Oberfläche
zeigt den konkreten Grund an, soweit er sicher ermittelt werden kann, zum
Beispiel:

- „Du bist noch etwa 120 Meter vom Ziel entfernt.“
- „Die Standortgenauigkeit ist derzeit zu gering.“
- „Der Standort konnte nicht ermittelt werden.“
- „Der Zugriff auf den Standort wurde verweigert.“

Die Schaltfläche zum erneuten Prüfen bleibt verfügbar.

## Datenmodell und lokaler Spielstand

Der Fortschritt einer Station sollte um einen Zustand für die erfolgreiche
Standortfreigabe ergänzt werden, zum Beispiel:

- `locationVerified: false | true`

Gespeichert wird ausschließlich das Ergebnis der Freigabe, nicht:

- die genaue Spielerposition,
- die GPS-Genauigkeit,
- ein Bewegungs- oder Standortverlauf,
- die genaue Uhrzeit einzelner Prüfungen.

Beim Zurücksetzen des Spiels wird auch die Standortfreigabe gelöscht.

Eine bereits gelöste Station bleibt weiterhin zugänglich. Für bereits gelöste
Stationen ist keine erneute Standortprüfung erforderlich.

## API-Änderungen

### Neuer Prüf-Endpunkt

Es sollte ein dedizierter Endpunkt für die Standortprüfung ergänzt werden, etwa:

```text
api/verify_location.php
```

Der konkrete Name kann an die vorhandenen API-Konventionen angepasst werden.

Der Endpunkt benötigt konzeptionell:

- Stations-ID
- Breitengrad
- Längengrad
- gemeldete Genauigkeit
- optional einen Zeitstempel der Positionsbestimmung

Die Antwort sollte eine eindeutige Struktur mit mindestens folgenden
Informationen liefern:

- erfolgreich oder nicht erfolgreich
- Statuscode beziehungsweise Fehlergrund
- Entfernung zur Station, sofern sie sicher berechnet werden kann
- serverseitig verwendeter Prüfstatus

Es sollten keine erfolgreichen Antworten zurückgegeben werden, wenn:

- die Stations-ID unbekannt ist,
- Koordinaten fehlen oder außerhalb gültiger Bereiche liegen,
- die Genauigkeit fehlt oder ungültig ist,
- die Genauigkeit größer als 50 Meter ist,
- die Entfernung größer als 50 Meter ist,
- die Anfrage nicht korrekt authentifiziert beziehungsweise gegen
  CSRF-/Missbrauchsanforderungen abgesichert ist, soweit dies zur bestehenden
  API-Struktur passt.

### Schutz der Antwortdetails

Die Antwortoptionen sollten nicht bereits vor der Standortfreigabe vollständig
an den Browser geliefert werden. Dafür sind zwei mögliche Umsetzungsvarianten
zu prüfen:

1. Stationsdetails werden weiterhin nachgeladen, enthalten vor der Freigabe
   aber keine Antworten und keine Lösungsschaltfläche.
2. Die Details werden in öffentliche und geschützte Daten aufgeteilt; der
   geschützte Teil wird erst nach erfolgreicher serverseitiger Prüfung
   angefordert.

Die zweite Variante ist sicherer gegen ein einfaches Auslesen der
Antwortoptionen aus den Browserdaten und sollte bevorzugt werden, sofern sie
mit der bestehenden API-Struktur ohne unnötige Komplexität umgesetzt werden
kann.

Die Einstellung **Standortabhängige Antwortfreigabe** muss bei jedem Abruf der
geschützten Details berücksichtigt werden. Eine lokale Freigabe im Browser
darf bei aktivierter Funktion allein nicht ausreichen; der Server muss eine
erfolgreiche Prüfung für die angeforderte Station nachvollziehbar anerkennen.
Bei deaktivierter Funktion muss der bisherige direkte Abruf möglich bleiben.

## Orientierungskarte

Die bestehende Leaflet-/OpenStreetMap-Karte soll für die Stationsansicht
erweitert oder um eine separate, kompakte Karte ergänzt werden.

### Anzeige

Die Karte sollte enthalten:

- Marker des Stationsziels
- Marker der zuletzt ermittelten Spielerposition
- Entfernung zum Ziel
- optional eine direkte Verbindungslinie
- eine sichtbare OpenStreetMap-Quellenangabe

Die Karte sollte in einem aufklappbaren oder kompakt dimensionierten Bereich
angezeigt werden, damit die eigentliche Aufgabe auf mobilen Geräten im
Vordergrund bleibt.

### Standortaktualisierung

Die Karte verwendet nur die Position, die der Spieler über
„Standort aktualisieren“ abfragt. Eine permanente Überwachung mit
`watchPosition` ist für die erste Version nicht vorgesehen.

Wenn der Spieler die Standortfreigabe erfolgreich erhält, kann dieselbe
Position für die Kartendarstellung und die Serverprüfung verwendet werden,
sofern sie noch aktuell ist.

### Routing

Eine echte Fußgängerroute mit Abbiegehinweisen wird zunächst nicht integriert.
Dafür wäre zusätzlich ein Routing-Dienst wie OSRM, GraphHopper,
openrouteservice oder Valhalla erforderlich. Eine spätere Erweiterung sollte
den Dienst, seine Nutzungsgrenzen, Datenschutzanforderungen und die
OpenStreetMap-Attribution gesondert prüfen.

## Frontend-Anpassungen

Die betroffenen Bereiche in [`index.html`](./index.html) sind voraussichtlich:

- Stationsfortschritt und `localStorage`
- Rendering der ungelösten Stationsansicht
- Nachladen und Validieren der Stationsdetails
- Aktionen und Event-Delegation
- Status- und Fehlermeldungen
- Leaflet-Karte und Stationsansicht
- Fokusführung und `aria-live`-Meldungen

Die Oberfläche sollte eindeutige Zustände unterscheiden:

1. Standortprüfung noch nicht gestartet
2. Standort wird ermittelt
3. Standort wird geprüft
4. Standort erfolgreich freigegeben
5. Spieler ist noch zu weit entfernt
6. Genauigkeit ist zu gering
7. Standortberechtigung verweigert
8. Standort technisch nicht verfügbar
9. Serverprüfung fehlgeschlagen

Die Antwortbuttons dürfen in allen Zuständen außer dem erfolgreichen
Freigabestatus weder auswählbar noch versehentlich über Tastatur oder
Accessibility-Tree erreichbar sein.

Bei der Freigabe sind insbesondere folgende Bedienungsdetails zu erhalten:

- sichtbarer Fokus
- verständliche `aria-live`-Statusmeldung
- Tastaturbedienbarkeit
- sinnvolle Fokusposition nach erfolgreicher Prüfung
- Rücksicht auf `prefers-reduced-motion`

## Backend-Anpassungen

Voraussichtlich erforderlich sind:

- neuer API-Endpunkt für die Prüfung
- zentrale Distanzberechnung
- Eingabe- und Wertebereichsvalidierung
- serverseitiges Nachschlagen der Stationskoordinaten
- einheitliche JSON-Fehlerantworten
- Prüfung der bestehenden CSRF- und Authentifizierungsstrategie
- Rate-Limit- oder Missbrauchsschutz, falls der Endpunkt öffentlich erreichbar
  ist
- Registrierung der Hauptschaltung für die standortabhängige Antwortfreigabe
- Registrierung der separaten OSM-Karten-Einstellung
- Speicherung, Validierung und Auslieferung von Radius und maximaler
  GPS-Ungenauigkeit
- serverseitige Berücksichtigung der Einstellungen beim Prüf- und
  Detailabruf

Die Standortprüfung sollte keine genauen Positionsdaten dauerhaft in der
SQLite-Datenbank speichern. Falls für technische Fehler kurzfristig Logs
notwendig sind, dürfen diese keine unnötigen personenbezogenen Standortdaten
enthalten und müssen zeitlich begrenzt beziehungsweise minimiert werden.

## Datenschutz und technische Voraussetzungen

- Geolocation funktioniert zuverlässig nur in einem sicheren Kontext, in der
  Regel über HTTPS oder lokal auf `localhost`.
- Die Standortberechtigung wird erst nach einer bewussten Aktion angefordert.
- Die Anwendung erklärt kurz, warum der Standort benötigt wird.
- Die genaue Position wird nur für die aktuelle Prüfung verwendet.
- Es wird kein dauerhafter Standortverlauf erstellt.
- Die Anwendung sollte bei verweigerter Berechtigung keine irreführende
  Erfolgsmeldung anzeigen.
- Die OSM-Quellenangabe muss sichtbar bleiben.
- Der Einsatz eines externen Routing-Dienstes ist für die erste Version nicht
  erforderlich und würde eine zusätzliche Datenübermittlung bedeuten.

## Testplan

### Fachliche Tests

- Position exakt am Stationsstandort: Freigabe
- Position innerhalb von 50 Metern: Freigabe, sofern Genauigkeit höchstens
  50 Meter beträgt
- Position knapp außerhalb des Radius: keine Freigabe
- Genauigkeit knapp über 50 Metern: keine Freigabe
- Position innerhalb des Radius, aber Genauigkeit zu schlecht: keine Freigabe
- unbekannte Stations-ID: kontrollierter Fehler
- ungültige oder fehlende Koordinaten: kontrollierter Fehler
- wiederholte Prüfung nach einem Fehlschlag
- Freigabe bleibt nach erneutem Öffnen der Station erhalten
- Freigabe wird beim Zurücksetzen gelöscht
- gelöste Stationen bleiben ohne erneute Prüfung zugänglich

### Browser- und UX-Tests

- Standortberechtigung wird angenommen
- Standortberechtigung wird verweigert
- Standortdienst ist deaktiviert
- Gerät liefert keine Position innerhalb des Timeouts
- schlechte GPS-Genauigkeit
- temporärer API-Fehler
- langsame Netzwerkverbindung
- mobile Darstellung im Hoch- und Querformat
- Bedienung ausschließlich mit Tastatur
- Screenreader-Ansage der Zustandsänderungen
- Dark Mode und reduzierte Bewegung

### Sicherheits- und Integritätstests

- Antwortdetails sind vor der Freigabe nicht im HTML oder in einer öffentlichen
  API-Antwort enthalten
- manipulierte Client-Koordinaten werden nur nach den Serverregeln bewertet
- Client kann eine lokale Freigabe nicht ohne erneute Serverprüfung
  vortäuschen, sofern die geschützten Details serverseitig abgesichert werden
- ungültige Eingaben führen nicht zu einer Erfolgsmeldung
- wiederholte Prüfungsanfragen werden angemessen behandelt

## Umsetzungsreihenfolge

1. Bestehende Datenflüsse für Stationspins, Stationsdetails und lokalen
   Fortschritt dokumentieren.
2. Einstellungen für Hauptschaltung, OSM-Karte und Prüfgrenzwerte in die
   vorhandene Registry integrieren.
3. Entscheidung für die Aufteilung in öffentliche und geschützte
   Stationsdetails technisch konkretisieren.
4. Server-Endpunkt mit Distanz- und Genauigkeitsprüfung implementieren.
5. API-Validierung und Fehlerantworten testen.
6. Lokalen Fortschritt um die Standortfreigabe erweitern.
7. Frontend-Zustände und die gesperrte Stationsansicht ergänzen.
8. Erfolgreiche Standortprüfung mit der geschützten Detailabfrage verbinden.
9. Orientierungskarte integrieren und an die separate Karteneinstellung
   anbinden.
10. Kombinationen aus aktivierter/deaktivierter Hauptschaltung, Kartenoption,
    Testmodus und API-Ausfall prüfen.
11. Accessibility-, Datenschutz- und Fehlerszenarien testen.
12. Dokumentation in [`README.md`](./README.md) aktualisieren.
13. Bestehende Tests ausführen und neue Tests für die Standortprüfung ergänzen.

## Nicht Bestandteil der ersten Version

- vollständig manipulationssichere Standortverifikation
- permanente Standortüberwachung
- Speicherung von Standortverläufen
- Turn-by-Turn-Navigation
- Offline-Karten und Offline-Routing
- automatische individuelle Radiuswerte pro Station
- alternative Freischaltung ohne Standortprüfung
