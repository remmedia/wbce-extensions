<?php
/**
 * This file provides the german translation of the multilingual array for this Tool
 * 
 * 
 * @author      Christian M. Stefan (Stefek)
 * @copyright   Christian M. Stefan
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */

// Deutsche Modulbeschreibung

$module_name        = 'Account- und Registrierungs-Einstellungen';
$module_description = 'Dieses AdminTool ist für die Einstellungen des Registrierungsvorgangs und der Accounts zuständig.';


////////////////////////////////////////////////////////////////////////////////////
//              GERMAN  LANGUAGE STRINGS FOR 'Tool Account Settings'              //
////////////////////////////////////////////////////////////////////////////////////

// Überschriften und Ausgabetexte
$TOOL_TXT['OVERVIEW_DESCRIPTION'] = 'Alle im System angemeldeten Benutzer.';
$TOOL_TXT['NO_USERS'] = 'Es sind keine Benutzer vorhanden.';
$TOOL_TXT['CREATE_USER'] = 'Neuen Benutzer anlegen';
$TOOL_TXT['PREFERENCES']          = 'Meine Einstellungen';

$TOOL_TXT['HEADING']    = 'UserBase Admin-Tool';
$TOOL_TXT['EDIT']       = 'User bearbeiten';
$TOOL_TXT['DELETE']     = 'User löschen';
$TOOL_TXT['NEW']        = 'Neuen User anlegen';

$TOOL_TXT['SAVE_SETTINGS'] = 'Einstellungen speichern';
$TOOL_TXT['SAVE_EMAIL']    = 'E-Mail speichern';
$TOOL_TXT['SAVE_PASSWORD'] = 'Passwort speichern';
$TOOL_TXT['SAVING']        = 'Wird gespeichert …';
$TOOL_TXT['ASYNC_SAVED']   = 'Einstellungen wurden gespeichert.';
$TOOL_TXT['ASYNC_FAILED']  = 'Einstellungen konnten nicht gespeichert werden.';
$TOOL_TXT['NAV_LABEL']     = 'Account-Einstellungen';
$TOOL_TXT['NOT_AVAILABLE'] = 'Nicht verfügbar';
$TOOL_TXT['VIEW_NOT_FOUND'] = 'Die angeforderte Ansicht ist nicht verfügbar.';
$TOOL_TXT['RESET_NEUTRAL'] = 'Wenn ein aktives Konto zu dieser Adresse gehört, wurde ein Link zum Zurücksetzen versendet.';
$TOOL_TXT['RESET_MAIL_SUBJECT'] = 'Passwort für %s zurücksetzen';
$TOOL_TXT['RESET_MAIL_BODY'] = "Hallo %s,\n\nüber den folgenden Link können Sie innerhalb einer Stunde ein neues Passwort festlegen:\n\n%s\n\nWenn Sie diese Anfrage nicht gestellt haben, können Sie diese E-Mail ignorieren.";
$TOOL_TXT['SECURE_TOKEN_FAILED'] = 'Es konnte kein sicherer Bestätigungsschlüssel erzeugt werden. Bitte versuchen Sie es später erneut.';

$TOOL_TXT['EXPORT']     = 'Export';
$TOOL_TXT['CLOSE']      = "Schliessen";
$TOOL_TXT['SAVE_PREFERENCES'] = "Einstellungen speichern";
$TOOL_TXT['USER_ID']    = 'User-ID';
$TOOL_TXT['GROUP']      = 'Gruppe'; 
$TOOL_TXT['GROUPS']     = 'Gruppen';
$TOOL_TXT['CONFIG']     = 'Konfiguration';
 
$TOOL_TXT['OVERVIEW']   = 'Benutzer-Übersicht';
$TOOL_TXT['USERLINK']   = 'Benutzer';
$TOOL_TXT['ACTIVATE']   = 'Aktivieren';
$TOOL_TXT['DEACTIVATE'] = 'Deaktivieren';


$TOOL_TXT['USER_CORE_ACTIVATED']    = 'aktiviert?';
$TOOL_TXT['MAIN_CONFIG']            = 'Haupt-Einstellungen';
$TOOL_TXT['USER_CORE_ACTIVE']       = 'User ist aktiviert';
$TOOL_TXT['USER_CORE_INACTIVE']     = 'User ist deaktiviert';
$TOOL_TXT['ACCOUNTS_CONFIG']        = 'Registrierungs- und Account-Einstellungen';
$TOOL_TXT['CONFIG_USER_DIR']        = 'Konfiguration für das <i>[root]/account/</i> Verzeichnis';
$TOOL_TXT['USERBASE_ACTIVE']        = 'User hat ein erweitertes Profil';
$TOOL_TXT['USERBASE_INACTIVE']      = 'User hat kein erweitertes Profil';

$TOOL_TXT['WARNING_USER_SELECTION'] = 'Fehler bei User-Auswahl!';

$TOOL_TXT['USER_DETAILS']           = 'Details zum Benutzer';
$TOOL_TXT['SEE_PROFILE']            = 'Profil betrachten &amp; bearbeiten';
$TOOL_TXT['EXTENDED_PROFILE']       = 'Erweitertes Profil';
$TOOL_TXT['SEARCH_EXTEND_ONLY']     = 'Nur Benutzer mit Extend';
$TOOL_TXT['LATEST_LOGIN']           = 'Zuletzt eingeloggt';
$TOOL_TXT['DATE_REGISTERED']        = 'Registriert am';

$TOOL_TXT['PLEASE_SELECT']          = 'Bitte wählen';
$TOOL_TXT['DETAILS_SAVED']          = 'Allgemeine Einstellungen wurden ge&auml;ndert';


$TOOL_TXT['HEADING_ERROR']          = 'Es ist ein Fehler aufgetreten';
$TOOL_TXT['GENERIC_ERROR_MESSAGE']  = 'Der Account wurde noch nicht bestätigt, ist bereits aktiv oder es wurden ungültige Daten übermittelt.';
$TOOL_TXT['CONTACT_ADMINISTRATOR']  = 'Bitte wenden Sie sich für weitere Unterstützung ggf. an den Administrator der Website.';

$MESSAGE['DISPLAY_NAME_EMPTY']       = 'Das Feld "Angezeigter Name" darf nicht leer sein.';
$MESSAGE['GDPR_AGREEMENT_MANDATORY'] = 'Es muss der Speicherung und Verarbeitung von Daten zugestimmt werden, um sich auf unserer Plattform anzumelden.';

$TEXT['REGISTER_THANKYOU']                   = 'Vielen Dank für Ihre Registrierung.';
$TEXT['REGISTER_CHECK_MAIL_ACTIVATION_USER'] = 'Bitte rufen Sie nun Ihre E-Mail ab und klicken Sie auf den Bestätigungslink, der Ihnen soeben per E-Mail zugesendet wurde.';
$TEXT['REGISTER_GENEREC_EMAIL_NOT_RECIEVED'] = 'Falls Sie die E-Mail noch nicht erhalten haben, warten Sie bitte noch einige Minuten bzw. prüfen Sie, ob die E-Mail irrtümlich als Spam behandelt wurde.';
$TEXT['REGISTER_LOGIN_SENT_TO_USER']         = 'Ihre Zugangsdaten wurden soeben an die angegebene E-Mail-Adresse gesendet.';
$TEXT['REGISTER_USER_ACTIVATED']             = 'Der Benutzeraccount wurde freigeschaltet. Die Anmeldedaten wurden an die angegebene E-Mail-Adresse versendet.';
$TEXT['REGISTER_ACTIVATION_PENDING']         = 'Bitte haben Sie noch etwas Geduld. Sie erhalten Ihre Zugangsdaten per E-Mail, sobald die eingegebenen Daten von uns geprüft und freigeschaltet wurden.';
$TEXT['REGISTER_GDPR_PHRASE']                = 'Die Datenschutzerklärung habe ich gelesen und akzeptiert. Mit der Speicherung und Verarbeitung der eingegebenen Daten bin ich einverstanden.';


/////////////////////////////////////////////////////////////////////////
//    Override language strings found in [WB_URL]/languages/DE.php     //
/////////////////////////////////////////////////////////////////////////
$MENU['PREFERENCES']    = 'Meine Daten';
$TEXT['ACCOUNT_SIGNUP'] = 'Registrierung';
$TEXT['SIGNUP']         = 'Registrierung';
