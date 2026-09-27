## 1.0.91

- Sammelaktionsformulare übertragen Priorität, Regel und Suchbegriff als eigene Felder; die Kriterien werden vor Bestätigung und Versand synchronisiert.

## 1.0.90

- Sammelaktionen werden ohne eindeutig übertragene gefilterte Auswahl sicher abgewiesen; leere Kriterien können keine Gesamtliste mehr verändern.

## 1.0.89

- Die Rückmeldung einer Sammelaktion zeigt die Zahl der tatsächlich sichtbaren und verarbeiteten Funde.

## 1.0.88

- Sammelaktionen arbeiten serverseitig mit den aktiven Filterkriterien und erfassen damit zuverlässig alle angezeigten offenen Funde.

## 1.0.87

- Gefilterte Fund-IDs werden bei Sammelaktionen zusätzlich als robuste, serverseitig geprüfte Liste übertragen.

## 1.0.86

- Sammelaktionen verarbeiten zuverlässig alle aktuell gefilterten offenen Funde.
- Filter für Priorität, Regel und Suche bleiben nach Einzelaktionen erhalten.

## 1.0.85

- Scanner-Aktionen für ausgewählte offene Funde setzen den Sicherheits-Token bei asynchronen Anfragen wieder zuverlässig.

# Änderungen

## 1.0.80

- Gültig authentifizierte Anfragen an den eigenen Backup-Server werden bereits in der Firewall erkannt. Die JSON-API hängt damit nicht mehr von der Startreihenfolge der Module ab.

## 1.0.63

- Der Hinweis auf kritische Dashboard-Funde erscheint wieder oben als gerahmte Karte.

## 1.0.56

- Name und ausführliche Beschreibung als deutsche und englische Modulmetadaten ergänzt.
- Aktiver Menüpunkt und Aktionsschaltflächen bleiben unabhängig vom Admin-Template kontrastreich sichtbar.
- Deutsche Seiteninhalte werden nicht mehr durch die englische Ersetzungstabelle übersetzt.
- Sprachcodes mit Regionsangabe wie `de-DE` werden korrekt als Deutsch erkannt.

## 1.0.51

- WBCE 1.7.0 ist nun die primäre Zielplattform; der Hook-Bridge-Fallback für WBCE 1.6.8 bleibt erhalten.
- Verwaltungsanfragen bleiben während der frühen Firewall-Phase nutzbar, während IP-Sperren, Uploadprüfung, Login-Limitierung und Sicherheitsheader weiter greifen.
- Der Fehler bei fehlender Datenbankverbindung wird vollständig lokalisiert ausgegeben.

## 1.0.39

- Helle Einzelrahmen, Schatten und Abstände innerhalb der Quellcodevorschau entfernt; nur der äußere Fensterrand bleibt erhalten.

## 1.0.38

- Korrigierter CSS-Selektor stellt nun auch jede einzelne Quellcodezeile einschließlich Zeilennummer und Codefläche dunkel dar.

## 1.0.37

- Die Quellcodeanzeige unter „Scanner → Offene Funde“ verwendet unabhängig vom Admin-Template einen schwarzen, kontrastreichen Hintergrund.

## 1.0.36

- gespeicherte PHP-Laufzeitwerte werden nach den WBCE- und Modulüberschreibungen in der normalen Initialisierungsphase erneut angewendet
- Sessiondirektiven bleiben auf die frühe Initialisierung begrenzt
- die Diagnose kennzeichnet gespeicherte, aber tatsächlich abweichende PHP-Werte ausdrücklich als noch nicht wirksam

## 1.0.35

- Schutzformular-Signaturen werden nicht länger als eigener Angriff fehlinterpretiert; insbesondere lässt sich die Proxy-/VPN-Erkennung ohne HTTP 403 aktivieren.
- Die Ausnahme gilt nur für die validierten Security-Center-Einstellungsaktionen; andere Backend-Formulare bleiben inhaltlich geschützt.

## 1.0.34

- mehrsprachige, gestaltbare Firewall-Sperrseite mit deutschen und englischen Sprachdateien, automatischer Sprachwahl, Farbschema, Vorschau und optionaler Referenznummer
- lokaler Worker-Portscanner mit auswählbaren wichtigen Diensten und verständlicher Offen-/Geschlossen-Ausgabe
- Diagnoseexport als CSV, Excel-kompatible XLS-Datei sowie druckoptimierte PDF-Ausgabe
- interne Admin-Steuerfelder von der Inhaltsfilterung getrennt, ohne frei eingegebene Formulardaten vom Schutz auszunehmen
- dauerhafte PHP-Laufzeiteinstellungen einschließlich Reparatur zuvor fehlerhaft verschachtelter Werte

## 1.0.31

- XSS-, SQL-Injection-, Pfad-, Stream- und Uploaderkennung funktional in einzeln schaltbare Prüfungen aufgeteilt.
- GET-, POST-, Cookie-, Rohdaten- und Pfadprüfung separat konfigurierbar.
- Eigene Aktionsmodi für XSS, SQLi, Pfadschutz, Uploadschutz und Anmeldebegrenzung.
- Verwaltbare Sperrliste mit manuellen und automatischen Einträgen, Ablaufzeit, Status und Notiz.
- Einzelnes Entsperren, Bearbeiten, vollständiges Leeren und CSV-Export für Sperren und Regeln.

## 1.0.27

- einmalige Code- und Virenscans zeigen „Geplant“, „Läuft“, „Abgeschlossen“ oder „Fehlgeschlagen“ sowie die vorhandene Startzeit eindeutig an
- laufende Scanaufträge besitzen keine Löschaktion; serverseitig bleiben ausschließlich wartende Aufträge löschbar
- `display_startup_errors` verwendet ebenfalls einen Schieberegler
- `phar.require_hash` und `phar.readonly` werden korrekt als nicht zur PHP-Laufzeit änderbar dargestellt
- die Ansicht „Offene Funde“ ist kompakter und begrenzt ihre Tabellenbreite

## 1.0.26

- Adminbereich bleibt gegen manipulierte Requests, XSS, SQL-Injection, Pfadangriffe und gefährliche Uploads geschützt
- automatische Reputations- und Zeitsperren können nicht mehr den gesamten geschützten Backendbereich blockieren
- Besucher-IP wird hinter vertrauenswürdigen oder lokalen Reverse Proxys korrekt aufgelöst; fremde Forwarded-Header bleiben wirkungslos
- Firewall-Ereignisse, Loginbegrenzung und Besucherprotokoll verwenden dieselbe aufgelöste Clientadresse
- aktive temporäre Sperren können unter „Sperren, Ausnahmen und Filter“ nach Overlay-Bestätigung aufgehoben werden

## 1.0.25

- Firewall gegen die Testinstallation mit sauberen sowie SQLi-, XSS-, Wrapper- und Pfadmanipulationsanfragen geprüft
- mehrstufige URL- und HTML-Entity-Kanonisierung vor den Erkennungsregeln ergänzt
- ausgewogene SQLi-Erkennung um boolesche, gestapelte, kommentierte und weitere zeitbasierte Varianten erweitert
- XSS-Erkennung um Ereignisattribute beliebiger Elemente und gefährliche URL-Attribute erweitert
- JSON-, XML- und Text-Request-Bodies, Cookies, Request-Pfade und Parameternamen in die begrenzte Prüfung aufgenommen
- getarnte PHP- und native Programmdateien bereits während des Uploads erkannt
- bei offenen Codescanner-Funden die Aktion „Anzeigen“ ergänzt
- Datei erst beim Klick sicher und schreibgeschützt innerhalb des WBCE-Verzeichnisses laden
- scrollbares Syntaxfenster automatisch an der markierten Fundzeile positionieren
- Binärdateien und Vorschauen über vier MB abweisen

## 1.0.24

- Virenscanner aus der Scanner-Seite in eine eigene Unterseite verschoben
- neuen Navigationspunkt „Virenscanner“ direkt nach „Scanner“ ergänzt
- Einstellungen, einmalige Aufträge, Zeitpläne und Funde vollständig auf der neuen Unterseite gebündelt
- Schutzkachel der Übersicht direkt mit der Virenscanner-Unterseite verknüpft
- asynchrone Virenscanner-Aktionen behalten die neue Unterseite bei

## 1.0.23

- Navigationspunkt und Seitenüberschrift „Fehlerüberwachung“ in „Error-Log“ umbenannt

## 1.0.22

- eigenständigen, vollständig lokalen PHP-Virenscanner ergänzt
- Erkennung von getarntem PHP-Code, Doppelendungen, nativen Programmen im Webverzeichnis, Webshell-Kombinationen, Decoder-Ausführungsketten, verschleiertem JavaScript, unsichtbaren Fremd-Iframes und hochentropischen Payloads
- begrenzte ZIP-Inhaltsprüfung ohne externe Programme oder Dienste
- eigene Datenbanktabellen für Virenscan-Aufträge und Malware-Funde
- einmalige und regelmäßige Virenscans über die vorhandene Worker- und Cron-Planung
- portionierte Verarbeitung mit atomarem Sperrcode gegen parallele Doppelverarbeitung
- eigene Virenscanner-Einstellungen, Auftragslisten, Zeitpläne und Fundbearbeitung in der Scanner-Unterseite
- Malware-Funde in die Kennzahlen und Schutzmodule der Übersicht integriert
- asynchrone Schalteraktualisierung zusätzlich nach Einstellungsbereich und Auftrags-ID abgesichert

## 1.0.21

- Live-Verkehr aktualisiert die Tabelle alle drei Sekunden asynchron
- noch nicht vom Worker importierte Puffereinträge erscheinen unmittelbar in der Live-Ansicht
- AJAX-, Worker-, Cron-, Webcron- und Dispatcher-Aufrufe werden nicht als Besucherverkehr protokolliert
- Besucherfilter und Live-Verkehr werden seitenbreit untereinander dargestellt
- Einmalige Scanaufträge und weitere Scannerbereiche erhalten die volle Seitenbreite

## 1.0.20

- ausgeschaltete PHP-Schieberegler übertragen zuverlässig den Wert `0`
- betroffene PHP-Konfigurationszeile wird nach dem Speichern gezielt aktualisiert, ohne Seite oder Abschnitt neu zu laden
- Scrollposition und geöffnete Bereiche bleiben bei Schalteränderungen erhalten
- PHP-Konfigurationsprüfung wird ohne eigenen Scrollcontainer dargestellt
- Erfolgsmeldung nennt unmittelbar den tatsächlich gewählten Aktivierungszustand

## 1.0.19

- `.htaccess-Editor` auf der Werkzeugseite vor das Hash-Werkzeug verschoben
- PHP-Informationen bei verfügbarer `phpinfo()` geschützt, ausklappbar und scrollbar ergänzt
- Reihenfolge und PHP-Informationsansicht durch zusätzliche Modultests abgesichert

## 1.0.18

- Fehlerüberwachung aus „Werkzeuge“ in eine eigene Unterseite verschoben
- neue Unterseite steht unmittelbar vor „Werkzeuge“ in der Navigation
- Live-Nachladen, Filter, Farbsteuerung und Löschen bleiben vollständig erhalten

## 1.0.17

- Fehlerüberwachung lädt standardmäßig alle fünf Sekunden asynchron nach
- automatisches Nachladen über einen Schieberegler ein- und ausschaltbar
- Protokolle chronologisch mit ältesten Einträgen oben und neuesten unten
- Ansicht scrollt beim Öffnen und nach jedem automatischen Abruf zum neuesten Eintrag
- Filter, Suche und Farbdarstellung bleiben beim Nachladen erhalten

## 1.0.16

- kopierfertige Sicherheitsempfehlungen für `php.ini`, PHP-FPM-Pools und Apache/mod_php
- nur nicht zur Laufzeit veränderbare PHP-Direktiven werden in den Servervorschlägen aufgeführt
- aktuell unsichere Direktiven werden unter den Konfigurationsblöcken gesondert aufgelistet
- jeder Vorschlag steht in einem schreibgeschützten Textfeld und besitzt einen eigenen Kopierknopf
- PHP-Schalter werden zuerst zur Laufzeit angewendet und geprüft; nur erfolgreiche Änderungen werden gespeichert
- Schalter und aktueller Wert werden nach der asynchronen Aktion aus dem tatsächlich wirksamen `ini_get()`-Wert neu aufgebaut

## 1.0.15

- Schalterzustand bildet den tatsächlichen PHP-Wert ab, unabhängig von der Sicherheitsempfehlung
- `0`, `off`, leer und `false` werden als „Deaktiviert“, `1`, `on`, `true` und `yes` als „Aktiviert“ dargestellt
- `display_startup_errors` besitzt ein eindeutiges Auswahlfeld „Aktiviert/Deaktiviert“
- Sicherheitsbewertung bleibt getrennt in der Spalte „Ergebnis“ sichtbar
- bei nicht veränderbaren Direktiven zeigt die Aktionsspalte ausschließlich „Nicht zur PHP-Laufzeit änderbar“

## 1.0.14

- `.user.ini` als unzuverlässige Abhängigkeit der PHP-Schalter entfernt
- veränderbare PHP-Werte werden im Security Center gespeichert und bei jedem Aufruf frühzeitig per `ini_set()` angewendet
- Schalter nur noch für in der aktuellen PHP-Laufzeit tatsächlich veränderbare Direktiven
- Spalte eindeutig als „Aktiviert“ beschriftet; Zustand zusätzlich als Text sichtbar
- nicht zur Laufzeit veränderbare Direktiven bleiben reine Diagnosewerte ohne Schalter

## 1.0.13

- verwaltbare PHP-Sicherheitseinstellungen als asynchrone Schieberegler dargestellt
- Änderungen werden dauerhaft in `.user.ini` gespeichert und, soweit PHP dies erlaubt, zusätzlich sofort per Laufzeitkonfiguration angewendet
- ausstehende Übernahme durch den PHP-Konfigurationscache wird je Einstellung sichtbar gekennzeichnet
- Reglerzustand berücksichtigt bereits gespeicherte Werte auch vor der serverseitigen PHP-Neuladung

## 1.0.12

- Selbstdeinstallation wird nicht mehr nach dem Löschen der eigenen Tabellen protokolliert
- Einstellungen und Ereignisprotokollierung prüfen ihre Tabellen vor jedem Zugriff
- fehlende Tabellen während Installation, Update oder Deinstallation führen nicht mehr zu Datenbank-Folgefehlern
- `.htaccess`-Editor über die gesamte Seitenbreite mit Syntaxhervorhebung direkt im Eingabebereich

## 1.0.11

- asynchrone Modulaufrufe aus dem eigenen Adminbereich werden vor der Request-Firewall freigegeben
- Host und Adminpfad des Referrers werden geprüft; die normale WBCE-Anmeldung und Rechteprüfung des Zielskripts bleibt aktiv
- wiederkehrende 403-Rückmeldungen auf sämtlichen Adminunterseiten beseitigt

## 1.0.10

- vorgelagerter Fail-safe: sämtliche geschützten Backendpfade werden bereits vor dem Laden der Firewall freigegeben
- öffentliche Login- und Passwort-Reset-Pfade bleiben weiterhin durch die Firewall geschützt
- lokaler Not-Aus über `temp/security-center-disable.flag`, ohne das Modul deinstallieren zu müssen

## 1.0.9

- Backend-Ausnahme anhand des konfigurierten Admin-Pfads statt eines festen `/admin`-Pfads
- authentifizierte asynchrone Admin-Endpunkte installierter Module sicher einbezogen
- Bot-, Fake-Bot- und Spam-Unteroptionen vollständig in Firewall und Worker verdrahtet
- Sicherheitsprotokolle zusätzlich zur vorhandenen Filterung nach Overlay-Bestätigung löschbar
- Frontend-Modulparameter bleiben unverändert; dokumentierte Hooks erlauben gezielte Prüfausnahmen

## 1.0.8

- gesamter authentifizierter Administrationsbereich einschließlich Admin-Modul-AJAX vor Firewall-Selbstblockade geschützt
- Login und Passwort-Zurücksetzen bleiben weiterhin vollständig geschützt
- detaillierte Unteroptionen für schädliche Bots, eigene Bot-Regeln und integrierte Scanner-Signaturen
- Google- und Bing-Botprüfung getrennt schaltbar und Cache-Dauer konfigurierbar
- Spam-Aktion und Mindestwortlänge für Wiederholungserkennung ergänzt
- Sicherheitsprotokolle nach Overlay-Bestätigung vollständig löschbar
- Hooks für selbst validierte Modulendpunkte und einzelne vertrauenswürdige Formularfelder ergänzt
- normale Frontend-Modulparameter werden unverändert an das Zielmodul weitergegeben

## 1.0.7

- PHP-Fehlerprotokoll nach Schweregrad und freiem Suchtext filterbar
- farbliche Kennzeichnung für kritische Meldungen, Warnungen und Informationen
- Farbdarstellung abschaltbar; Auswahl wird lokal im Browser gespeichert
- beschreibbare lokale Fehlerprotokolle nach Overlay-Bestätigung sicher leerbar
- leerer und nicht lesbarer Protokollstatus eindeutig unterschieden

## 1.0.6

- Selbstsperre der Security-Center-Verwaltung durch Proxy-, Browserkennungs- und Inhaltsregeln verhindert
- Proxy-/VPN-Erkennung um vertrauenswürdige Proxys, konfigurierbare Signalheader und Signalwerte ergänzt
- Unteraktionen für fehlende Browserkennung sowie Trefferart und Schreibweise des Inhaltsfilters ergänzt
- Bot-Aktion gilt konsistent auch für erkannte Sicherheitsscanner
- Besucheranalyse als kombinierbarer Filter für den Live-Verkehr umgesetzt
- Scannerblöcke neu ausgerichtet und Zeitplanmodus als Auswahlkarten gestaltet
- Benachrichtigungsintervall wird auch beim Speichern der Schutzseite an den Worker übertragen
- Diagnosewerte für deaktivierte PHP-Optionen korrekt normalisiert und farblich eindeutig dargestellt
- gezielte `.user.ini`-Korrektur für zur Laufzeit lokal änderbare PHP-Einstellungen ergänzt
- Fehlerprotokollzeiten vollständig in die konfigurierte WBCE-Zeitzone umgerechnet
- `.htaccess`-Editor zeigt Pfad und Dateistatus und besitzt eine hervorgehobene Syntaxvorschau
- Übersichtskennzahlen führen direkt zu den zugehörigen Detailseiten

## 1.0.5

- Update aus älteren, im selben PHP-Prozess bereits geladenen Service-Versionen abgesichert
- Schema-Nachzug erst aufrufen, wenn die neue Methode tatsächlich verfügbar ist

## 1.0.4

- fehlende Diagnose- und Bot-Cache-Tabellen nach unvollständigen Updates selbstheilend anlegen
- Upgrade-Routine für die neuen Tabellen zusätzlich abgesichert

## 1.0.3

- Kartenanordnung der Seiten Schutz, Scanner und Besucher optimiert
- ausgewogene zweispaltige Desktopansicht und saubere einspaltige Mobilansicht
- Scanaufträge und Zeitpläne nebeneinander, offene Funde über die volle Breite
- Besuchereinstellungen, Analyse und Live-Verkehr klar voneinander getrennt

## 1.0.2

- Schutzmodule lassen sich direkt in den Kacheln der Startseite einzeln ein- und ausschalten
- Schalter auf den jeweiligen Einstellungsseiten speichern sofort asynchron
- Schnellschalter verändern ausschließlich die ausgewählte Schutzfunktion
- leere Werte der konfigurierbaren Sperrseite korrigiert

## 1.0.1

- sämtliche Hauptbereiche als asynchron geladene Unterseiten
- detaillierte und tatsächlich wirksame Schutz-, Benachrichtigungs- und Scanneroptionen
- wiederkehrende und einmalige Scan-Zeitpläne mit lokalisierter Zeitausgabe
- automatische temporäre Sperren sowie vertrauenswürdige Länder- und Providerheader
- asynchrone Fake-Bot-, IP-, DNSBL- und Portdiagnosen über den Worker
- Besucheranalyse, Warnseitengestaltung und abgesicherter `.htaccess`-Editor
- Worker-Ausführungsfehler und hängen gebliebene Sperren werden protokolliert

## 1.0.0

- eigenständige Neuimplementierung als Security Center im Verzeichnis `security_center`
- Firewall, Regelverwaltung, Sperr- und Erlaubnislisten, Protokolle und Besucheranalyse
- Codescanner und automatische Integritätsüberwachung
- zeitaufwendige Aufgaben in den Worker ausgelagert
- robuste Installation und Deinstallation auch aus dem Store-Kontext
- WBCE 1.7 als Basis, kompatibel mit WBCE 1.6.8 über Hook Bridge
