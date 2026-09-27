# Cookie Banner

Weitere Module melden Kategorien über den Filter `cookie_banner.categories` an:

```php
wbce_add_filter('cookie_banner.categories', static function (array $categories) {
    $categories['analytics'] = array('name'=>'Statistik','description'=>'Anonyme Reichweitenmessung.','scripts'=>array(WB_URL.'/modules/example/analytics.js'));
    return $categories;
});
```

Die Kategorie wird erst geladen, nachdem Besucher zugestimmt haben. Erforderliche Kategorien erhalten `required => true`.

Ein Modul kann außerdem einen eigenen, bereits vollständig ausgegebenen Einstellungsbereich über `cookie_banner.settings.sections` ergänzen. Die Felder liegen im zentralen Formular und werden beim Speichern über den Action-Hook `cookie_banner.settings.save` verarbeitet:

```php
wbce_add_filter('cookie_banner.settings.sections', static function (array $sections) {
    $sections[] = '<section class="cookie-banner-extension"><h3>Statistik</h3>…</section>';
    return $sections;
});
wbce_add_action('cookie_banner.settings.save', static function (array $post) {
    // Eigene Werte validieren und speichern.
});
```

Damit bleiben Konfiguration und Änderungen vollständig im Backend des Cookie-Banner-Moduls.
