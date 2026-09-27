<?php

final class WbceAltchaProviderSettings
{
    private static $defaults = array(
        'auto'=>'off','delay'=>0,'hidefooter'=>false,'hidelogo'=>false,
        'color_brand'=>'','color_success'=>'','color_base'=>'',
        'color_checkbox'=>'','color_text'=>'','border_radius'=>'',
    );

    public static function get()
    {
        $stored = json_decode((string)Settings::Get('captcha_altcha', '{}'), true);
        return array_merge(self::$defaults, is_array($stored) ? $stored : array());
    }

    public static function save($input)
    {
        $old = self::get();
        $auto = isset($input['altcha_auto']) && in_array($input['altcha_auto'], array('off','onload','onsubmit'), true) ? $input['altcha_auto'] : 'off';
        $radius = isset($input['altcha_border_radius']) && in_array($input['altcha_border_radius'], array('','0px','4px','12px'), true) ? $input['altcha_border_radius'] : '';
        $cfg = array_merge($old, array(
            'auto'=>$auto,
            'delay'=>max(0, min(3000, (int)(isset($input['altcha_delay']) ? $input['altcha_delay'] : 0))),
            'hidefooter'=>!empty($input['altcha_hidefooter']),
            'hidelogo'=>!empty($input['altcha_hidelogo']),
            'color_brand'=>self::color(isset($input['altcha_color_brand']) ? $input['altcha_color_brand'] : ''),
            'color_success'=>self::color(isset($input['altcha_color_success']) ? $input['altcha_color_success'] : ''),
            'color_base'=>self::color(isset($input['altcha_color_base']) ? $input['altcha_color_base'] : ''),
            'color_checkbox'=>self::color(isset($input['altcha_color_checkbox']) ? $input['altcha_color_checkbox'] : ''),
            'color_text'=>self::color(isset($input['altcha_color_text']) ? $input['altcha_color_text'] : ''),
            'border_radius'=>$radius,
        ));
        $encoded = json_encode($cfg, JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            $text = WbceCaptchaProviderUi::language(__DIR__, array('settings_encode_failed'=>'The ALTCHA settings could not be encoded.'));
            throw new RuntimeException((string)$text['settings_encode_failed']);
        }
        $settingsError = Settings::Set('captcha_altcha', $encoded);
        $complexityError = Settings::Set('captcha_altcha_complexity', (string)max(1000, min(500000, (int)(isset($input['captcha_altcha_complexity']) ? $input['captcha_altcha_complexity'] : 50000))));
        if ($settingsError || $complexityError) {
            throw new RuntimeException((string)($settingsError ?: $complexityError));
        }
    }

    public static function form()
    {
        $c=self::get();
        $t=WbceCaptchaProviderUi::language(__DIR__,array());
        $h=function($v){return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');};
        $checked=function($v){return $v?' checked':'';};
        $selected=function($a,$b){return $a===$b?' selected':'';};
        return '<fieldset class="altcha-provider-config"><legend>'.$h($t['configure']).'</legend><div class="altcha-provider-layout"><div class="altcha-provider-fields">'
            .WbceCaptchaProviderUi::row($t['complexity'],WbceCaptchaProviderUi::input('number','captcha_altcha_complexity',(int)Settings::Get('captcha_altcha_complexity',50000),array('min'=>'1000','max'=>'500000')),$t['complexity_hint'])
            .WbceCaptchaProviderUi::row($t['start_mode'],WbceCaptchaProviderUi::select('altcha_auto',$c['auto'],array('off'=>$t['start_action'],'onload'=>$t['start_load'],'onsubmit'=>$t['start_submit'])))
            .WbceCaptchaProviderUi::row($t['delay'],'<span class="altcha-delay-control">'.WbceCaptchaProviderUi::input('number','altcha_delay',(int)$c['delay'],array('class'=>'altcha-delay-input','min'=>'0','max'=>'3000','step'=>'100')).'<span>ms</span></span>')
            .self::colorRow($t['brand'],'altcha_color_brand',$c['color_brand'],$h).self::colorRow($t['success'],'altcha_color_success',$c['color_success'],$h).self::colorRow($t['base'],'altcha_color_base',$c['color_base'],$h).self::colorRow($t['checkbox'],'altcha_color_checkbox',$c['color_checkbox'],$h).self::colorRow($t['text'],'altcha_color_text',$c['color_text'],$h)
            .WbceCaptchaProviderUi::row($t['radius'],WbceCaptchaProviderUi::select('altcha_border_radius',$c['border_radius'],array(''=>$t['standard'],'0px'=>$t['square'],'4px'=>$t['slight'],'12px'=>$t['round'])))
            .'</div><aside class="altcha-provider-preview"><strong>'.$h($t['preview']).'</strong><div class="altcha-provider-mock" style="--altcha-brand:'.$h($c['color_brand']?:'#2271b1').';--altcha-success:'.$h($c['color_success']?:'#16a34a').';--altcha-base:'.$h($c['color_base']?:'#ffffff').';--altcha-checkbox:'.$h($c['color_checkbox']?:'transparent').';--altcha-text:'.$h($c['color_text']?:'#111827').';--altcha-radius:'.$h($c['border_radius']?:'4px').'"><div class="altcha-provider-mock-main"><span class="altcha-provider-mock-check"></span><span class="altcha-provider-mock-text">'.$h($t['human']).'</span><svg class="altcha-provider-mock-logo" viewBox="0 0 20 20" aria-label="ALTCHA"><path d="M2.34 16.43A10 10 0 1 0 3.57 2.34l1.29 1.53a8 8 0 1 1-1 11.27z" fill="currentColor"/><path d="M7 10H5a5 5 0 0 0 10 0h-2a3 3 0 0 1-6 0z" fill="currentColor"/></svg></div><div class="altcha-provider-mock-footer">'.$h($t['protected']).'</div></div>'
            .'<div class="altcha-preview-controls"><strong>'.$h($t['elements']).'</strong><div class="altcha-switch-list"><label class="captcha-switch altcha-switch-option"><input type="checkbox" name="altcha_hidefooter" value="1"'.$checked(!empty($c['hidefooter'])).'><span class="captcha-slider"></span><span>'.$h($t['hide_footer']).'</span></label><label class="captcha-switch altcha-switch-option"><input type="checkbox" name="altcha_hidelogo" value="1"'.$checked(!empty($c['hidelogo'])).'><span class="captcha-slider"></span><span>'.$h($t['hide_logo']).'</span></label></div></div><small>'.$h($t['saved_hint']).'</small></aside></div></fieldset>'
            .'<script>(function(){var root=document.currentScript.previousElementSibling;if(!root)return;var mock=root.querySelector(".altcha-provider-mock"),footer=root.querySelector(".altcha-provider-mock-footer"),logo=root.querySelector(".altcha-provider-mock-logo");function value(n,d){var e=root.querySelector("[name=\""+n+"\"]");return e&&e.value?e.value:d}function update(){mock.style.setProperty("--altcha-brand",value("altcha_color_brand","#2271b1"));mock.style.setProperty("--altcha-success",value("altcha_color_success","#16a34a"));mock.style.setProperty("--altcha-base",value("altcha_color_base","#ffffff"));mock.style.setProperty("--altcha-checkbox",value("altcha_color_checkbox","transparent"));mock.style.setProperty("--altcha-text",value("altcha_color_text","#111827"));mock.style.setProperty("--altcha-radius",value("altcha_border_radius","4px"));footer.hidden=root.querySelector("[name=altcha_hidefooter]").checked;logo.hidden=root.querySelector("[name=altcha_hidelogo]").checked}root.querySelectorAll("input,select").forEach(function(e){e.addEventListener("input",update);e.addEventListener("change",update)});update()}());</script>';
    }

    private static function colorRow($label,$name,$value,$h)
    {
        $defaults=array(
            'altcha_color_brand'=>'#2271b1',
            'altcha_color_success'=>'#16a34a',
            'altcha_color_base'=>'#ffffff',
            'altcha_color_checkbox'=>'#ffffff',
            'altcha_color_text'=>'#111827',
        );
        $preview=$value!==''?$value:$defaults[$name];
        return '<div class="cp-setting-row"><div class="cp-setting-name">'.$label.'</div><div class="cp-setting-value"><span class="altcha-color-control"><input type="color" class="altcha-native-color" value="'.$h($preview).'" data-color-target="'.$name.'" aria-label="'.$label.'"><input type="text" id="'.$name.'" class="altcha-coloris" data-wbecoloris name="'.$name.'" value="'.$h($value).'" placeholder="'.$h($defaults[$name]).'" pattern="#[0-9A-Fa-f]{6}" inputmode="text"></span></div></div>';
    }

    private static function color($value)
    {
        $value=trim((string)$value);
        return $value===''||preg_match('/^#[0-9a-f]{6}$/i',$value)?$value:'';
    }
}
