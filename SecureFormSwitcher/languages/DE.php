<?php
/**
 * @category        modules
 * @package         Maintainance Mode
 * @author          WBCE Project
 * @copyright       Luisehahne, Norbert Heimsath
 * @license         GPLv2 or any later.
 */

//Module description
$module_description = 'Einige Zusatzeinstellungen für die Sicherheit';
$SFS['MODULE_NAME'] = 'Weitere Sicherheitseinstellungen';
$SFS['MODULE_DESCRIPTION'] = 'Zusätzliche Einstellungen für sichere Sitzungen und Formular-Token.';
$SFS['UNINSTALL_FAILED'] = 'Die Sicherheitseinstellungen konnten nicht entfernt werden.';

$SFS['HEADER'] = 'Extra Sicherheitseinstellungen ';
$SFS['DESCRIPTION'] = 'Hier können Sie weitere Sicherheitseinstellungen vornehmen';

// Backend variables
$SFS['SUBMIT'] = 'Speichern';
$SFS['RESET_SETTINGS'] = 'Standardeinstellung';
$SFS['ON_OFF'] = 'Ein/Aus';

// Variablen fuer AdminTool Optionen
$SFS['USEIP'] = 'IP-Blocks (1-4, 0=kein Check)';
$SFS['USEIP_TTIP'] = '<em>Hilfe</em>
Diese Anzahl der Segmente einer IP-Adresse wird für den Fingerprint genutzt, d. h. bei „4“ die gesamte IP-Adresse. „2“ ist für wechselnde Privatanschlüsse meist ein guter Kompromiss.
<ul>
<li>4= xxx.xxx.xxx.xxx</li>
<li>3= xxx.xxx.xxx</li>
<li>2= xxx.xxx</li>
<li>1= xxx</li>
<li>0=keine Nutzung der IP</li></ul>';
$SFS['USEIP_ERR'] = 'Oktette dürfen nur Werte von 0 bis 4 enthalten.';

$SFS['TOKENNAME'] = 'Tokenname [a-zA-Z] 5-20 Zeichen ';
$SFS['TOKENNAME_TTIP'] = '<em>Hilfe</em>Der interne Name des Formular-Tokens.';
$SFS['TOKENNAME_ERR'] = 'Der Tokenname darf nur Buchstaben enthalten und muss 5 bis 20 Zeichen lang sein.';

$SFS['SECRET'] = 'Secret [a-zA-Z0-9] 20-60 Zeichen';
$SFS['SECRET_TTIP'] = '<em>Hilfe</em>Ein zufälliger Schlüssel für die Token-Erstellung. Empfohlen sind mindestens 20 Zeichen.';
$SFS['SECRET_ERR'] = 'Das Secret darf a-zA-z0-9 enthalten und muss zwischen 20 und 60 Zeichen lang sein';

$SFS['SECRETTIME'] = 'Secrettime [0-9] 1-5 Zeichen';
$SFS['SECRETTIME_TTIP'] = '<em>Hilfe</em>Zeit in Sekunden, bis sich der geheime Schlüssel erneuert.';
$SFS['SECRETTIME_ERR'] = 'Secrettime darf nur Zahlen enthalten und max. 5 Stellen lang sein';

$SFS['TIMEOUT'] = 'Session/Token Timeout [0-9] 1-5 Zeichen';
$SFS['TIMEOUT_TTIP'] = '<em>Hilfe</em>Zeit in Sekunden, bis Formular-Token und Sitzung ihre Gültigkeit verlieren.';
$SFS['TIMEOUT_ERR'] = 'Timeout darf nur Zahlen enthalten und max. 5 Stellen lang sein';

$SFS['USEFP'] = 'Fingerprinting';
$SFS['USEFP_TTIP'] = '<em>Hilfe</em>Zusätzlich zur IP-Adresse werden Betriebssystem und Browser in die Token-Prüfung einbezogen.';
$SFS['USEFP_Err'] = '';
$SFS['SHOW_SECRET'] = 'Anzeigen';
$SFS['HIDE_SECRET'] = 'Ausblenden';
$SFS['CANCEL'] = 'Abbrechen';
$SFS['RESET_CONFIRM'] = 'Alle Sicherheitseinstellungen auf die Standardwerte zurücksetzen?';
$SFS['SAVED'] = 'Die Sicherheitseinstellungen wurden gespeichert.';
$SFS['DEFAULTS_SAVED'] = 'Die Standardwerte der Sicherheitseinstellungen wurden wiederhergestellt.';
$SFS['SAVE_FAILED'] = 'Die Sicherheitseinstellungen konnten nicht gespeichert werden.';
$SFS['INVALID_SETTINGS'] = 'Mindestens ein Wert ist ungültig. Bitte prüfen Sie die markierten Felder.';
$SFS['FORBIDDEN'] = 'Sie dürfen diese Einstellungen nicht ändern.';
$SFS['SECURITY_ERROR'] = 'Das Sicherheitstoken ist ungültig oder abgelaufen.';
$SFS['INVALID_RESPONSE'] = 'Der Server hat keine gültige Antwort geliefert.';
$SFS['CONFIGURATION_UNAVAILABLE'] = 'Die WBCE-Konfiguration ist nicht verfügbar.';
$SFS['SAVE_NOT_CONFIRMED'] = 'WBCE hat nicht alle gespeicherten Einstellungen bestätigt.';
$SFS['RANDOM_FAILED'] = 'Der sichere Zufallsschlüssel konnte nicht erzeugt werden.';
