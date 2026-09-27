<?php
/** Provider-spezifische Mailer-Konfiguration. */
return [
    'transports'=>['api','smtp'],
    'smtp'=>[
        'host'=>'smtp.tem.scaleway.com',
        'smtp_connections'=>[
            ['id'=>'587-tls','label'=>'587 · STARTTLS','port'=>587,'encryption'=>'tls'],
            ['id'=>'2587-tls','label'=>'2587 · STARTTLS','port'=>2587,'encryption'=>'tls'],
            ['id'=>'465-ssl','label'=>'465 · SSL/TLS','port'=>465,'encryption'=>'ssl'],
            ['id'=>'2465-ssl','label'=>'2465 · SSL/TLS','port'=>2465,'encryption'=>'ssl'],
        ],
    ],
    'api_fields'=>[
        ['name'=>'api_key','label'=>'Access Key','type'=>'password','required'=>true],
        ['name'=>'api_secret','label'=>'Secret Key','type'=>'password','required'=>true],
        ['name'=>'region','label'=>'Region','type'=>'text','required'=>true,'options'=>['fr-par']],
    ],
    'api_dsn'=>static fn(array $v)=>'scaleway+api://'.rawurlencode($v['api_key']).':'.rawurlencode($v['api_secret']).'@default?region='.rawurlencode($v['region']),
];
