<?php
$CAPTCHA_PRECHECK_LABEL='Hook interfaces';
$CAPTCHA_PROVIDER=array('name'=>'CaptchaFox','description'=>'GDPR-oriented bot protection from a European provider.','configure'=>'Configure CaptchaFox','sitekey'=>'Site key','secret'=>'Secret','keep_secret'=>'Leave empty to keep the existing secret','stored'=>'Secret stored','mode'=>'Mode','inline'=>'Inline','popup'=>'Popup','hidden'=>'Invisible','theme'=>'Appearance','light'=>'Light','dark'=>'Dark','start'=>'Start','automatic'=>'Automatic','focus'=>'On focus','manual'=>'Manual');
$CAPTCHA_PROVIDER['not_configured']='CaptchaFox is not fully configured yet.';
$CAPTCHA_PROVIDER+=array('registration_failed'=>'The CaptchaFox add-on registration failed.','hooks_required'=>'WBCE 1.7 hook API or WBCE Hook Bridge 1.2.0','available'=>'Available','unavailable'=>'Unavailable');
