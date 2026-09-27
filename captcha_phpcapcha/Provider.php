<?php
final class WbcePhpcapchaProvider
{
    private static function randomHex($bytes,$message)
    {
        try{return bin2hex(random_bytes((int)$bytes));}catch(Throwable $error){if(function_exists('openssl_random_pseudo_bytes')){$strong=false;$value=openssl_random_pseudo_bytes((int)$bytes,$strong);if($strong&&is_string($value)&&strlen($value)===(int)$bytes)return bin2hex($value);}throw new RuntimeException((string)$message,0,$error);}
    }

    public static function render(array $context)
    {
        $text=WbceCaptchaProviderUi::language(__DIR__,array('gd_required'=>'PHP GD is required for this CAPTCHA.','image_alt'=>'Security code image','input_label'=>'Enter security code','secure_random_required'=>'This CAPTCHA requires a secure random source.'));
        if(!extension_loaded('gd') || !function_exists('imagepng')) return '<p class="error">'.self::e($text['gd_required']).'</p>';
        $length=max(4,min(8,(int)Settings::Get('captcha_phpcapcha_length',6))); $alphabet='ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; $answer='';
        for($i=0;$i<$length;$i++)$answer.=$alphabet[random_int(0,strlen($alphabet)-1)];
        $token=self::randomHex(16,$text['secure_random_required']);
        if(!isset($_SESSION['wbce_phpcapcha'])||!is_array($_SESSION['wbce_phpcapcha']))$_SESSION['wbce_phpcapcha']=array();
        foreach($_SESSION['wbce_phpcapcha'] as $oldToken=>$challenge)if(!is_array($challenge)||(int)($challenge['expires']??0)<time())unset($_SESSION['wbce_phpcapcha'][$oldToken]);
        while(count($_SESSION['wbce_phpcapcha'])>=20)array_shift($_SESSION['wbce_phpcapcha']);
        $_SESSION['wbce_phpcapcha'][$token]=array('answer'=>$answer,'expires'=>time()+max(60,min(3600,(int)Settings::Get('captcha_phpcapcha_timeout',600))),'scope'=>self::scope($context));
        $src=WB_URL.'/modules/captcha_phpcapcha/image.php?token='.rawurlencode($token);
        return '<div class="wbce-phpcapcha" role="group" aria-label="'.self::e($text['image_alt']).'"><img src="'.self::e($src).'" width="210" height="70" alt="'.self::e($text['image_alt']).'"><label><span>'.self::e($text['input_label']).'</span><input name="captcha" maxlength="8" autocomplete="off" autocapitalize="characters" spellcheck="false" required></label><input type="hidden" name="phpcapcha_token" value="'.$token.'"></div>';
    }
    public static function verify($input,array $context)
    {
        $request=isset($context['request'])&&is_array($context['request'])?$context['request']:array();$rawToken=$request['phpcapcha_token']??'';$rawAnswer=$request['captcha']??'';if(!is_scalar($rawToken)||!is_scalar($rawAnswer))return false;$token=(string)$rawToken;$answer=strtoupper(trim((string)$rawAnswer));
        if(!preg_match('/^[a-f0-9]{32}$/D',$token)||!preg_match('/^[A-Z2-9]{4,8}$/D',$answer)||!isset($_SESSION['wbce_phpcapcha'][$token]))return false;$challenge=$_SESSION['wbce_phpcapcha'][$token];unset($_SESSION['wbce_phpcapcha'][$token]);
        return is_array($challenge)&&(int)($challenge['expires']??0)>=time()&&hash_equals((string)($challenge['scope']??''),self::scope($context))&&hash_equals((string)($challenge['answer']??''),$answer);
    }
    private static function scope(array $context){$purpose=$context['purpose']??'';$section=$context['section_id']??'';return hash('sha256',(is_scalar($purpose)?(string)$purpose:'').'|'.(is_scalar($section)?(string)$section:''));}
    private static function e($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
}
