# WBCE Hook Bridge

Primärziel ist WBCE 1.7, unterstützt wird zusätzlich WBCE 1.6.8. Die Bridge ergänzt ausschließlich APIs, die in einer unveränderten Installation fehlen. Erkennt sie native WBCE-Hooks, bleiben ihre Adapter inaktiv.

Die Kompatibilität ist vollständig in diesem Modul gekapselt. Es ersetzt keine Core-Dateien und legt keine eigenen Anwendungsdaten an.

## Frontend-Ausgabe

Module registrieren die Ausgabeanpassung unabhängig von der WBCE-Version über:

```php
wbce_add_filter('frontend.page.output', $callback, 10);
```

Der Callback erhält die vollständige HTML-Ausgabe, die Seiten-ID und die Seitendaten. Auf WBCE-Versionen ohne nativen Ausgabe-Hook aktiviert das aufrufende Modul einmalig `WbceHookBridge::enableFrontendOutputHooks()`. Auf Systemen mit nativen Hooks bleibt dieser Adapter vollständig inaktiv.

## Benutzerverwaltung

Zusätzliche Formularbereiche werden als HTML an den bestehenden Inhalt angehängt:

```php
wbce_add_filter('admin.user.form.sections', function ($html, $userId, $isNew, $context) {
    return $html . '<section>…</section>';
}, 10);
```

Der Callback erhält den bisherigen HTML-Inhalt, die Benutzer-ID, den Status
„neuer Benutzer“ und einen Kontext. Unter WBCE 1.6.8 aktiviert das Modul den
Adapter einmalig mit `WbceHookBridge::enableUserFormHooks()`. Die Bridge löst
nach einer passenden Formularverarbeitung außerdem diese allgemeinen Aktionen
aus:

- `user.created($userId, $savedData, $requestData)`
- `user.updated($userId, $savedData, $requestData)`
- `user.password.changed($userId, $source)`

Die Datenhaltung und jede fachliche Reaktion bleiben vollständig im
registrierenden Modul.

## „Meine Daten“

Module geben ihre Bereiche als Array von HTML-Fragmenten zurück:

```php
wbce_add_filter('user.preferences.sections', function ($sections, $userId) {
    $sections[] = '<section>…</section>';
    return $sections;
}, 10);
```

Auf WBCE 1.6.8 aktiviert das Modul dafür einmalig
`WbceHookBridge::enablePreferencesHooks()`. Die Bridge stellt weder Farben noch
Formular-Komponenten bereit; Gestaltung und Bedienelemente werden weiterhin
vom aktiven Admin-Template bezogen.

## Dispatcher-API

Falls WBCE die Hook-Funktionen nicht selbst bereitstellt, ergänzt die Bridge:

- `wbce_add_action()` und `wbce_add_filter()` zur Registrierung,
- `wbce_do_action()` für Ereignisse,
- `wbce_apply_filters()` und `wbce_apply_array_filters()` für Filter,
- `wbce_has_hook()`, `wbce_remove_hook()` und `wbce_remove_all_hooks()` zur Abfrage und Entfernung,
- `wbce_hook_allows()` für abbrechbare Schutzprüfungen,
- `wbce_current_hook()`, `wbce_doing_hook()` und `wbce_did_hook()` für sichere Diagnose und Rekursionsschutz,
- `wbce_redirect_url()` und `wbce_safe_redirect_url()` für filterbare und
  installationsinterne Weiterleitungen.

Callbacks mit kleinerer Prioritätszahl laufen zuerst; bei identischer Priorität
bleibt die Registrierungsreihenfolge erhalten. `wbce_apply_array_filters()`
weist einen ungültigen Rückgabewert ausdrücklich zurück.

## Abgrenzung und Entfernung

Die Bridge enthält bewusst keine Logik für konkrete Module und keine eigene
Administrationsoberfläche. Auf Systemen mit nativer Hook-API werden keine
Ausgabeadapter aktiviert. Vor ihrer Deinstallation muss geprüft werden, ob
installierte Module sie aufgrund der WBCE-Version noch als Abhängigkeit
benötigen; der Store wertet hierzu deren `module_requires_any`-Metadaten aus.

## Erweiterte Hook-Punkte

Die Bridge stellt den vollständigen Dispatcher-Vertrag aus `CMS/wbce2-cms/docs/HOOKS.md` bereit, einschließlich `all` für reine Diagnose, der Abfragefunktionen und der Array-Filter. Sie kann auf WBCE 1.6.8 nur Hooks auslösen, für die ein Adapter oder das aufrufende Modul einen Auslösepunkt besitzt; sie verändert keine Core-Dateien.
