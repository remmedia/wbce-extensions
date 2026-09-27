<?php
$CAPTCHA_PRECHECK_LABEL='Hook-Schnittstellen';
$CAPTCHA_PROVIDER=array('name'=>'CaptchaFox','description'=>'DSGVO-orientierter Bot-Schutz eines europäischen Anbieters.','configure'=>'CaptchaFox konfigurieren','sitekey'=>'Sitekey','secret'=>'Secret','keep_secret'=>'Leer lassen, um das vorhandene Secret beizubehalten','stored'=>'Secret hinterlegt','mode'=>'Modus','inline'=>'Inline','popup'=>'Popup','hidden'=>'Unsichtbar','theme'=>'Darstellung','light'=>'Hell','dark'=>'Dunkel','start'=>'Start','automatic'=>'Automatisch','focus'=>'Bei Fokus','manual'=>'Manuell');
$CAPTCHA_PROVIDER['not_configured']='CaptchaFox ist noch nicht vollständig konfiguriert.';
$CAPTCHA_PROVIDER+=array('registration_failed'=>'Die CaptchaFox-Modulregistrierung ist fehlgeschlagen.','hooks_required'=>'WBCE-1.7-Hook-API oder WBCE Hook Bridge 1.2.0','available'=>'Verfügbar','unavailable'=>'Nicht verfügbar');
