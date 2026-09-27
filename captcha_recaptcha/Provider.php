<?php
final class WbceGoogleRecaptchaProvider
{
    private static function randomHex($bytes,$message)
    {
        try{return bin2hex(random_bytes((int)$bytes));}catch(Throwable $error){if(function_exists('openssl_random_pseudo_bytes')){$strong=false;$value=openssl_random_pseudo_bytes((int)$bytes,$strong);if($strong&&is_string($value)&&strlen($value)===(int)$bytes)return bin2hex($value);}throw new RuntimeException((string)$message,0,$error);}
    }

    public static function render(array $context)
    {
        $site=trim((string)Settings::Get('captcha_recaptcha_sitekey',''));
        $secret=trim((string)Settings::Get('captcha_recaptcha_secret',''));
        $text=WbceCaptchaProviderUi::language(__DIR__,array('not_configured'=>'Google reCAPTCHA is not fully configured yet.','verification_failed'=>'The security check could not be completed. Please try again.','secure_random_required'=>'Google reCAPTCHA requires a secure random source.'));
        if($site===''||$secret==='')return '<p class="warning">'.self::e($text['not_configured']).'</p>';
        $domain=(string)Settings::Get('captcha_recaptcha_domain','google.com')==='recaptcha.net'?'recaptcha.net':'google.com';
        if((string)Settings::Get('captcha_recaptcha_version','v2')==='v2'){
            $theme=(string)Settings::Get('captcha_recaptcha_theme','light')==='dark'?'dark':'light';
            $asset='';if(!defined('WBCE_RECAPTCHA_V2_ASSET_LOADED')){define('WBCE_RECAPTCHA_V2_ASSET_LOADED',true);$asset='<script src="https://www.'.$domain.'/recaptcha/api.js?trustedtypes=true" async defer></script>';}
            return '<div class="g-recaptcha" data-sitekey="'.self::e($site).'" data-theme="'.$theme.'"></div>'.$asset;
        }
        $action=self::action($context);$id='wbce-recaptcha-'.self::randomHex(4,$text['secure_random_required']);$asset='';
        if(!defined('WBCE_RECAPTCHA_V3_ASSETS_LOADED')){define('WBCE_RECAPTCHA_V3_ASSETS_LOADED',true);$asset='<script src="https://www.'.$domain.'/recaptcha/api.js?render='.rawurlencode($site).'&amp;trustedtypes=true" defer></script><script src="'.self::e(WB_URL.'/modules/captcha_recaptcha/assets/recaptcha-v3.js?v=1.0.14').'" defer></script>';}
        return '<div class="wbce-recaptcha-v3" data-sitekey="'.self::e($site).'" data-action="'.self::e($action).'" data-error="'.self::e($text['verification_failed']).'"><input type="hidden" name="g-recaptcha-response" id="'.$id.'" value=""><span data-recaptcha-status role="alert" aria-live="polite"></span></div>'.$asset;
    }
    public static function verify($input,array $context)
    {
        $request=isset($context['request'])&&is_array($context['request'])?$context['request']:array();$rawToken=$request['g-recaptcha-response']??'';if(!is_scalar($rawToken))return false;$token=trim((string)$rawToken);$secret=trim((string)Settings::Get('captcha_recaptcha_secret',''));if($token===''||strlen($token)>16384||$secret==='')return false;
        $domain=(string)Settings::Get('captcha_recaptcha_domain','google.com')==='recaptcha.net'?'recaptcha.net':'google.com';$data=array('secret'=>$secret,'response'=>$token);$remote=$context['remote_address']??'';if(is_scalar($remote)&&filter_var((string)$remote,FILTER_VALIDATE_IP)!==false)$data['remoteip']=(string)$remote;$result=self::post('https://www.'.$domain.'/recaptcha/api/siteverify',$data);if(!is_array($result)||empty($result['success']))return false;
        $expectedHost=strtolower(rtrim((string)parse_url(defined('WB_URL')?WB_URL:'',PHP_URL_HOST),'.'));$verifiedHost=strtolower(rtrim((string)($result['hostname']??''),'.'));if($expectedHost===''||$verifiedHost===''||!hash_equals($expectedHost,$verifiedHost))return false;
        if((string)Settings::Get('captcha_recaptcha_version','v2')==='v3'){$score=(float)($result['score']??-1);if($score<(float)Settings::Get('captcha_recaptcha_score','0.5'))return false;if(!isset($result['action'])||!hash_equals(self::action($context),(string)$result['action']))return false;}
        return true;
    }
    private static function action(array $context){$value=$context['purpose']??($context['section_id']??($context['action']??'form'));$raw=is_scalar($value)?(string)$value:'form';if(stripos($raw,'forgot')!==false||stripos($raw,'reset')!==false)$raw='password_reset';elseif(stripos($raw,'login')!==false)$raw='login';elseif($raw==='')$raw='signup';$raw=preg_replace('/[^a-zA-Z0-9_\/]/','_',strtolower($raw));return substr($raw?:'form',0,100);}
    private static function post($url,array $data){$body=http_build_query($data,'','&');$raw=false;if(function_exists('curl_init')){$curl=curl_init($url);if($curl!==false){curl_setopt_array($curl,array(CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_HTTPHEADER=>array('Content-Type: application/x-www-form-urlencoded','Accept: application/json'),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>10,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS));$raw=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);if(!is_string($raw)||$status<200||$status>=300||strlen($raw)>65536)$raw=false;if(PHP_VERSION_ID<80500)curl_close($curl);}}if($raw===false&&filter_var(ini_get('allow_url_fopen'),FILTER_VALIDATE_BOOLEAN)){$options=array('http'=>array('method'=>'POST','timeout'=>10,'ignore_errors'=>false,'follow_location'=>0,'max_redirects'=>0,'header'=>"Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json",'content'=>$body),'ssl'=>array('verify_peer'=>true,'verify_peer_name'=>true));$raw=@file_get_contents($url,false,stream_context_create($options),0,65537);}if(!is_string($raw)||strlen($raw)>65536)return null;$decoded=json_decode($raw,true);return is_array($decoded)?$decoded:null;}
    private static function e($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
}
