<?php
/** Provider-spezifische Mailer-Konfiguration. */
return ['transports'=>['api','smtp'],'smtp'=>['host'=>'smtp.postmarkapp.com','port'=>587,'encryption'=>'tls'],'api_fields'=>[['name'=>'api_key','label'=>'Server-API-Schlüssel','type'=>'password','required'=>true]],'api_dsn'=>static fn(array $v)=>'postmark+api://'.rawurlencode($v['api_key']).'@default'];
