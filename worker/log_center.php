<?php
$language = defined('LANGUAGE') ? strtoupper(substr((string) LANGUAGE, 0, 2)) : 'EN';
$file = __DIR__.'/log_center_languages/'.($language === 'DE' ? 'DE' : 'EN').'.php';
$text = require $file;
return array('type'=>'worker','label'=>$text['label'],'description'=>$text['description']);
