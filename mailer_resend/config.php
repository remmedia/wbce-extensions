<?php
/** Provider-spezifische Mailer-Konfiguration. */
return ['transports'=>['api','smtp'],'smtp'=>['host'=>'smtp.resend.com','port'=>587,'encryption'=>'tls','username'=>'resend'],'api_fields'=>[['name'=>'api_key','label'=>'API-Schlüssel','type'=>'password','required'=>true]],'api_dsn'=>static fn(array $v)=>'resend+api://'.rawurlencode($v['api_key']).'@default'];
