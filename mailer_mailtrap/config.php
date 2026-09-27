<?php
/** Provider-spezifische Mailer-Konfiguration. */
return ['transports'=>['api','smtp'],'smtp'=>['host'=>'live.smtp.mailtrap.io','port'=>587,'encryption'=>'tls'],'api_fields'=>[['name'=>'api_key','label'=>'API-Token','type'=>'password','required'=>true]],'api_dsn'=>static fn(array $v)=>'mailtrap+api://'.rawurlencode($v['api_key']).'@default'];
