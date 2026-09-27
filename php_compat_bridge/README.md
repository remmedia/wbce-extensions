# PHP-Bridge

Die Bridge stellt auf PHP 8.2 und 8.3 die PHP-8.4-Funktionen `array_find`,
`array_find_key`, `array_any`, `array_all`, `mb_ucfirst`, `mb_lcfirst`,
`json_validate`, `mb_str_pad` und `grapheme_str_split` bereit. Jede Funktion
wird nur definiert, wenn PHP sie nicht selbst anbietet.
Damit kann das Paket nach dem Ende der PHP-8.2-Unterstützung entfernt werden.
