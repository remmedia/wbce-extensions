# Accessibility Tools

Das Modul bindet die Bedienungshilfen einmal zentral in alle Frontendseiten ein. Die Sprache wird aus dem `lang`-Attribut des HTML-Dokuments gelesen.

## Integrationswege

1. WBCE 1.7: nativer Filter `frontend.page.output`
2. WBCE-Frontend-Include: templateunabhängige Registrierung auch auf älteren Installationen
3. WBCE 1.6.8 mit Hook-Bridge: derselbe Filter wird durch die Bridge bereitgestellt
4. WBCE 1.6.8 ohne Hook-Bridge: eigener, isolierter Ausgabepuffer aus `legacy/OutputBuffer.php`

Der vierte Weg ist bewusst vollständig im Ordner `legacy` gekapselt. Der kleine Puffer dient zusätzlich als Sicherheitsnetz, falls eine alte Installation zwar Hook-Funktionen bereitstellt, den Ausgabe-Hook aber noch nicht ausführt. Hat der reguläre Hook die Skripte bereits eingesetzt, erkennt der Puffer den Modul-Marker und verändert nichts. Sobald solche Installationen nicht mehr unterstützt werden sollen, kann der markierte Block in `src/Injector.php` zusammen mit dem kompletten Ordner `legacy` entfernt werden.

Die Hook-Bridge ist optional. Frontend-Templates müssen keinen eigenen Aufruf und keine eigene Kopie des Skripts enthalten.
Das Output Filter Dashboard wird weder benötigt noch verändert.
