# WBCE Updater

Ein Updater für WBCE CMS mit PHP-Kompatibilitätsprüfung, sicheren Paketprüfungen und einer verständlichen Benutzerführung.

**Version:** 1.0.109  
**Kompatibel mit:** WBCE 1.7.x

## Features

### Intelligente Update-Verwaltung

- **Empfohlene Updates**: Zeigt das höchste Patch-Update als empfohlenes Update an.
- **Update-Risiko-Level**: Farbcodierte Hinweise für Patch-, Minor- und Major-Updates.
- **Versteckte Updates**: Blendet Zwischenversionen auf Wunsch aus.
- **Store-Integration**: Bezieht freigegebene Pakete über den WBCE Store Server.

### Sicherheit

- **CSRF-Schutz**: Geschützte Aktionen mit FTAN-Token.
- **SQL-Injection-Schutz**: Validierung und Escaping der Tabellenpräfixe.
- **XSS-Schutz**: Konsequentes HTML-Escaping aller Ausgaben.
- **ZIP-Slip-Schutz**: Verhindert Pfadmanipulation beim Entpacken.
- **SSL-Verifikation**: Zertifikatsprüfung für externe Requests.
- **Paketvalidierung**: Prüft Archive vor der Installation.

### PHP-Kompatibilitätsprüfung

- **Dynamische Prüfung**: Liest die Anforderungen mit lokalem Fallback.
- **Pre-Update-Warnung**: Informiert vor inkompatiblen PHP-Versionen, ohne den Ablauf unnötig zu blockieren.
- **EOL-Warnungen**: Hinweise für veraltete PHP-Versionen.
- **Visuelle Badges**: Zeigt den Kompatibilitätsstatus direkt in der Update-Liste.

### Benutzeroberfläche

- **Live-Feedback**: Sichtbarer Fortschritt bei Uploads, Backups und Updates.
- **Farbcodierte Updates**: Patch, Minor und Major sind klar unterscheidbar.
- **Backup-Integration**: Direkte Verbindung zum Backup Center, wenn es installiert ist.
- **Wartungsmodus**: Kann während eines Updates aktiviert werden.
- **Detaillierte Changelogs**: Release-Hinweise direkt im Interface.
- **Responsives Design**: Für Desktop und mobile Geräte geeignet.

### Update-Optionen

1. **Store-Download**: Freigegebene Pakete direkt aus dem Store installieren.
2. **Manueller Upload**: Eigene ZIP-Dateien in Teilstücken übertragen.
3. **Wartungsmodus**: Während des Updates optional aktivieren.
4. **Backup-Integration**: Direkter Link zum Backup Center, falls installiert.

## Installation

1. **Installation**: Über den Store installieren.
2. **Zugriff**: Über **Admin-Tools** → **Updater** aufrufen.

## Upgrade

Ein Upgrade wird automatisch erkannt und durchgeführt:

- Der Cache wird aktualisiert.
- Die Konfiguration bleibt erhalten.
- Kompatibel mit WBCE 1.7.x.

Manuelle Pakete werden in Teilstücken übertragen. Dadurch dürfen fertige ZIP-Dateien bis zu 512 MB groß sein, auch wenn `upload_max_filesize` und `post_max_size` deutlich niedriger gesetzt sind.

## Systemanforderungen

- **WBCE CMS**: 1.7.x
- **PHP**: 8.2+ (abhängig von der eingesetzten WBCE-Version)
- **PHP-Erweiterungen**:
  - ZipArchive für die ZIP-Verarbeitung
  - cURL oder `file_get_contents` für Downloads
  - JSON für die Paketinformationen
- **Schreibrechte**: Auf `WB_PATH` und das Verzeichnis `/temp`
- **Internet**: Zugriff auf den konfigurierten Store Server

## Update-Prozess

1. Backup erstellen, empfohlen mit dem Backup Center.
2. Updates prüfen.
3. PHP-Kompatibilität prüfen.
4. Update auswählen.
5. Wartungsmodus bei Bedarf aktivieren.
6. Paket übertragen oder aus dem Store laden und mit Live-Feedback installieren.
7. Das WBCE-Update-Script wird automatisch aufgerufen.

## Sicherheitshinweise

- Erstellen Sie vor Updates immer ein Backup.
- Testen Sie Updates zunächst in einer Testumgebung.
- Prüfen Sie die PHP-Kompatibilität vor dem Update.
- Aktivieren Sie auf produktiven Seiten bei Bedarf den Wartungsmodus.

## Troubleshooting

### Updates werden nicht angezeigt

- Verbindung zum konfigurierten Store Server prüfen.
- Den Update-Cache in `/temp/` leeren.
- Die Browser-Konsole auf JavaScript-Fehler prüfen.

### Upload schlägt fehl

- Schreibrechte auf `WB_PATH` prüfen.
- Den verfügbaren Speicherplatz prüfen.
- Den Upload nach einer Unterbrechung erneut starten; Teilstücke werden wiederverwendet.

### PHP-Kompatibilitätsprüfung zeigt Fehler

- Die Warnung prüfen und die passende PHP-Version für das Zielrelease wählen.
- Bei Unsicherheit zuerst WBCE aktualisieren und danach die PHP-Version ändern.

## Lizenz

MIT License

## Autoren

WBCE Community

## Support

- **WBCE Forum**: https://forum.wbce.org/
