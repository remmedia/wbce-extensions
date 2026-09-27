<?php
/**
 * @category        modules
 * @package         Maintenance Mode
 * @author          WBCE Project
 * @copyright       Norbert Heimsath
 * @license         WTFPL
 */
 
// Module description 
$module_description = 'Im Wartungsmodus wird Besuchern der Seite im Frontend nur eine Baustellenseite angezeigt.';

// Language vars
$MOD_MAINTAINANCE['HEADER'] =      'Wartungsmodus';
$MOD_MAINTAINANCE['DESCRIPTION'] = 'Bei aktiviertem Wartungsmodus sehen Besucher die Wartungsseite statt der Website. Ein angemeldeter Superadministrator kann das normale Frontend weiterhin aufrufen. Die Seite lässt sich im aktiven Frontend-Template unter <code>systemplates/maintainance.tpl.php</code> anpassen.';
$MOD_MAINTAINANCE['CHECKBOX'] =    'Wartungsmodus aktivieren';
$MOD_MAINTAINANCE['ENABLED'] = 'Aktiviert';
$MOD_MAINTAINANCE['DISABLED'] = 'Deaktiviert';
$MOD_MAINTAINANCE['ENABLED_SAVED'] = 'Der Wartungsmodus wurde aktiviert.';
$MOD_MAINTAINANCE['DISABLED_SAVED'] = 'Der Wartungsmodus wurde deaktiviert.';
$MOD_MAINTAINANCE['SAVE_FAILED'] = 'Der Wartungsmodus konnte nicht geändert werden.';
$MOD_MAINTAINANCE['FORBIDDEN'] = 'Sie dürfen den Wartungsmodus nicht ändern.';
$MOD_MAINTAINANCE['SECURITY_ERROR'] = 'Das Sicherheitstoken ist ungültig oder abgelaufen.';
$MOD_MAINTAINANCE['INVALID_RESPONSE'] = 'Der Server hat keine gültige Antwort geliefert.';
$MOD_MAINTAINANCE['CONFIGURATION_UNAVAILABLE'] = 'Die WBCE-Konfiguration ist nicht verfügbar.';
$MOD_MAINTAINANCE['SAVE_NOT_CONFIRMED'] = 'WBCE hat die Änderung des Wartungsmodus nicht bestätigt.';
$MOD_MAINTAINANCE['INSTALL_FAILED'] = 'Die Einstellung für den Wartungsmodus konnte nicht angelegt werden.';
$MOD_MAINTAINANCE['UNINSTALL_FAILED'] = 'Die Einstellung für den Wartungsmodus konnte nicht entfernt werden.';
