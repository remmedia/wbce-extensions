<?php
/** Provider-spezifische Mailer-Konfiguration. */
return ['transports'=>['api','smtp'],'smtp'=>['host'=>'smtp-relay.brevo.com','port'=>587,'encryption'=>'tls'],'api_fields'=>[['name'=>'api_key','label'=>'API-Schlüssel','type'=>'password','required'=>true]],'api_dsn'=>static fn(array $v)=>'brevo+api://'.rawurlencode($v['api_key']).'@default'];
