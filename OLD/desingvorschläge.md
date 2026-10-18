Hier ist der erweiterte Prompt. Er enthält nun eine komplett neue, dedizierte Sektion für Micro-Interaktionen und flüssige Animationen. Diese sind exakt so konzipiert, dass sie das haptische Feedback auf dem Smartphone verstärken und das Entdecker-Gefühl der Schnitzeljagd digital erlebbar machen.

UI/UX-Design-Briefing: „Münster unvermutet“ Mobile Redesign (inkl. Micro-Interaktionen)


🎯 Projektziel & Spielprinzip

Das Ziel ist das Redesign der Web-App „Münster unvermutet“ für die primäre Nutzung auf dem Smartphone (Mobile First). Das visuelle Design soll modern, clean und intuitiv sein – vergleichbar mit einer nativen App.
Das Layout muss das dreistufige Spielprinzip der Foto-Schnitzeljagd perfekt unterstützen:
1. Station wählen (Lokalisieren auf der Karte)
2. Detail erkennen (Visueller Abgleich zwischen Foto und Realität vor Ort)
3. Frage beantworten (Wissens- und Hintergrund-Rätsel lösen)

🎨 Allgemeine Design-Richtlinien (Modern Mobile App Look)

• Farbe & Kontrast: Ein minimalistisches, kontrastreiches Farbschema für optimale Lesbarkeit im Freien (Sonnenlicht). Basis: Reines Weiß (#FFFFFF) für UI-Elemente, ein tiefes Anthrazit für Texte. Als Primärfarbe für aktive Elemente dient ein modernes, sattes Münster-Rot oder ein dynamisches Digital-Blau. Ein frisches Smaragdgrün wird exklusiv für Erfolgsmomente (gelöste Stationen, Fortschritt) genutzt.
• Typografie: Verwendung einer modernen, serifenlosen System-Schriftart (z. B. Inter oder SF Pro) mit klaren Hierarchien und großzügigem Zeilenabstand.
• Formen: Abgerundete Ecken (border-radius: 16px bis 24px) für alle Karten und Buttons, um einen freundlichen, spielerischen Charakter zu erzeugen.
• Interaktion: Alle primären Interaktionen müssen in der „Thumb Zone“ (unteres Bildschirmdrittel) stattfinden, da das Spiel einhändig im Gehen bedient wird.

🏗️ Detaillierte UI/UX-Anforderungen nach Spielphasen


Sektion 1: Das Onboarding (Der Einstieg in die Jagd)

• Status Quo: Der Einstieg ist textlastig. Die drei Spielschritte stehen als graue Textboxen untereinander.
• Redesign-Anweisung:
	• Gestalte einen kompakten, modernen Header. Das Lupe-Emoji wird durch ein minimalistisches Logo ersetzt.
	• Die Spielanleitung („Pin antippen“, „Ort finden“, „Frage beantworten“) wird in eine horizontale, visuelle Kette umgewandelt. Jeder Schritt erhält ein klares, selbsterklärendes Icon statt einer reinen Zahl.
	• Barrierefrei- und Hilfe-Optionen wandern als dezente Icons in die obere rechte Ecke.

Sektion 2: Das Spielfeld (Karte & Lokalisierung)

• Status Quo: Die OpenStreetMap lädt in der Vorschau oft nicht; Fehlermeldungen sind reiner Fließtext.
• Redesign-Anweisung:
	• Die funktionierende Karte ist der Full-Screen-Hintergrund der App. Alle anderen UI-Elemente schweben als teiltransparente „Floating Cards“ (mit backdrop-filter: blur()) darüber.
	• Wenn die Karte nicht lädt (Fallback-Szenario), erscheint eine elegant gestaltete, hellblau hinterlegte Warnbox. Der Button „Spiel herunterladen & im Browser öffnen“ wird als prominenter, vollflächiger Call-to-Action (CTA) darunter platziert.
	• Die Pins auf der Karte zeigen den Spielstatus: Offene Stationen sind rot/blau, bereits gelöste Stationen wechseln zu einem grünen Häkchen-Symbol.

Sektion 3: Die Entdeckung (Foto-Abgleich & Rätselphase)

• Status Quo: Der Wechsel zwischen Bildsuche und Frage ist flach auf einer Standard-Webseite gelöst.
• Redesign-Anweisung:
	• Beim Tippen auf einen Pin schiebt sich von unten ein „Bottom Sheet“ (eine interaktive Karte) in den Bildschirm.
	• Ebene 1 (Das Suchen): Das Detailfoto steht im Fokus. Darunter befindet sich ein gut erreichbarer, unaufdringlicher Button für den „Hint“ (Hinweis).
	• Ebene 2 (Das Rätseln): Durch Wischen nach oben (Swipe-up) expandiert das Bottom Sheet und gibt die Quizfrage frei. Die Antwortoptionen sind große, leicht zu treffende Touch-Flächen (keine kleinen Radio-Buttons). Bei Auswahl der richtigen Antwort färbt sich die Fläche sofort grün, und es erscheint die Hintergrundgeschichte.

Sektion 4: Gamification & Fortschritt

• Status Quo: Der Spielfortschritt wird nur als Text oder rudimentär getrackt.
• Redesign-Anweisung:
	• Am oberen linken Bildschirmrand schwebt eine permanente, kreisförmige Fortschrittsanzeige (z. B. 🎯 4/12).
	• Sobald die letzte Station gelöst ist, blockiert die App das Spielfeld mit einem feierlichen Fullscreen-Erfolgs-Modal. Eine integrierte Konfetti-Animation belohnt den Nutzer. Die Buttons „Zur Karte zurück“ und „Nochmal spielen“ sind als gleichwertige, daumenfreundliche CTAs nebeneinander angeordnet.

Sektion 5: Community-Feature („Station vorschlagen“)

• Status Quo: Ein langes, technisches Formular mit unübersichtlichen Antwort-Eingaben ([-] 0) und händischer Koordinateneingabe.
• Redesign-Anweisung:
	• Wandle das Formular in einen Multi-Step-Wizard (Schritt-für-Schritt-Prozess) um, damit der Nutzer unterwegs nicht vom Text erschlagen wird.
	• Ersetze das unhandliche Kategorie-Dropdown durch moderne Filter-Chips zum Antippen.
	• Die manuelle Eingabe von Breitengrad und Längengrad fällt komplett weg. Stattdessen setzt der Nutzer den Punkt intuitiv per Fingertipp direkt auf einer interaktiven Minimap, welche die Koordinaten im Hintergrund automatisch ausliest.

🎬 NEU: Animationen & Micro-Interaktionen (Das haptische Spielgefühl)

Um den spielerischen Charakter (Gamification) zu verstärken, müssen alle Übergänge flüssig (transition: all 0.3s ease-in-out) gestaltet sein. Vermeide harte, abrupte UI-Wechsel.

1. Karten- & Pin-Interaktionen (Spielstufe 1: Wählen)

• Der „Radar-Effekt“: Unbespielte Stations-Pins auf der Karte pulsieren ganz leicht im Hintergrund (eine dezente, skalierende CSS-Aura), um die Aufmerksamkeit des Nutzers zu lenken.
• Sanfter Kamera-Fokus: Wenn ein Nutzer auf einen Pin tippt, animiert die Karte sanft (Smooth Scroll/Zoom), zentriert den Pin im oberen Bildschirmdrittel und blendet unbeteiligte Pins leicht aus (60% Opacity).
• Bottom-Sheet-Slide: Gleichzeitig gleitet das Bottom Sheet mit dem Detailfoto von der unteren Kante nach oben (transform: translateY(0)).

2. Das Enthüllen des Hinweises (Spielstufe 2: Erkennen)

• Der „Kratz- oder Blur-Effekt“: Wenn der Nutzer auf den „Hint“-Button tippt, wird der Text oder das Hilfsbild nicht einfach angezeigt. Es wird mit einem sanften „Fade-in“ und einem leichten Entspannungseffekt (filter: blur(10px) zu blur(0)) freigegeben. Das simuliert das haptische „Freirubbeln“ eines Hinweises.

3. Quiz-Feedback & Belohnung (Spielstufe 3: Rätseln)

• Haptisches Antwort-Feedback:
	• Falsche Antwort: Die angetippte Box wackelt zweimal kurz horizontal (CSS-Shake-Animation) und färbt sich dezent rot. Das signalisiert „Versuch es noch einmal“, ohne den Spielfluss komplett zu stoppen.
	• Richtige Antwort: Die Box pulsiert kurz auf, färbt sich smaragdgrün und die restlichen falschen Antworten fliegen nach unten aus dem Sichtfeld, während die geschichtliche Hintergrundinformation elegant nach oben aufrollt.
• Pin-Transformation: Auf der Karte verwandelt sich der gerade gelöste Pin in einer flüssigen Morph-Animation von einem roten/blauen Punkt in ein grünes Häkchen.

4. Fortschritts-Anpassung

• Die wachsende Progress-Bar: Wenn eine Station gelöst wird, füllt sich die Fortschrittsanzeige am oberen Bildschirmrand nicht abrupt. Der Balken oder Kreisring wandert in einer flüssigen Bewegung weiter nach vorne, begleitet von einer kurzen, diskreten Zahlen-Zähl-Animation (z. B. das Umspringen von 4 auf 5).
Möchtest du, dass wir für dieses Animations-Konzept direkt die passenden CSS Keyframes (z. B. für den Shake-Effekt oder das Pulsieren der Pins) erstellen, damit du sie direkt in dein Stylesheet kopieren kannst?
