<?php
return array(
    'module'=>array(
        'name'=>'Security Center',
        'description'=>'Firewall, Angriffserkennung, Sperrlisten, Überwachung, Auswertung und Codescanner für WBCE – vollständig lokal.',
        'intro'=>'Firewall, Sperrlisten, Angriffserkennung, Überwachung und Auswertung – vollständig lokal.'
    ),
    'nav'=>array('status'=>'Übersicht','protection'=>'Schutz','rules'=>'Sperren & Filter','scanner'=>'Scanner','virus_scanner'=>'Virenscanner','traffic'=>'Besucher','logs'=>'Protokolle','errors'=>'Error-Log','tools'=>'Werkzeuge'),
    'common'=>array('active'=>'Aktiv','inactive'=>'Inaktiv','cancel'=>'Abbrechen','confirm'=>'Bestätigen','confirm_action'=>'Aktion bestätigen','processing'=>'Wird ausgeführt …','loading'=>'Wird geladen …','saved'=>'Status gespeichert.','success'=>'Aktion erfolgreich ausgeführt.','queued'=>'Geplant','running'=>'Läuft','done'=>'Abgeschlossen','failed'=>'Fehlgeschlagen','started'=>'Gestartet'),
    'errors'=>array('secure_random'=>'Die sichere Konfigurationsfreigabe konnte nicht erzeugt werden.','runtime_write'=>'Die lokale Firewall-Konfiguration konnte nicht aktualisiert werden.','database_unavailable'=>'Die WBCE-Datenbankverbindung ist nicht verfügbar.'),
    'precheck'=>array('hooks_label'=>'Hook-Schnittstellen','hooks_required'=>'WBCE 1.7.0 oder Hook-Bridge 1.2.0','available'=>'Verfügbar','unavailable'=>'Nicht verfügbar'),
    'client'=>array(
        'page_missing'=>'Unterseite fehlt.','page_load_failed'=>'Unterseite konnte nicht geladen werden: {error}','updated_page_missing'=>'Aktualisierte Unterseite fehlt.',
        'updated_control_missing'=>'Aktualisierter Einstellungsblock fehlt.','control_update_failed'=>'Einstellungsblock konnte nicht aktualisiert werden.','action_failed'=>'Aktion fehlgeschlagen: {error}','status_failed'=>'Status konnte nicht gespeichert werden: {error}',
        'file_preview_missing'=>'Dateivorschau fehlt.','finding_row_missing'=>'Fundzeile fehlt.','file_preview_failed'=>'Datei konnte nicht angezeigt werden: {error}','loading'=>'Wird geladen …',
        'edit'=>'Bearbeiten','close'=>'Schließen','csv_exported'=>'Liste wurde als CSV exportiert.','excel_exported'=>'Liste wurde für Excel exportiert.','print_blocked'=>'Das Druckfenster wurde vom Browser blockiert.',
        'sha_unavailable'=>'SHA-256 wird von diesem Browser nicht unterstützt.','copied'=>'Kopiert','config_copied'=>'Konfigurationsblock wurde kopiert.','enter_suffix'=>': {label} eingeben.',
        'active_filters'=>'Aktive Filter: {filters}','traffic_missing'=>'Verkehrsdaten fehlen.','traffic_updated'=>'Zuletzt aktualisiert: {time}','traffic_failed'=>'Echtzeit-Aktualisierung fehlgeschlagen: {error}',
        'error_no_match'=>'Keine Meldung entspricht dem gewählten Filter.','error_empty'=>'Das Fehlerprotokoll ist leer.','error_updated'=>'Zuletzt automatisch aktualisiert: {time}','error_refresh_failed'=>'Automatisches Nachladen fehlgeschlagen: {error}','error_live_on'=>'Automatisches Nachladen ist aktiv.','error_live_off'=>'Automatisches Nachladen ist deaktiviert.',
        'scan_once_heading'=>'Einmalige Scanaufträge','virus_once_heading'=>'Einmalige Virenscans','export_title'=>'Security Center – Diagnose'
    ),
    'actions'=>array(
        'security_failed'=>'Die Sicherheitsprüfung ist fehlgeschlagen. Bitte die Seite neu laden.',
        'invalid_action'=>'Ungültige Aktion.',
        'logs_cleared'=>'Sicherheitsprotokolle gelöscht.','error_log_cleared'=>'PHP-Fehlerprotokoll geleert.',
        'php_enabled'=>'PHP-Einstellung wurde sofort aktiviert.','php_disabled'=>'PHP-Einstellung wurde sofort deaktiviert.',
        'protection_saved'=>'Schutzstatus gespeichert.','htaccess_saved'=>'Die .htaccess-Datei wurde mit Sicherungskopie gespeichert.',
        'diagnostic_queued'=>'Die Netzwerkdiagnose wurde an den Worker übergeben.','diagnostic_deleted'=>'Diagnoseauftrag gelöscht.',
        'code_scan_queued'=>'Codescan {id} wurde an den Worker übergeben.','virus_scan_queued'=>'Virenscan {id} wurde an den Worker übergeben.',
        'virus_scan_deleted'=>'Der geplante Virenscan wurde gelöscht.','scan_deleted'=>'Der geplante Scan wurde gelöscht.',
        'worker_scan_required'=>'Für Scan-Zeitpläne muss der Worker installiert sein.','worker_virus_required'=>'Für Virenscan-Zeitpläne muss der Worker installiert sein.',
        'default_scan_name'=>'Security Center Scan','default_virus_name'=>'Security Center Virenscan',
        'scan_schedule_saved'=>'Scan-Zeitplan gespeichert.','virus_schedule_saved'=>'Virenscan-Zeitplan gespeichert.',
        'virus_schedule_missing'=>'Virenscan-Zeitplan wurde nicht gefunden.','virus_schedule_deleted'=>'Virenscan-Zeitplan gelöscht.','virus_schedule_status_saved'=>'Virenscan-Zeitplanstatus gespeichert.',
        'scan_schedule_missing'=>'Scan-Zeitplan wurde nicht gefunden.','scan_schedule_deleted'=>'Scan-Zeitplan gelöscht.','scan_schedule_status_saved'=>'Zeitplanstatus gespeichert.',
        'virus_finding_saved'=>'Virenscanner-Fundstatus gespeichert.','finding_saved'=>'Fundstatus gespeichert.','findings_all_ignored'=>'{count} offene Funde wurden ignoriert.','findings_all_resolved'=>'{count} offene Funde wurden als erledigt markiert.',
        'bans_cleared'=>'Die Sperrliste wurde geleert.','ban_saved'=>'Sperre gespeichert und Laufzeitregeln aktualisiert.','address_unblocked'=>'Adresse wurde entsperrt.',
        'rule_saved'=>'Regel gespeichert.','rule_runtime_saved'=>'Regel und Laufzeitregeln aktualisiert.','rule_updated'=>'Regel aktualisiert.',
        'settings_saved'=>'Einstellungen gespeichert und von der Firewall übernommen.'
    ),
    'summary'=>array('open'=>'Offene Funde','critical'=>'Hohe Priorität','blocked'=>'Blockierte Angriffe','visits'=>'Besucherereignisse','findings'=>'Funde anzeigen','details'=>'Details anzeigen','open_log'=>'Protokoll öffnen','open_traffic'=>'Besucheranalyse öffnen','protections'=>'Schutzmodule','dashboard_critical'=>'kritische oder hoch priorisierte offene Funde.'),
    'worker'=>array(
        'scheduled_scan_label'=>'Security Center geplanter Scan','scheduled_scan_description'=>'Führt einen einmaligen oder regelmäßigen vollständigen Codescan aus.',
        'code_scan_label'=>'Security Center Codescanner','code_scan_description'=>'Prüft PHP-Dateien portionsweise auf gefährliche Codekombinationen.',
        'virus_scan_label'=>'Security Center Virenscanner','virus_scan_description'=>'Prüft Dateien portionsweise mit der lokalen PHP-Malware-Erkennung.',
        'scheduled_virus_scan_label'=>'Security Center geplanter Virenscan','scheduled_virus_scan_description'=>'Führt einen einmaligen oder regelmäßigen PHP-Virenscan aus.',
        'integrity_label'=>'Security Center Dateiüberwachung','integrity_description'=>'Erkennt neue, veränderte und gelöschte Dateien in kritischen Systempfaden.',
        'traffic_label'=>'Security Center Besucherprotokoll','traffic_description'=>'Übernimmt gepufferte Besucherdaten ohne den Seitenaufruf zu belasten.',
        'bot_label'=>'Security Center Bot-DNS-Prüfung','bot_description'=>'Verifiziert behauptete Suchmaschinen-Bots asynchron über Rückwärts- und Vorwärts-DNS.',
        'diagnostics_label'=>'Security Center Netzwerkdiagnose','diagnostics_description'=>'Führt begrenzte DNS-, Bot- und Portprüfungen außerhalb des Seitenaufrufs aus.',
        'notifications_label'=>'Security Center Benachrichtigungen','notifications_description'=>'Versendet wartende Sicherheitsmeldungen außerhalb des Seitenaufrufs.',
        'cleanup_label'=>'Security Center Protokollbereinigung','cleanup_description'=>'Entfernt abgelaufene Sicherheitsereignisse.'),
    'worker_runtime'=>array(
        'code_disabled'=>'Codescanner ist deaktiviert.','virus_disabled'=>'Virenscanner ist deaktiviert.','diagnostic_none'=>'Keine Netzwerkdiagnose ausstehend.',
        'diagnostic_encode_failed'=>'Das Diagnoseergebnis konnte nicht gespeichert werden.','diagnostic_done'=>'Netzwerkdiagnose #{id} abgeschlossen.','diagnostic_unknown'=>'Unbekannte Diagnoseart.','unavailable'=>'Nicht ermittelbar','dnsbl_ipv4'=>'DNSBL unterstützt derzeit nur IPv4.',
        'notifications_disabled'=>'E-Mail-Benachrichtigungen sind deaktiviert.','notifications_sent'=>'{count} Sicherheitsbenachrichtigungen versendet.','mail_subject'=>'WBCE Security Center: {severity}','mail_body'=>"Ereignis: {event}\nPriorität: {severity}\nPfad: {path}\nZeit: {time}",'cleanup_done'=>'Alte Sicherheits-, Diagnose- und Besucherdaten wurden bereinigt.',
        'secure_random'=>'Die sichere Auftragskennung konnte nicht erzeugt werden.','code_none'=>'Kein Codescan ausstehend.','code_result'=>'{files} Dateien geprüft, {findings} neue Funde.','code_path_invalid'=>'Ungültiger Scanpfad.','virus_none'=>'Kein Virenscan ausstehend.','virus_result'=>'{files} Dateien auf Malware geprüft, {findings} neue Funde.','virus_path_invalid'=>'Ungültiger Virenscanpfad.',
        'bot_none'=>'Keine Bot-DNS-Prüfung ausstehend.','bot_busy'=>'Der Bot-DNS-Puffer wird bereits verarbeitet.','bot_done'=>'{count} Bot-DNS-Prüfungen abgeschlossen.','traffic_none'=>'Keine neuen Besucherdaten.','traffic_busy'=>'Der Besucherpuffer wird bereits verarbeitet.','traffic_done'=>'{count} Besucherdatensätze übernommen.',
        'integrity_disabled'=>'Dateiüberwachung ist deaktiviert.','integrity_changed'=>'Datei wurde seit der letzten Integritätsprüfung verändert.','integrity_created'=>'Neue ausführbare Datei in einem sensiblen Pfad.','integrity_result'=>'{files} Dateien auf Änderungen geprüft, {changes} auffällig.'
    ),
    'scanner_findings'=>array('dynamic_eval'=>'Dynamische Codeausführung mit eval.','assert_code'=>'Mögliche dynamische Ausführung über assert.','encoded_payload'=>'Mehrstufig dekodierter oder entpackter Inhalt.','reversed_decoder'=>'Rückwärts geschriebener Decodername.','variable_function'=>'Variable Funktion mit dekodiertem Funktionsnamen.','process_execution'=>'Direkter Aufruf eines Systemprozesses.','obfuscated_chr'=>'Lange Verkettung numerischer ASCII-Zeichen.','hex_escapes'=>'Viele hexadezimale Escapes in einem String.','remote_include'=>'Dynamisches Einbinden einer externen Adresse.'),
    'errorlog'=>array('title'=>'Error-Log','severity'=>'Schweregrad','all'=>'Alle Meldungen','critical'=>'Kritisch/Exception','warning'=>'Warnung/Deprecated','info'=>'Information','search'=>'Protokoll durchsuchen','placeholder'=>'Text, Datei oder Meldung','colors'=>'Farbig darstellen','live'=>'Automatisch nachladen','clear'=>'Protokoll leeren','confirm_clear'=>'Soll das PHP-Fehlerprotokoll wirklich vollständig geleert werden?','unreadable'=>'Das konfigurierte PHP-Fehlerprotokoll ist nicht als lokale Datei lesbar.','no_match'=>'Keine Meldung entspricht dem gewählten Filter.','empty'=>'Das Fehlerprotokoll ist leer.','live_active'=>'Automatisches Nachladen ist aktiv.'),
    'markup'=>array(),
    'title'=>'Zugriff verweigert',
    'heading'=>'Diese Anfrage wurde blockiert',
    'message'=>'Das Security Center hat die Anfrage aus Sicherheitsgründen abgewiesen.',
    'reference'=>'Referenz',
    'back'=>'Zur vorherigen Seite'
);
