<?php
$config=dirname(__DIR__,2).'/config.php'; require $config;
require_once WB_PATH.'/framework/Admin.php'; require_once __DIR__.'/src/MailerRegistry.php'; require_once __DIR__.'/src/I18n.php';
$t=static fn(string $key,string $fallback='')=>WbceMailerI18n::t($key,$fallback);
header('Content-Type: application/json; charset=utf-8');
try {
    $admin=new admin('Admintools','admintools',false,false);
    if(!$admin->is_authenticated()||!$admin->isAdmin()||!$admin->checkFTAN()) throw new RuntimeException($t('NOT_AUTHORIZED'));
    $providers=WbceMailerRegistry::providers();
    $provider=preg_replace('/[^a-z0-9_-]/i','',(string)($_POST['provider']??''));
    if(!isset($providers[$provider])) throw new RuntimeException($t('INVALID_PROVIDER'));
    $engine=($_POST['engine']??'symfony')==='phpmailer'?'phpmailer':'symfony';
    $available=(array)($providers[$provider]['transports']??['smtp']);
    $transport=(string)($_POST['provider_transport']??reset($available));
    if(!in_array($transport,$available,true)) throw new RuntimeException($t('INVALID_CONNECTION'));
    if($engine==='phpmailer'&&$transport!=='smtp') throw new RuntimeException($t('PHPMAILER_SMTP_ONLY'));
    $settings=['enabled'=>empty($_POST['enabled'])?'0':'1','engine'=>$engine,'provider'=>$provider,'provider_transport'=>$transport,'from_email'=>trim((string)($_POST['from_email']??'')),'from_name'=>trim((string)($_POST['from_name']??''))];
    Settings::set('mailer',json_encode($settings));
    $dsn='';
    if($transport==='smtp') {
        $prefix=$provider==='smtp'?'mailer_smtp_':'mailer_'.$provider.'_smtp_';
        $smtpProfile=(array)($providers[$provider]['smtp']??[]);
        $region='';
        $servers=(array)($smtpProfile['smtp_servers']??[]);
        $selectedHost=trim((string)($_POST['smtp_host']??''));
        if($servers!==[]) {
            $allowedHosts=array_column($servers,'value');
            if(!in_array($selectedHost,$allowedHosts,true)) $selectedHost=(string)($allowedHosts[0]??'');
        }
        $host=trim((string)($smtpProfile['host']??$selectedHost)); if($host==='') throw new RuntimeException($t('SMTP_HOST_REQUIRED'));
        $connections=(array)($smtpProfile['smtp_connections']??[]);
        $connectionId=(string)($_POST['smtp_connection']??'');
        $connection=null; foreach($connections as $candidate) { if(is_array($candidate)&&($candidate['id']??'')===$connectionId) {$connection=$candidate; break;} }
        if($connections!==[]&&$connection===null) $connection=$connections[0]??null;
        $port=max(1,min(65535,(int)($connection['port']??$smtpProfile['port']??$_POST['smtp_port']??587))); $username=trim((string)($smtpProfile['username']??$_POST['smtp_username']??''));
        $password=trim((string)($_POST['smtp_password']??'')); if($password==='') $password=(string)Settings::get($prefix.'password','');
        $encryption=(string)($connection['encryption']??$smtpProfile['encryption']??(in_array($_POST['smtp_encryption']??'', ['tls','ssl',''], true)?(string)$_POST['smtp_encryption']:'tls'));
        foreach(['host'=>$host,'port'=>$port,'username'=>$username,'encryption'=>$encryption,'region'=>$region] as $key=>$value) Settings::set($prefix.$key,(string)$value);
        if(trim((string)($_POST['smtp_password']??''))!=='') Settings::set($prefix.'password',$password);
        $scheme=$encryption==='ssl'?'smtps':'smtp'; $credentials=$username!==''?rawurlencode($username).':'.rawurlencode($password).'@':'';
        $dsn=$scheme.'://'.$credentials.$host.':'.$port;
    } else {
        $values=[];
        foreach((array)($providers[$provider]['api_fields']??[]) as $field) {
            $name=(string)($field['name']??''); if($name==='') continue;
            $value=trim((string)($_POST['api_'.$name]??''));
            if($value==='') $value=(string)Settings::get('mailer_'.$provider.'_api_'.$name,'');
            if(!empty($field['required'])&&$value==='') throw new RuntimeException(sprintf($t('FIELD_REQUIRED'),WbceMailerI18n::label((string)($field['label']??$name))));
            $values[$name]=$value; if($value!=='') Settings::set('mailer_'.$provider.'_api_'.$name,$value);
        }
        $builder=$providers[$provider]['api_dsn']??null;
        if(!is_callable($builder)) throw new RuntimeException($t('API_CONFIG_MISSING'));
        $dsn=(string)$builder($values);
    }
    Settings::set('mailer_'.$provider.'_dsn',$dsn);
    if(!empty($_POST['test'])) {
        require_once WB_PATH.'/include/SymfonyMailer/vendor/autoload.php';
        $email=(new \Symfony\Component\Mime\Email())->from($settings['from_email']?:'noreply@localhost')->to((string)($_POST['test_to']??''))->subject($t('TEST_SUBJECT'))->text($t('TEST_TEXT'));
        (new \Symfony\Component\Mailer\Mailer(\Symfony\Component\Mailer\Transport::fromDsn($dsn)))->send($email);
    }
    Settings::set('mailer_status_ever_configured','1');
    Settings::set('mailer_status','ok');
    echo json_encode(['ok'=>true,'message'=>$t('SAVED')]);
} catch(Throwable $e) {
    if(isset($admin) && $admin instanceof admin) {
        $wasConfigured=(string)Settings::get('mailer_status_ever_configured','')==='1';
        Settings::set('mailer_status',$wasConfigured?'temporary_error':'error');
    }
    http_response_code(400); echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
}
