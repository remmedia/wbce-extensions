# Worker für WBCE 1.7

Das Admin-Modul verwaltet Aufgaben mit fünfteiligen Cron-Ausdrücken und startet jede fällige Aufgabe in einem eigenen PHP-Hintergrundprozess. Hinterlegte Aufgaben referenzieren ausschließlich registrierte Worker; freie Shell-Befehle können aus Sicherheitsgründen nicht gespeichert werden.

## Installation und System-Cron

Das ZIP-Paket wie ein normales WBCE-Modul installieren. Danach unter **Admin-Tools → Worker** den angezeigten System-Cron auf dem Server eintragen. Er muss einmal pro Minute aufgerufen werden:

```cron
* * * * * nice -n 10 /usr/bin/php /absoluter/pfad/modules/worker/cron.php >/dev/null 2>&1
```

Unter **Admin-Tools → Worker** lassen sich die Anzahl gestarteter Aufgaben,
das Zeitbudget eines Scheduler-Laufs und eine Pause zwischen Starts begrenzen.
Damit werden keine weiteren Aufgaben gestartet, sobald das Budget erreicht ist.
Der angezeigte Cron-Befehl startet den Scheduler zudem mit niedriger
CPU-Priorität. Eine harte prozentuale CPU-Grenze benötigt weiterhin eine
Server-Funktion wie systemd/cgroups.

`cron.php` und `run.php` sind ausschließlich in PHP-CLI ausführbar. Eine Stunde alte Sperren werden automatisch aufgehoben. Zeitstempel werden intern in UTC gespeichert; Cron-Auswertung und Anzeige verwenden die im CMS konfigurierte IANA-Zeitzone einschließlich Sommer- und Winterzeit.

Im Modus **Seitenaufruf** wird bei Frontend- und Backend-Aufrufen geprüft, ob das eingestellte Intervall abgelaufen ist. Unter FastCGI beginnt die Verarbeitung nach dem Senden der Antwort. Unter anderen PHP-SAPIs wird sie über einen signierten internen HTTP-Aufruf von der ursprünglichen Seitenanfrage getrennt.

## Worker aus einem anderen Modul registrieren

Das andere Modul braucht die Funktion `initialize` und registriert in seiner `initialize.php` einen Filter. Die ID bleibt über alle Versionen stabil.

```php
wbce_add_filter('worker.definitions', static function (array $workers): array {
    $workers['mein_modul.newsletter'] = array(
        'label' => 'Newsletter versenden',
        'description' => 'Versendet die vorbereitete Newsletter-Warteschlange.',
        'callable' => static function (array $configuration, array $task): array {
            $limit = max(1, (int)($configuration['limit'] ?? 25));
            // Maximal $limit Einträge bearbeiten.
            return array('message' => $limit . ' Einträge wurden bearbeitet.');
        },
    );
    return $workers;
});
```

Der Callback erhält zuerst die in der Aufgabe hinterlegte JSON-Konfiguration als Array und danach den Datensatz der Aufgabe. Er kann einen Meldungstext oder `array('message' => '…')` zurückgeben. Exceptions werden abgefangen und als fehlgeschlagene Ausführung protokolliert.

Aufgaben, die ein Modul über `WbceWorkerService::save()` anlegt, gelten automatisch als vom Modul verwaltet. Sie werden in der Worker-Oberfläche nur angezeigt und können dort weder bearbeitet, manuell ausgeführt, deaktiviert noch gelöscht werden. Das anlegende Modul darf sie über die Service-Methoden weiterhin verwalten. Nur der interne Dialog „Neue Aufgabe“ legt frei verwaltbare Aufgaben an. Eine feste Systemaufgabe kann zusätzlich mit `managed_task => true`, `default_name`, `default_cron` und `default_configuration` registriert werden. Der Worker für die Protokollbereinigung wird bei Installation und Upgrade automatisch auf diese Weise angelegt.

## Cron-Schema

Unterstützt werden `*`, Listen (`1,5`), Bereiche (`1-5`) und Schritte (`*/10`, `1-20/2`) in der Reihenfolge Minute, Stunde, Tag, Monat, Wochentag. Sonntag kann als `0` oder `7` angegeben werden.
Sind sowohl Tag als auch Wochentag eingeschränkt, gilt wie bei klassischem Cron eine ODER-Verknüpfung.
