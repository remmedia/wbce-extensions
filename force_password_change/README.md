# Passwortänderung erzwingen

Version 2.0 ist neu für WBCE 1.7 entwickelt. Die Integration erfolgt ausschließlich über die öffentliche Hook-API beziehungsweise deren kompatible Bereitstellung durch `wbce_hook_bridge`.

## Verwendete Hooks

- `admin.user.form.sections`: fügt die dynamische Auswahl in das Formular zum Anlegen und Bearbeiten von Benutzern ein.
- `user.created`: speichert die Auswahl nach dem Anlegen eines Benutzers.
- `user.updated`: speichert die Auswahl nach dem Bearbeiten eines Benutzers.
- `user.deleted`: entfernt den nicht mehr benötigten Moduldatensatz des Benutzers.
- `auth.login.redirect`: leitet einen betroffenen Benutzer unmittelbar nach erfolgreicher Anmeldung zur Passwortänderung.
- `user.password.reset_completed`: erfüllt die Vorgabe nach einer abgeschlossenen Passwortwiederherstellung.
- `user.password.changed` und `user.password.forced_change_completed`: informieren weitere Module nach erfolgreicher Änderung.

## Kompatibilität

WBCE 1.7 ist die primäre Laufzeit. Unter WBCE 1.6.8 stellt `wbce_hook_bridge` die fehlenden Formular-, Benutzer- und Login-Anknüpfungspunkte bereit. Die abweichenden Datenbank- und Sitzungszugriffe sind ausschließlich in `Compatibility.php` gekapselt und können später gemeinsam entfernt werden.

Das Modul besitzt keine Admin-Tools-Seite. Seine Einstellung erscheint nur bei installiertem Modul direkt im Benutzerformular.

## Version 2.0.15

- Eigenständige Metadaten mit deutscher Bezeichnung und Beschreibung.
- Übersetzungsfunktionen können aus temporärem und installiertem Verzeichnis geladen werden, ohne einen PHP-Fatalfehler auszulösen.
- Installation und Upgrade überschreiben die vom CMS registrierte Paketversion nicht mehr mit 2.0.11.

## Version 2.0.16

- Der Installations-Precheck verwendet eigenständige Klartexte und bleibt dadurch auch bei einer bereits geladenen älteren Modulübersetzung lesbar.

## Version 2.0.17

- Der Precheck erkennt die nativen Hook-Schnittstellen direkt, auch wenn eine angepasste WBCE-Installation keine zuverlässig vergleichbare Versionsangabe liefert.
