# Umsetzungsplan: „Münster unvermutet“ als PWA

## Ziel und Abgrenzung

Die vorhandene Anwendung in [`index.html`](./index.html) soll als installierbare,
app-ähnliche Webanwendung auf Mobilgeräten und Desktop angeboten werden. Im
Vordergrund stehen ein Startbildschirm-/App-Symbol, ein passender App-Name und
ein eigenständiges Fenster ohne übliche Browser-Adressleiste.

**Offline-Fähigkeit ist ausdrücklich nicht Teil des Vorhabens.** Die Anwendung
bleibt netzabhängig: Stationsdaten und Einstellungen kommen aus der PHP-API,
die Karte benötigt Leaflet und externe OpenStreetMap-Kacheln. Es wird daher
kein Service Worker und keine Offline-Cache-Strategie eingeführt. Eine
Installation macht die Inhalte nicht automatisch offline verfügbar.

## Bestandsaufnahme

- Das Frontend ist eine einzelne, mobil ausgelegte HTML-Datei mit relativ
  referenzierten PHP-Endpunkten unter `api/`.
- Fortschritt und einige Einstellungen werden im Browser über `localStorage`
  gespeichert.
- Die Standortprüfung nutzt die Geolocation-API und braucht HTTPS (außer auf
  `localhost`).
- Leaflet-CSS und -JavaScript werden derzeit von unpkg geladen. Die
  Kartenkacheln kommen von `tile.openstreetmap.de`.
- Das Viewport-Meta-Tag berücksichtigt bereits `viewport-fit=cover`; die
  Oberfläche verwendet außerdem Safe-Area-Abstände.
- Im `<head>` fehlen derzeit ein Web-App-Manifest, PWA-Installationsmetadaten
  und eine Apple-Touch-Icon-Verknüpfung. Ein Service Worker ist für das
  gewünschte Nicht-offline-Ziel nicht vorgesehen.
- Das bereitgestellte [`MS_Rathhaus_Logo.jpg`](./MS_Rathhaus_Logo.jpg) ist
  236 × 233 Pixel groß. Es zeigt eine weiße Lupe mit stilisierten Gebäudelinien
  auf türkisfarbenem Grund. Es dient als Gestaltungsvorlage für sämtliche
  App- und Browser-Symbole.

## Umsetzung in Phasen

### 1. Bereitstellungsadresse und Installationsumfang festlegen

1. Eine endgültige HTTPS-Adresse für das Spiel bestimmen und prüfen, ob es im
   Webserver-Stammverzeichnis oder in einem Unterverzeichnis liegt.
2. Sicherstellen, dass Manifest und Symbol-Dateien unter derselben Herkunft
   erreichbar sind wie die Spielseite.
3. Den Installationsumfang auf das Spiel begrenzen. `start_url` und `scope`
   müssen zum tatsächlichen Bereitstellungspfad passen; beim Start sollen
   Stationslinks und vorhandene Query-Parameter weiterhin funktionieren.
4. Sicherstellen, dass die bestehende Apache-/PHP-Bereitstellung JSON- und
   Bilddateien mit geeigneten MIME-Typen ausliefert. Falls nötig, gezielt
   Webserver-Konfiguration ergänzen.

### 2. Logo-Vorlage in geeignete Symbole umsetzen

Die Quelldatei ist fast quadratisch, aber klein und im JPEG-Format. Sie sollte
nicht einfach gestreckt oder als JPEG für alle Plattformen verwendet werden.
Stattdessen das Motiv als saubere, quadratische Grafik aufbereiten: weißen
Lupe-/Gebäudezug auf dem vorhandenen türkisfarbenen Hintergrund, mit genügend
Rand, damit beim Abrunden oder Maskieren keine wichtigen Teile abgeschnitten
werden. Das Original bleibt erhalten.

Vorgesehene Dateien (PNG, sRGB):

- `assets/icons/icon-192.png` – Manifest-Symbol für unterstützende Browser.
- `assets/icons/icon-512.png` – großes Manifest-Symbol.
- `assets/icons/icon-512-maskable.png` – eigens mit großzügigem Sicherheitsrand
  gestaltete Variante für adaptive/maskierte Android-Symbole.
- `assets/icons/apple-touch-icon.png` (180 × 180) – Symbol für iOS/iPadOS.
- `assets/icons/favicon-32.png` (32 × 32) – optionales Browser-Tab-Symbol.

Vor Abnahme die Symbole in tatsächlicher kleiner Darstellung und mit
verschiedenen Android-Masken kontrollieren. Die Maskable-Variante muss innerhalb
der zentralen sicheren Fläche lesbar bleiben. Die 236 × 233 Pixel große Vorlage
reicht für eine technische erste Ableitung, kann beim Hochskalieren aber weich
wirken; vor Freigabe ist die Qualität bei 192/512 Pixel zu beurteilen. Bei Bedarf
das Motiv anhand der Vorlage sauber nachzeichnen lassen.

### 3. Web-App-Manifest ergänzen

Eine neue Datei `manifest.webmanifest` im passenden App-Verzeichnis anlegen und
mindestens folgende Eigenschaften definieren:

- `name`: `Münster unvermutet – Foto-Schnitzeljagd`
- `short_name`: ein auf kleinen Symbolen lesbarer Kurzname, zum Beispiel
  `Münster unvermutet`
- `lang`: `de`
- `start_url`: Einstieg in das Spiel am tatsächlichen Bereitstellungspfad
- `scope`: nur der Spielpfad, nicht unbeabsichtigt die gesamte Website
- `display`: `standalone`
- `background_color` und `theme_color`: an die vorhandenen hellen/dunklen
  Oberflächen anpassen; den Start-/Browser-Chrome-Farbton auf Mobilgeräten
  berücksichtigen
- passende `icons`-Einträge mit Größen, MIME-Typ und Zwecken `any` bzw.
  `maskable`
- optional eine stabile `id`, falls sie zur endgültigen Startadresse passt

Das Manifest anschließend mit `<link rel="manifest" href="…">` in den
`<head>` von `index.html` einbinden. Relative Pfade müssen sowohl in der
Entwicklungsumgebung als auch am finalen Veröffentlichungsort korrekt sein.

### 4. iOS-/iPadOS- und Browser-Metadaten ergänzen

Im `<head>` von `index.html`:

- `apple-touch-icon` auf das 180 × 180-PNG setzen.
- `apple-mobile-web-app-capable` mit `yes` setzen, damit Safari die
  Home-Bildschirm-Web-App eigenständig öffnen kann.
- Einen passenden `apple-mobile-web-app-title` vorsehen, sofern der kurze
  Name nicht zuverlässig aus dem Manifest übernommen wird.
- `theme-color` setzen; falls für helle und dunkle Darstellung erforderlich,
  die Unterstützung mit den Zielbrowsern prüfen.
- Vorhandene Viewport- und Safe-Area-Einstellungen erhalten.

Keine browser-spezifischen Meta-Tags ohne konkreten Plattformnutzen ergänzen.
Die iOS-Installation erfolgt typischerweise manuell über Safari → Teilen →
„Zum Home-Bildschirm“. Es soll nicht mit einem universellen iOS-Installations-
Popup gerechnet werden.

### 5. Installation verständlich machen (optional, empfohlen)

Eine kleine, nicht aufdringliche Installationshilfe für Mobilgeräte und Desktop
vorsehen:

- Android und unterstützte Desktop-Browser: bei verfügbarer Installationsaktion
  eine passende Anleitung oder Installieren-Schaltfläche anbieten. Eine
  `beforeinstallprompt`-Behandlung ist browserabhängig und darf nicht
  Voraussetzung für die Nutzung sein.
- iPhone/iPad: kurze Safari-Anleitung anzeigen, falls die App noch nicht zum
  Home-Bildschirm hinzugefügt wurde. Andere iOS-Browser können je nach
  Betriebssystemversion unterschiedlich installieren.
- Nach erfolgreicher Installation bzw. beim erneuten Besuch die Hilfe nicht
  ständig erneut anzeigen.
- Im normalen Browser und im installierten Fenster muss das Spiel vollständig
  nutzbar bleiben; die Installation ist optional.

Für diese Hilfe müssen Installationsstatus und Browsermöglichkeiten zuverlässig
erkannt werden. Wo das nicht sicher möglich ist, eine neutrale Anleitung statt
einer falschen Erfolgsmeldung zeigen.

### 6. Online-Verhalten und Erwartungen absichern

- Keine Service-Worker-Datei registrieren; keine Offline-Cache- oder
  Hintergrund-Synchronisationslogik hinzufügen.
- Den bestehenden Datenfluss zu `api/` sowie externe Leaflet- und
  Kartenressourcen unverändert online betreiben.
- Bei fehlendem Netz weiterhin Fehler- und Wiederholen-Zustände der Anwendung
  verwenden. Falls die vorhandene Meldung nicht verständlich genug ist, sie
  knapp um den Hinweis ergänzen, dass Netzwerkzugriff erforderlich ist.
- Nicht den Eindruck erwecken, dass die App offline spielbar sei. Das gilt
  besonders für Installationstexte und Hilfen.
- Den vorhandenen lokalen Fortschritt weiterverwenden. Auf iOS ist zu prüfen,
  ob ein aus Safari zum Home-Bildschirm hinzugefügtes Web-App-Fenster einen
  separaten Datenspeicher hat: Fortschritt, der vorher im Safari-Tab angelegt
  wurde, kann dann möglicherweise nicht automatisch in der installierten
  Web-App erscheinen.

### 7. Tests und Abnahme

Automatisierte Tests an die vorhandene Playwright-Konfiguration
(`playwright.config.js`, `tests/game.spec.js`) anpassen oder ergänzen. Die
Installationsdarstellung nicht nur per User-Agent vortäuschen, sondern die
wichtigen Fälle zusätzlich auf echten Zielgeräten prüfen.

**Funktionale Abnahmekriterien**

- Manifest ist am Veröffentlichungsort abrufbar, valides JSON und referenziert
  alle vorhandenen Symbole mit korrekten Pfaden, Größen und MIME-Typen.
- Die Seite öffnet sich über HTTPS und wird in unterstützten Browsern als
  installierbar erkannt, soweit die aktuelle Browser-/Betriebssystemversion dies
  unterstützt.
- Installierter Start öffnet `index.html` im eigenständigen Modus am richtigen
  Pfad; Startparameter wie `?station=…` funktionieren weiterhin.
- Name, Symbol, Farben und Ausschnitt wirken auf Startbildschirm/App-Launcher,
  Browser-Tab und Installationsdialog korrekt.
- iPhone/iPad zeigt das Apple-Touch-Icon beim Hinzufügen zum Home-Bildschirm;
  safe-area-Abstände, Dialoge, Zurücknavigation und Standortabfrage funktionieren
  auch im installierten Fenster.
- Android-Symbol bleibt in unterschiedlichen adaptiven Masken vollständig
  erkennbar; Installationshinweise erscheinen nur, wenn sie sinnvoll sind.
- Desktop-Ansicht bleibt in Browserfenster und eigenständigem App-Fenster
  verwendbar; vorhandenes zentriertes Layout wird nicht abgeschnitten.
- Online funktionieren Datenladen, Kartendarstellung, Stationsdetails,
  Standortprüfung und Fortschritt wie zuvor. Offline wird keine Funktionalität
  versprochen; Fehlerzustände bleiben verständlich.
- Kein Service Worker wird registriert und es werden keine Stationsantworten,
  geschützten Details oder Kartenkacheln durch neue PWA-Logik gecacht.

**Manuelle Geräte-/Browserprüfung**

- iOS/iPadOS: Safari und zum Home-Bildschirm hinzugefügte Web-App.
- Android: Chrome, Installation und Start über App-Symbol.
- Desktop: Chrome oder Edge (Installation, falls angeboten), zusätzlich
  Safari/macOS als normale Webansicht.
- Kleiner Mobilbildschirm, Querformat, heller/dunkler Look und längere
  Stationstitel bzw. Stationslinks.

## Reihenfolge und voraussichtliche Dateien

1. Hosting-Pfad und HTTPS bestätigen.
2. Symbole aus dem Logo gestalten und visuell abnehmen.
3. `manifest.webmanifest` erstellen.
4. Manifest- und Plattform-Metadaten in `index.html` einbinden.
5. Installationshilfe und bei Bedarf kurze Online-Fehlermeldung ergänzen.
6. Playwright-Regressionstests sowie echte Geräteprüfungen durchführen.
7. README um Installationsschritte und den ausdrücklich netzabhängigen Betrieb
   ergänzen.

Voraussichtliche neue Dateien: `manifest.webmanifest` und Symbole unter
`assets/icons/`. Voraussichtlich anzupassen: `index.html`, gegebenenfalls
`README.md` sowie gezielte Tests. Ein Service Worker, eine neue Build-Kette und
Offline-Datenhaltung sind nicht erforderlich.

## Quellen und technische Hinweise

- [MDN: Web App Manifest](https://developer.mozilla.org/en-US/docs/Web/Manifest) –
  Manifest-Felder und Icon-Einträge.
- [MDN: PWAs installierbar machen](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps/Guides/Making_PWAs_installable) –
  Bereitstellung und browserabhängige Installationsanforderungen.
- [web.dev: Installation von PWAs](https://web.dev/learn/pwa/installation/) –
  Installationsverhalten unter Android, iOS/iPadOS und Desktop.
- [Apple: Eine Website als Web-App verwenden](https://support.apple.com/en-us/104996) –
  Web-Apps unter Apple-Plattformen; Web-App-Daten können von Safari getrennt sein.
- [MDN: Geolocation API](https://developer.mozilla.org/en-US/docs/Web/API/Geolocation_API) –
  Berechtigungs- und Secure-Context-Anforderungen der Standortfunktion.

Installierbarkeit und Darstellung ändern sich mit Browser- und
Betriebssystemversionen. Die obigen Eigenschaften sind daher auf den zum
Umsetzungszeitpunkt unterstützten Zielversionen praktisch zu verifizieren.

## Offene Fragen

1. Unter welcher endgültigen HTTPS-Adresse und in welchem Pfad wird das Spiel
   veröffentlicht (Domain-Stamm oder Unterverzeichnis)?
   ANTWORT: https://stina-ms.de/muenster-unvermutet/

2. Soll der App-Name auf dem Startbildschirm vollständig
   **„Münster unvermutet“** heißen oder ist ein kürzerer Name gewünscht?
   ANTWORT: **„Münster unvermutet“**

3. Soll eine Installationshilfe in der Spieloberfläche erscheinen? Wenn ja:
   an welcher Stelle (z. B. Einstellungen/„Mehr“ oder dezenter Hinweis beim
   ersten Besuch)?
   ANTWORT: Ja, die Installationshilfe soll beim ersten Besuch als dezenter Hinweis erscheinen.

4. Soll auf iOS ausdrücklich darauf hingewiesen werden, dass Safari-Spielstände
   möglicherweise nicht automatisch in die separat gestartete Home-Bildschirm-
   Web-App übernommen werden?
   ANTWORT: Ja, ein Hinweis soll angezeigt werden.

5. Ist das vorhandene Logo als endgültiges App-Motiv freigegeben, oder darf es
   für kleine Icon-Größen anhand der Vorlage vereinfacht/nachgezeichnet werden?
   ANTWORT: Das Logo darf für kleine Icon-Größen vereinfacht/nachgezeichnet werden.

6. Wer erstellt und gibt die finalen PNG-Symbole visuell frei, falls eine
   hochwertige Nachzeichnung nötig ist?
   ANTWORT: Die finalen PNG-Symbole werden von der Designabteilung erstellt und freigegeben.

7. Welche Geräte-/Betriebssystemversionen sollen als verbindliche
   Mindest-Zielversionen für die Abnahme gelten?
   ANTWORT: Die Mindest-Zielversionen sind iOS 15 und Android 12.
