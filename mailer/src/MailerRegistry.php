<?php
final class WbceMailerRegistry {
    private const CATALOG = [
        'smtp' => ['name' => 'SMTP', 'description' => 'Universeller SMTP-Transport.', 'module' => 'mailer_smtp', 'icon' => 'fa fa-server', 'transports' => ['smtp']],
        'postmark' => ['name' => 'Postmark', 'description' => 'Transaktionale E-Mails per API.', 'module' => 'mailer_postmark', 'icon' => 'fa fa-envelope', 'transports' => ['api','smtp']],
        'sendgrid' => ['name' => 'SendGrid', 'description' => 'Versand über die SendGrid-API.', 'module' => 'mailer_sendgrid', 'icon' => 'fa fa-paper-plane', 'transports' => ['api','smtp']],
        'mailgun' => ['name' => 'Mailgun', 'description' => 'Versand über die Mailgun-API.', 'module' => 'mailer_mailgun', 'icon' => 'fa fa-send', 'transports' => ['api','smtp']],
        'amazon_ses' => ['name' => 'Amazon SES', 'description' => 'Versand über Amazon SES.', 'module' => 'mailer_amazon_ses', 'icon' => 'fa fa-cloud', 'transports' => ['api','smtp']],
        'brevo' => ['name' => 'Brevo', 'description' => 'Versand über die Brevo-API.', 'module' => 'mailer_brevo', 'icon' => 'fa fa-envelope-o', 'transports' => ['api','smtp']],
        'mailtrap' => ['name' => 'Mailtrap', 'description' => 'Test- und Transaktionsversand.', 'module' => 'mailer_mailtrap', 'icon' => 'fa fa-flask', 'transports' => ['api','smtp']],
        'scaleway' => ['name' => 'Scaleway', 'description' => 'Versand über Scaleway Transactional Email.', 'module' => 'mailer_scaleway', 'icon' => 'fa fa-cloud-upload', 'transports' => ['api','smtp']],
        'resend' => ['name' => 'Resend', 'description' => 'Versand über die Resend-API.', 'module' => 'mailer_resend', 'icon' => 'fa fa-repeat', 'transports' => ['api','smtp']],
        'rapidmail' => ['name' => 'rapidmail', 'description' => 'Versand über rapidmail.', 'module' => 'mailer_rapidmail', 'icon' => 'fa fa-envelope', 'transports' => ['smtp']],
        'cleverreach' => ['name' => 'CleverReach', 'description' => 'Versand über CleverReach.', 'module' => 'mailer_cleverreach', 'icon' => 'fa fa-send-o', 'transports' => ['smtp']],
    ];

    public static function catalog(): array { return self::CATALOG; }
    public static function providers(): array {
        $registered = function_exists('wbce_apply_array_filters') ? wbce_apply_array_filters('mailer.providers', []) : [];
        $providers = [];
        foreach (self::CATALOG as $id => $definition) {
            if ($id === 'smtp' || is_dir(WB_PATH.'/modules/'.$definition['module'])) {
                $providerFile=WB_PATH.'/modules/'.$definition['module'].'/provider.php';
                $providerDefinition=is_file($providerFile)?include $providerFile:[];
                $providers[$id] = array_merge($definition, is_array($providerDefinition)?$providerDefinition:[], (array)($registered[$id] ?? []));
            }
        }
        if (isset($registered['symfony'])) $providers['symfony'] = $registered['symfony'];
        return $providers;
    }
    public static function settings(): array {
        $raw=Settings::get('mailer',''); $data=json_decode((string)$raw,true);
        return array_merge(['enabled'=>'1','engine'=>'symfony','provider'=>'smtp','from_email'=>'','from_name'=>''],is_array($data)?$data:[]);
    }
    public static function active(): string { return (string)(self::settings()['provider']??''); }
    public static function configurationState(): string {
        $stored=(string)Settings::get('mailer_status','');
        $configured=(string)Settings::get('mailer_status_ever_configured','')==='1';
        $settings=self::settings(); $provider=(string)($settings['provider']??'smtp');
        $dsn=(string)Settings::get('mailer_'.$provider.'_dsn','');
        if($stored==='temporary_error'&&$configured) return 'temporary_error';
        if($stored==='error') return 'error';
        if(!$configured||$dsn==='') return 'unconfigured';
        return 'ok';
    }
    public static function canUninstall(string $provider): bool { $all=self::providers();unset($all[$provider]);return !empty($all)&&self::active()!==$provider; }
    /** Consumers may use this before offering e-mail based functionality. */
    public static function isReady(): bool {
        $settings=self::settings();
        return ($settings['enabled']??'0')==='1' && self::configurationState()==='ok';
    }
    public static function send(array $message): array {
        $s=self::settings();$providers=self::providers();$id=(string)($s['provider']??'smtp');$engine=(string)($s['engine']??'symfony');
        $transportId=$engine==='symfony'?'symfony':$id;
        if(($s['enabled']??'0')!=='1'||!isset($providers[$transportId])||!is_callable($providers[$transportId]['send']??null))return ['handled'=>false];
        $s['selected_provider']=$id; return (array)call_user_func($providers[$transportId]['send'],$message,$s);
    }
}
