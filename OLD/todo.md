# To-do

`✦` kennzeichnet Punkte, die nachträglich für die Release-Vorbereitung ergänzt wurden.

## Phase 1: Inhalte und redaktionelle Freigabe

### Impressum, Credits und Datenschutz vervollständigen ✦

Die aktuell hinterlegten Impressums- und Credit-Texte enthalten noch `example.invalid`-Adressen und Beispielangaben. Vor der Veröffentlichung durch korrekte, freigegebene Angaben ersetzen. Verantwortliche, Kontaktmöglichkeiten, Bildnachweise und Nutzungsrechte der verwendeten Fotos sowie die Angaben zur anonymen Statistik und deren Aufbewahrung prüfen und veröffentlichungsreif dokumentieren.

### Test- und Beispieldaten aus dem Produktivbestand entfernen ✦

Vor dem Livegang Datenbank und Admin-Bereich auf Musterinhalte prüfen. Insbesondere die drei offenen Beispielvorschläge und Beispiel-Kontaktdaten im Datenbank-Snapshot entfernen oder eindeutig durch redaktionell geprüfte Inhalte ersetzen. Sicherstellen, dass nur freigegebene Stationen samt Aufgaben- und Lösungsfotos veröffentlicht werden.

### Texte im Admin-Bereich bearbeiten

Alle Texte auf der Webseite (Datenschutz, PWA-Installation etc.) müssen im Admin-Bereich bearbeitbar sein. Dazu gehören auch Hinweise, Fehlermeldungen und Texte in Installations- und Bestätigungsdialogen. Die Inhalte sollen ohne Codeänderung gepflegt und nach dem Speichern auf der passenden Seite angezeigt werden; für noch nicht konfigurierte Texte soll es verständliche Standardtexte geben.

## Phase 2: Technische und betriebliche Absicherung

### Entwicklungsfunktionen entfernen oder abschalten

Entwicklungs- und Testfunktionen dürfen in der Produktionsumgebung weder in der Oberfläche erscheinen noch über öffentlich erreichbare Endpunkte nutzbar sein. Lokale Entwicklung und Tests sollen weiterhin wie bisher möglich sein.

### Produktive Admin-Zugänge einrichten ✦

Die in der Projektdokumentation aufgeführten Testkonten und Testpasswörter dürfen nicht produktiv verwendet werden. Für den Livebetrieb ausschließlich individuelle, starke Zugangsdaten einrichten, Testkonten deaktivieren oder entfernen und den Login sowie den erzwungenen Passwortwechsel mit den produktiven Einstellungen prüfen.

### Produktionsumgebung und Datensicherung abnehmen ✦

PHP 8+, PDO/SQLite und `fileinfo` auf dem Zielserver verifizieren. HTTPS, sichere Session-Cookies, Schreibrechte für Datenbank und Uploads sowie den Schutz dieser Verzeichnisse von außen prüfen. Vor dem Start einen Sicherungs- und Wiederherstellungsablauf für Datenbank und hochgeladene Bilder festlegen und eine Wiederherstellung testweise durchführen.

### `index.html` in CSS- und JavaScript-Dateien aufteilen

Inline-CSS und Inline-JavaScript in passende, externe Dateien auslagern und diese in `index.html` einbinden. Bestehende Reihenfolge und Abhängigkeiten erhalten sowie sicherstellen, dass Startseite, PWA-Funktionen und vorhandene Tests nach der Aufteilung unverändert funktionieren.

### Domain registrieren

Eine passende, verfügbare Domain für „Münster unvermutet“ auswählen und registrieren. Anschließend DNS auf die Anwendung konfigurieren, HTTPS aktivieren und Weiterleitungen für abweichende Schreibweisen bzw. die bevorzugte Domain einrichten; die Erreichbarkeit der Website und PWA unter der finalen Adresse prüfen.

## Phase 3: Spielablauf und Bedienbarkeit

### OSM-Karte während der Station anzeigen

Während die Station und die Frage angezeigt werden, bleibt die Karte geöffnet; nach dem Absenden der Antwort wird sie eingeklappt. Das Verhalten bei richtiger und falscher Antwort sowie beim Wechsel zur nächsten Station prüfen.

### Dateiname unter dem Lösungsfoto vermeiden

Unter dem Foto nur den vorgesehenen Beschreibungstext anzeigen; den technischen Dateinamen weder ergänzen noch als Ersatztext ausgeben. Das Verhalten auch prüfen, wenn kein Beschreibungstext hinterlegt ist.

### Animationen vereinheitlichen

Alle Animationen überprüfen und einheitlich gestalten. Übergänge, Dauer, Timing und Bewegungsrichtung für vergleichbare Aktionen abstimmen und abrupte oder unnötige Animationen beseitigen. Animationen auf Mobilgeräten testen und die Systemeinstellung für reduzierte Bewegung berücksichtigen.

### Übergang nach richtiger Antwort überarbeiten

Die Animation zur Ansicht des neuen Fotos und Textes ist immer noch nicht flüssig oder einheitlich; gegebenenfalls die Anzeige neu denken. Den gesamten Ablauf von der Antwortbestätigung bis zum neuen Foto samt Text betrachten und ruckartige, doppelte oder widersprüchliche Übergänge beseitigen. Eine konsistente Übergangsfolge festlegen und auf Mobilgeräten testen; bei Bedarf die Darstellung vereinfachen statt weitere Effekte hinzuzufügen.

### Fehlende Release-Tests ergänzen ✦

Die in `TEST-ABDECKUNG.md` als fehlend oder teilweise markierten Fälle für Vorschlags-API (CSRF und Rate-Limit), fehlerhafte Stationsdetails, ungültige externe Links, Kartenstatus und Filter sowie Orientierungskarte testen und bei relevanten Regressionen automatisierte Tests ergänzen. Tastaturbedienung und Fokus-Reihenfolge ebenfalls systematisch prüfen.

## Phase 4: Visuelle Überarbeitung und PWA

### Icon verbessern

Ein klares, zur Anwendung passendes Motiv und eine auch in kleinen Größen gut erkennbare Darstellung festlegen. Das Icon in den benötigten Varianten für Browser, PWA und Geräte bereitstellen und prüfen, dass es auf dem Startbildschirm und im Browser scharf und vollständig erscheint.

### Icons in Logos zentrieren

Die Ausrichtung für alle verwendeten Logos und Icons anhand ihrer sichtbaren Grafik prüfen, nicht nur anhand der Bildfläche. Einheitliche Ausrichtung in Header, Navigation und weiteren Ansichten auf Mobil- und Desktopgrößen sicherstellen.

### Header verbessern

Aufbau, Abstände, Ausrichtung und Größenverhältnisse von Logo, Titel und Navigation überarbeiten. Darstellung auf schmalen Mobilbildschirmen und breiten Desktop-Ansichten prüfen und sicherstellen, dass wichtige Inhalte nicht umbrechen, abgeschnitten werden oder mit Bedienelementen kollidieren.

## Phase 5: Veröffentlichung und Abnahme

### End-to-End-Abnahme auf der veröffentlichten Website durchführen ✦

Nach dem Deployment die Kernabläufe auf der endgültigen HTTPS-Adresse mit aktuellem iOS/Safari und Android/Chrome prüfen: Karte und Stationen laden, Standortfreigabe erteilen und verweigern, Antworten absenden, Spielstand nach Neuladen behalten, Installation der PWA testen und Vorschläge übermitteln. Dabei auch Kartenkacheln, Fotos, externe Links und verständliche Fehlermeldungen bei fehlender Verbindung kontrollieren.




 