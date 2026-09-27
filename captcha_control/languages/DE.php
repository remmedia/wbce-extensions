<?php
/**
 * captcha_control — DE.php
 *
 * @copyright WBCE Project (2015-)
 * @license   GNU GPL2 (or any later version)
 */

$module_name        = 'Captcha &amp; Spam-Schutz';
$module_description = 'Globale, durch installierbare Provider erweiterbare CAPTCHA- und Spam-Schutz-Verwaltung.';

// ── General ──────────────────────────────────────────────────────────────────
$CAPTCHA['HEADING']            = 'CAPTCHA & Spam-Schutz';
$CAPTCHA['HOWTO']              = 'Hier kann das ALTCHA Proof-of-Work Captcha und der Honeypot-Spamfilter konfiguriert werden. Beide Schutzmaßnahmen wirken seitenübergreifend ohne Benutzeraufwand.';
$CAPTCHA['CAPTCHA_TYPE']       = 'Captcha-Typ';
$CAPTCHA['CAPTCHA_EXP']        = 'ALTCHA ist ein selbst-gehostetes, datenschutzfreundliches Proof-of-Work-Captcha. Kein Drittanbieter erforderlich.';
$CAPTCHA['USE_SIGNUP_CAPTCHA'] = 'Captcha für Registrierungen';
$CAPTCHA['ADMIN_TOOLS']        = 'Admin-Tools';
$CAPTCHA['CONFIGURE_PROTECTION'] = 'Schutz konfigurieren';
$CAPTCHA['ACTIVE']             = 'Aktiv';
$CAPTCHA['AVAILABLE']          = 'Verfügbar';
$CAPTCHA['INSTALLED_PROVIDER'] = 'Installierter CAPTCHA-Anbieter';
$CAPTCHA['ACTIVE_PROVIDER']    = 'Aktiver Provider:';
$CAPTCHA['NO_PROVIDER_TITLE']  = 'Kein CAPTCHA-Provider installiert.';
$CAPTCHA['NO_PROVIDER_INFO']   = 'Bitte installieren Sie mindestens einen Provider. ALTCHA wird bei einer Store-Installation automatisch ergänzt.';
$CAPTCHA['PROVIDER_DEFAULT_INFO'] = 'Der ausgewählte CAPTCHA-Provider schützt die konfigurierten Bereiche vor automatisierten Anfragen.';
$CAPTCHA['LOGIN_CAPTCHA']      = 'CAPTCHA beim Login';
$CAPTCHA['LOGIN_AFTER_FAILURES'] = 'Nach fehlgeschlagenen Anmeldungen';
$CAPTCHA['LOGIN_ALWAYS']       = 'Immer';
$CAPTCHA['PASSWORD_RESET_CAPTCHA'] = 'CAPTCHA beim Zurücksetzen des Passworts';
$CAPTCHA['SAVING']             = 'Wird gespeichert …';
$CAPTCHA['SAVE_SUCCESS']       = 'Einstellungen wurden gespeichert.';
$CAPTCHA['SAVE_FAILED']        = 'Speichern fehlgeschlagen:';
$CAPTCHA['INVALID_RESPONSE']   = 'Ungültige Serverantwort';
$CAPTCHA['TWIG_FAILED']        = 'Die Twig-Kompatibilitätsschicht konnte das CAPTCHA-Template nicht laden.';
$CAPTCHA['TWIG_REQUIRED']      = 'Für diese WBCE-Version wird die Hook-Bridge mit Twig-Kompatibilität benötigt.';
$CAPTCHA['FALLBACK_PROVIDER_NAME'] = 'ALTCHA (Fallback)';
$CAPTCHA['FALLBACK_PROVIDER_DESCRIPTION'] = 'Eingebauter Notfallanbieter; installierte Provider-Module ersetzen ihn automatisch.';
$CAPTCHA['ENABLED'] = 'Aktiviert';
$CAPTCHA['DISABLED'] = 'Deaktiviert';
$CAPTCHA['NO_SESSION'] = 'Das Captcha benötigt eine aktive Sitzung.';
$CAPTCHA['LEAVE_EMPTY'] = 'Dieses Feld leer lassen';
$CAPTCHA['PRECHECK_HOOKS_REQUIRED'] = 'WBCE 1.7.0 oder Hook-Bridge 1.2.0';
$CAPTCHA['PRECHECK_HOOKS_LABEL'] = 'Hook-Schnittstellen';
$CAPTCHA['PRECHECK_AVAILABLE'] = 'Verfügbar';
$CAPTCHA['PRECHECK_UNAVAILABLE'] = 'Nicht verfügbar';

// ── Advanced Spam Protection ──────────────────────────────────────────────────
$CAPTCHA['ASP_LABEL']             = 'Erweiterter Spam-Schutz (Honeypot)';
$CAPTCHA['ASP_DESCRIPTION']       = 'Ein verstecktes Feld überführt Bots, die alle Eingabefelder automatisch ausfüllen. Kein Benutzeraufwand. Funktioniert unabhängig vom Captcha oben.';
$CAPTCHA['MODULES_SETTINGS_INFO'] = '<b>WICHTIG:</b> Einzelne Module wie <i>MiniForm</i>, <i>Guestbook</i> etc. haben ihre eigenen Einstellungen, ob Captcha im Formular des Moduls verwendet werden soll. <br><b>Bitte in den Einstellungen jeweiliger Module nachschauen</b>.';

// ── Widget-Anpassung ──────────────────────────────────────────────────────────
$CAPTCHA['WIDGET_HEADING']     = 'Widget-Anpassung';
$CAPTCHA['AUTO_LABEL']         = 'Start-Modus';
$CAPTCHA['AUTO_OFF']           = 'Manuell (Klick)';
$CAPTCHA['AUTO_ONLOAD']        = 'Automatisch';
$CAPTCHA['AUTO_ONSUBMIT']      = 'Bei Formular-Submit';
$CAPTCHA['DELAY_LABEL']        = 'Verzögerung';
$CAPTCHA['DELAY_HINT']         = 'ms Pause vor PoW-Start — erschwert automatisierte Angriffe';
$CAPTCHA['HIDEFOOTER']         = '„Powered by ALTCHA“-Footer ausblenden';
$CAPTCHA['HIDELOGO']           = 'ALTCHA-Logo ausblenden';
$CAPTCHA['COLOR_BRAND']        = 'Akzentfarbe';
$CAPTCHA['COLOR_BRAND_HINT']   = 'Spinner &amp; Rahmen';
$CAPTCHA['COLOR_SUCCESS']      = 'Check-Farbe';
$CAPTCHA['COLOR_BASE']         = 'Widget-Hintergrund';
$CAPTCHA['COLOR_CHECKBOX']     = 'Checkbox-Hintergrund';
$CAPTCHA['COLOR_TEXT']         = 'Textfarbe';
$CAPTCHA['BORDER_RADIUS']      = 'Eckenrundung';
$CAPTCHA['COLOR_DEFAULT']      = 'Vorgabewert';
$CAPTCHA['CORNER_SQUARE']      = 'Eckig';
$CAPTCHA['CORNER_LIGHT']       = 'Leicht';
$CAPTCHA['CORNER_ROUND']       = 'Rund';
$CAPTCHA['PREVIEW']            = 'Vorschau';
$CAPTCHA['WIDGET_FOOTER_TEXT'] = 'Geschützt durch ALTCHA';
$CAPTCHA['WIDGET_CHECK_TEXT']  = 'Ich bin kein Roboter';

// ── Kein HTTPS — Fallback ──────────────────────────────────────────────────────
$CAPTCHA['NO_HTTPS_HEADING']       = 'Keine Einstellungen verfügbar — HTTPS erforderlich';
$CAPTCHA['NO_HTTPS_INFO']          = 'ALTCHA benötigt eine Browser-Funktion (Web Crypto), die ausschließlich über HTTPS zur Verfügung steht. Diese Seite ist derzeit nur über einfaches HTTP erreichbar, wodurch das Widget seine Prüfung nie abschließen könnte — hier gibt es daher nichts einzustellen, bis ein SSL-Zertifikat installiert und die Seite auf HTTPS umgestellt wurde.';
$CAPTCHA['NO_HTTPS_FALLBACK']      = 'In der Zwischenzeit wird automatisch ein einfaches Rechen-Captcha (z.B. „3 + 5 = ?") verwendet, damit Formulare weiterhin geschützt bleiben.';
$CAPTCHA['VERIFICATION_INFO_RES']  = 'Bitte lösen Sie die Rechenaufgabe, um zu bestätigen, dass Sie kein Roboter sind.';
