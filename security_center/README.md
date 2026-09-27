# Security Center

## Notfall-Deaktivierung

Falls eine fehlerhafte Regel den Zugriff verhindert, kann per FTP oder Dateimanager
die leere Datei `temp/security-center-disable.flag` angelegt werden. Dadurch wird
die Request-Firewall vollständig übersprungen. Nach der Korrektur muss die Datei
wieder entfernt werden.

Eigenständige Neuentwicklung für WBCE 1.7; über Hook Bridge und Worker auch unter WBCE 1.6.8. PHP 8.2 oder neuer.

## Funktionsbereiche

- schneller lokaler Schutz gegen SQL-Injection, XSS, gefährliche Streams, Traversal und ausführbare Uploads
- Bot-, Crawler-, Referrer-, Inhalts- und Login-Schutz
- IP-, CIDR-, Browser-, Betriebssystem-, Provider-, Länder-, Referrer-, Wort- und Datei-Regeln
- Erlaubnislisten, Sperrlisten und einzeln schaltbare Regeln
- Ereignisprotokolle für Angriffe, Bots, Spam, Proxy-Regeln, Login, 2FA und Add-on-Aktionen
- gepuffertes Besucherprotokoll und lokale Auswertung
- inkrementeller Codescanner mit Fundverwaltung
- SHA-256-Überwachung neuer, geänderter und gelöschter Dateien
- Sicherheits-Header, System- und PHP-Konfigurationsprüfung
- Aufbewahrung, Stichprobe, Grenzwerte und Benachrichtigungsparameter

Der Request-Pfad liest eine kleine lokale JSON-Konfiguration. Besucherereignisse werden nur stichprobenartig gepuffert. Scan, Integritätsprüfung, Import und Bereinigung laufen im Worker. Es werden keine Server des externen Referenzprodukts aufgerufen. IP-Adressen werden nur als HMAC gespeichert.

## Formular- und Modul-Hooks

- `security.firewall.bypass`: erhält `false` und den Request-Kontext. Nur für einen vollständig selbst validierten Modulendpunkt `true` zurückgeben.
- `security.firewall.excluded_fields`: erhält eine Liste von Feldnamen und den Request-Kontext. Module können eigene, serverseitig validierte Formularfelder ergänzen, die nicht zusätzlich inhaltlich durch die Request-Firewall geprüft werden sollen.

Normale GET- und POST-Parameter werden unverändert an das Zielmodul übertragen. Die Hooks verändern nur die zusätzliche Security-Center-Prüfung. Authentifizierte Backend-Aufrufe einschließlich asynchroner Modulendpunkte aus dem Administrationsbereich werden von der Request-Blockierung ausgenommen; Login und Passwort-Zurücksetzen bleiben geschützt.

Abhängigkeiten: `wbce_hook_bridge >= 1.1.1` und `worker >= 1.10.3`.
