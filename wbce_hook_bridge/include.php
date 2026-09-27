<?php
if (!defined('WB_PATH')) { return; }

// Verlässlicher Frontend-Einstieg für WBCE 1.6.8. Der normale Hook-Weg
// bleibt erhalten; die registrierenden Module sind selbst idempotent.
require_once __DIR__.'/preinit.php';
