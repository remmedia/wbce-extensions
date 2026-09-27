<?php
final class WbceIconCaptchaProvider
{
    private static function randomHex($bytes,$message)
    {
        try { return bin2hex(random_bytes((int)$bytes)); }
        catch (Throwable $error) {
            if(function_exists('openssl_random_pseudo_bytes')){$strong=false;$value=openssl_random_pseudo_bytes((int)$bytes,$strong);if($strong&&is_string($value)&&strlen($value)===(int)$bytes)return bin2hex($value);}
            throw new RuntimeException((string)$message,0,$error);
        }
    }

    public static function render(array $context)
    {
        $text=WbceCaptchaProviderUi::language(__DIR__,array('question'=>'Which symbol appears only once?','option_label'=>'Symbol %1$d: %2$s','selected'=>'Symbol selected','circle'=>'circle','diamond'=>'diamond','triangle'=>'triangle','square'=>'square','star'=>'star','heart'=>'heart','pentagon'=>'pentagon','hexagon'=>'hexagon','secure_random_required'=>'IconCaptcha requires a secure random source.'));
        $symbols=array('circle'=>'●','diamond'=>'◆','triangle'=>'▲','square'=>'■','star'=>'★','heart'=>'♥','pentagon'=>'⬟','hexagon'=>'⬢');
        $groups = array(array('circle','circle','circle','diamond'),array('triangle','triangle','square','triangle'),array('star','heart','star','star'),array('pentagon','pentagon','hexagon','pentagon'));
        $items = $groups[array_rand($groups)]; shuffle($items);
        $counts = array_count_values($items); $answer = 0;
        foreach ($items as $index=>$symbolName) if ($counts[$symbolName]===1) $answer=$index;
        $token = self::randomHex(16,$text['secure_random_required']);
        if(!isset($_SESSION['wbce_icon_captcha'])||!is_array($_SESSION['wbce_icon_captcha']))$_SESSION['wbce_icon_captcha']=array();
        foreach($_SESSION['wbce_icon_captcha'] as $oldToken=>$challenge)if(!is_array($challenge)||(int)($challenge['expires']??0)<time())unset($_SESSION['wbce_icon_captcha'][$oldToken]);
        while(count($_SESSION['wbce_icon_captcha'])>=20)array_shift($_SESSION['wbce_icon_captcha']);
        $_SESSION['wbce_icon_captcha'][$token] = array('answer'=>$answer,'expires'=>time()+max(60,min(3600,(int)Settings::Get('captcha_icon_timeout',600))),'scope'=>self::scope($context));
        $html='<fieldset class="wbce-icon-captcha" data-selected-text="'.self::e($text['selected']).'"><legend>'.self::e($text['question']).'</legend><div class="wbce-icon-options">';
        foreach($items as $index=>$symbolName){$label=sprintf((string)$text['option_label'],$index+1,(string)$text[$symbolName]);$html.='<button type="button" class="wbce-icon-option" data-value="'.$index.'" aria-pressed="false" aria-label="'.self::e($label).'">'.self::e($symbols[$symbolName]).'</button>';}
        $assets='';if(!defined('WBCE_ICON_CAPTCHA_ASSETS_LOADED')){define('WBCE_ICON_CAPTCHA_ASSETS_LOADED',true);$assets='<link rel="stylesheet" href="'.self::e(WB_URL.'/modules/captcha_icon/assets/icon.css?v=1.0.14').'"><script src="'.self::e(WB_URL.'/modules/captcha_icon/assets/icon.js?v=1.0.14').'" defer></script>';}
        return $html.'</div><input type="hidden" name="icon_captcha_token" value="'.$token.'"><input type="hidden" name="icon_captcha_answer" value=""><span class="wbce-icon-status" aria-live="polite"></span></fieldset>'.$assets;
    }
    public static function verify($input,array $context)
    {
        $request=isset($context['request'])&&is_array($context['request'])?$context['request']:array();$rawToken=$request['icon_captcha_token']??'';$rawAnswer=$request['icon_captcha_answer']??'';if(!is_scalar($rawToken)||!is_scalar($rawAnswer))return false;$token=(string)$rawToken;$answer=(string)$rawAnswer;
        if(!preg_match('/^[a-f0-9]{32}$/D',$token)||!preg_match('/^[0-3]$/D',$answer)||!isset($_SESSION['wbce_icon_captcha'][$token])) return false;
        $challenge=$_SESSION['wbce_icon_captcha'][$token]; unset($_SESSION['wbce_icon_captcha'][$token]);
        return is_array($challenge)&&(int)($challenge['expires']??0)>=time()&&hash_equals((string)($challenge['scope']??''),self::scope($context))&&hash_equals((string)($challenge['answer']??''),$answer);
    }
    private static function scope(array $context){$purpose=$context['purpose']??'';$section=$context['section_id']??'';return hash('sha256',(is_scalar($purpose)?(string)$purpose:'').'|'.(is_scalar($section)?(string)$section:''));}
    private static function e($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
}
