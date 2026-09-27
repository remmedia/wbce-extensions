<?php
/** Provider-spezifische Mailer-Konfiguration. */
return ['transports'=>['api','smtp'],'smtp'=>['smtp_servers'=>[['label'=>'US · smtp.mailgun.org','value'=>'smtp.mailgun.org'],['label'=>'EU · smtp.eu.mailgun.org','value'=>'smtp.eu.mailgun.org']],'port'=>587,'encryption'=>'tls'],'api_fields'=>[['name'=>'api_key','label'=>'Privater API-Schlüssel','type'=>'password','required'=>true],['name'=>'domain','label'=>'Versand-Domain','type'=>'text','required'=>true]],'api_dsn'=>static fn(array $v)=>'mailgun+api://'.rawurlencode($v['api_key']).':'.rawurlencode($v['domain']).'@default'];
