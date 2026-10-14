# Globale Feature-Einstellungen

## Ziel und Umfang

Die Admin-Einstellungen sind für optionale Features gedacht, die bei Bedarf global
deaktiviert oder kontrolliert getestet werden müssen. Sie sind kein allgemeines
System, um beliebige Teile der Oberfläche abzuschalten.

Die reguläre Oberfläche ist statisch und immer verfügbar: Hilfe, Farbmodus,
Barrierefrei-Modus und Stationsliste, Fortschritt, Zurücksetzen, Kategorienfilter,
Hinweise, Lösung und Abschlussansicht. Diese Funktionen sind Bestandteile des
Spielablaufs oder persönliche Anzeigeoptionen und werden nicht zentral konfiguriert.

Aktuell ist nur `ui.suggest` registriert: „Station vorschlagen“. Das Feature kann
global an, aus oder im Testmodus sein. Ein Abschalten im Frontend ist keine
Zugriffskontrolle; der schreibende Endpoint prüft die Einstellung ebenfalls.

## Bestehende Infrastruktur

- `includes/settings.php` enthält `UI_REGISTRY` als Quelle der verfügbaren Flags,
  deren Labels, Gruppen, Beschreibungen und Standardwerte.
- `admin/settings.php` erzeugt die Admin-Auswahl aus dieser Registry.
- `api/settings.php` liefert die aufgelösten Werte als Booleans.
- Werte werden in der SQLite-Tabelle `settings` gespeichert. Fehlende oder
  ungültige Werte fallen auf den jeweiligen Registry-Standard zurück.
- Zulässige Zustände sind `off`, `on` und `test`.
- `?preview=key1,key2` beziehungsweise `?preview=all` aktiviert registrierte
  Test-Features für den aktuellen Aufruf. Eine Vorschau überschreibt weder `off`
  noch `on` und wird nicht gespeichert.
- Wenn die Einstellungs-API ausfällt, verwendet das Frontend seine Defaults
  (derzeit ist `suggest` standardmäßig aktiv).

## Ein zukünftiges Feature als Flag ergänzen

Nur wenn es einen konkreten Betriebs- oder Testbedarf gibt:

1. Das Feature in `UI_REGISTRY` ergänzen (PHP-Key `ui.feature_name`). Das
   Admin-Formular, die Validierung und die API-Antwort ergeben sich aus der
   Registry; eine Datenbankschemaänderung ist nicht nötig.
2. Den passenden Default unter `feature_name` in `UI_DEFAULTS` in `index.html`
   eintragen und das Flag mit `isEnabled('feature_name')` beim Initialisieren oder
   Rendern prüfen.
3. Markiere nur die betroffenen optionalen Elemente mit
   `data-ui-option="feature_name"`. Die Oberfläche darf bei nicht erreichbarer API
   nicht versehentlich unvollständig werden.
4. Falls das Feature serverseitig wirkt, prüfe dieselbe Einstellung am betroffenen
   Endpoint. Ein ausgeblendeter Button ist keine Sicherheitsmaßnahme.
5. Teste `off`, `on`, `test` mit und ohne passendem `preview`, API-Ausfall und
   Kombinationen mit erforderlichen Abhängigkeiten.

Eine Einstellung sollte ein zusammenhängendes Feature steuern und einen
nachvollziehbaren Zweck haben. Einzelne stabile Steuerelemente oder Inhalte sollen
nicht als globale Optionen aufgenommen werden.
