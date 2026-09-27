<?php
final class WbceAltchaCaptchaProvider
{
    private static function randomHex($bytes)
    {
        try {
            return bin2hex(random_bytes((int)$bytes));
        } catch (Throwable $error) {
            if (function_exists('openssl_random_pseudo_bytes')) {
                $strong = false;
                $value = openssl_random_pseudo_bytes((int)$bytes, $strong);
                if ($strong && is_string($value) && strlen($value) === (int)$bytes) return bin2hex($value);
            }
            $text = WbceCaptchaProviderUi::language(__DIR__, array('secure_random_required'=>'ALTCHA requires a secure random source.'));
            throw new RuntimeException((string)$text['secure_random_required'], 0, $error);
        }
    }

    public static function render(array $context)
    {
        $max = max(1000, min(500000, (int)Settings::Get('captcha_altcha_complexity', 50000)));
        $number = random_int(1, $max);
        $expires = time() + 600;
        $salt = self::randomHex(12) . '?expires=' . $expires;
        $challenge = hash('sha256', $salt . $number);
        $signature = hash_hmac('sha256', $challenge, self::secret());
        $json = json_encode(array('algorithm'=>'SHA-256','challenge'=>$challenge,'maxnumber'=>$max,'salt'=>$salt,'signature'=>$signature), JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            $errorText = WbceCaptchaProviderUi::language(__DIR__, array('challenge_encode_failed'=>'The ALTCHA challenge could not be encoded.'));
            throw new RuntimeException((string)$errorText['challenge_encode_failed']);
        }
        $payload = base64_encode($json);
        $id = 'wbce-altcha-' . self::randomHex(4);
        $sectionId = isset($context['section_id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$context['section_id']) : '';
        $syncToken = self::randomHex(16);
        $_SESSION['captcha' . $sectionId] = $syncToken;
        // Keep the signed challenge in the active session as well. This makes
        // verification stable when a settings cache is refreshed between the
        // form render and its POST request, while binding the proof to this
        // exact browser session and form section.
        $_SESSION[self::sessionKey($sectionId)] = array(
            'challenge' => $challenge,
            'salt' => $salt,
            'signature' => $signature,
            'maxnumber' => $max,
            'expires' => $expires,
        );
        $url = WB_URL . '/modules/captcha_altcha/assets/altcha.js';
        $cssUrl = WB_URL . '/modules/captcha_altcha/assets/altcha.css?v=1.1.32';
        $cfg = WbceAltchaProviderSettings::get();
        $text = WbceCaptchaProviderUi::language(__DIR__, array('start_label'=>'Start security check','ready'=>'Security check ready','protected'=>'Protected by ALTCHA'));
        $widgetLanguage=defined('LANGUAGE')?strtoupper((string)LANGUAGE):'EN';$widgetFile=__DIR__.'/languages/widget/'.$widgetLanguage.'.php';if(!is_file($widgetFile))$widgetFile=__DIR__.'/languages/widget/EN.php';$CAPTCHA_WIDGET=array();include $widgetFile;
        $style = self::widgetStyle($cfg);
        $auto = in_array($cfg['auto'], array('off','onload','onsubmit'), true) ? $cfg['auto'] : 'off';
        return '<link rel="stylesheet" href="'.htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8').'">'
            . '<div class="wbce-altcha" id="'.$id.'" data-challenge="'.htmlspecialchars($payload, ENT_QUOTES, 'UTF-8').'" data-auto="'.$auto.'" data-delay="'.(int)$cfg['delay'].'" data-i18n-completed="'.self::escape($CAPTCHA_WIDGET['completed']).'" data-i18n-failed="'.self::escape($CAPTCHA_WIDGET['failed']).'" style="'.$style.'">'
            . '<div class="wbce-altcha-main"><button type="button" class="wbce-altcha-start" aria-label="'.self::escape($text['start_label']).'"></button><span class="wbce-altcha-status">'.self::escape($text['ready']).'</span>'.(!self::flag($cfg['hidelogo'])?self::logoSvg('wbce-altcha-logo'):'').'<input type="hidden" name="altcha" value=""><input type="hidden" name="captcha" value="" data-altcha-sync="'.self::escape($syncToken).'"></div>'
            . (!self::flag($cfg['hidefooter']) ? '<div class="wbce-altcha-footer">'.self::escape($text['protected']).'</div>' : '')
            . '</div>'
            . '<script src="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'" defer></script>';
    }
    public static function verify($input, array $context)
    {
        $encoded = isset($context['request']['altcha']) ? $context['request']['altcha'] : '';
        $data = json_decode((string)base64_decode($encoded, true), true);
        if (!is_array($data) || empty($data['challenge']) || empty($data['salt']) || !isset($data['number']) || empty($data['signature']) || !isset($data['maxnumber'])) return false;
        if ((int)$data['number'] < 0 || (int)$data['number'] > (int)$data['maxnumber'] || (int)$data['maxnumber'] > 500000) return false;
        if (!preg_match('/(?:^|\?)expires=(\d+)/', $data['salt'], $m) || (int)$m[1] < time()) return false;
        $expectedChallenge = hash('sha256', $data['salt'] . (int)$data['number']);
        if (!hash_equals($expectedChallenge, (string)$data['challenge'])) return false;

        $sectionId = isset($context['section_id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$context['section_id']) : '';
        $stored = $_SESSION[self::sessionKey($sectionId)] ?? null;
        if (is_array($stored)) {
            $matchesSessionChallenge = hash_equals((string)($stored['challenge'] ?? ''), (string)$data['challenge'])
                && hash_equals((string)($stored['salt'] ?? ''), (string)$data['salt'])
                && hash_equals((string)($stored['signature'] ?? ''), (string)$data['signature'])
                && (int)($stored['maxnumber'] ?? -1) === (int)$data['maxnumber']
                && (int)($stored['expires'] ?? 0) >= time();
            if (!$matchesSessionChallenge) return false;
        } else {
            // Compatibility for an already rendered form after an addon update.
            $expectedSignature = hash_hmac('sha256', $data['challenge'], self::secret());
            if (!hash_equals($expectedSignature, (string)$data['signature'])) return false;
        }

        $replay = hash('sha256', $encoded);
        if (!isset($_SESSION['wbce_altcha_used']) || !is_array($_SESSION['wbce_altcha_used'])) $_SESSION['wbce_altcha_used'] = array();
        foreach ($_SESSION['wbce_altcha_used'] as $usedHash=>$usedAt) if ((int)$usedAt < time()-900) unset($_SESSION['wbce_altcha_used'][$usedHash]);
        if (isset($_SESSION['wbce_altcha_used'][$replay])) return false;
        $_SESSION['wbce_altcha_used'][$replay] = time();
        unset($_SESSION[self::sessionKey($sectionId)]);
        return true;
    }
    private static function sessionKey($sectionId)
    {
        return 'wbce_altcha_challenge_' . ($sectionId !== '' ? $sectionId : 'default');
    }
    private static function widgetStyle(array $cfg)
    {
        $map=array('color_brand'=>'--altcha-brand','color_success'=>'--altcha-success','color_base'=>'--altcha-base','color_checkbox'=>'--altcha-checkbox','color_text'=>'--altcha-text','border_radius'=>'--altcha-radius');
        $style='';
        foreach($map as $key=>$property){$value=(string)($cfg[$key]??'');if($value!==''&&preg_match('/^#[0-9a-f]{6}$/i',$value))$style.=$property.':'.htmlspecialchars($value,ENT_QUOTES,'UTF-8').';';}
        return $style;
    }
    private static function logoSvg($class)
    {
        return '<svg class="'.htmlspecialchars($class,ENT_QUOTES,'UTF-8').'" viewBox="0 0 20 20" aria-label="ALTCHA"><path d="M2.33955 16.4279C5.88954 20.6586 12.1971 21.2105 16.4279 17.6604C18.4699 15.947 19.6548 13.5911 19.9352 11.1365L17.9886 10.4279C17.8738 12.5624 16.909 14.6459 15.1423 16.1284C11.7577 18.9684 6.71167 18.5269 3.87164 15.1423C1.03163 11.7577 1.4731 6.71166 4.8577 3.87164C8.24231 1.03162 13.2883 1.4731 16.1284 4.8577C16.9767 5.86872 17.5322 7.02798 17.804 8.2324L19.9522 9.01429C19.7622 7.07737 19.0059 5.17558 17.6604 3.57212C14.1104-.658624 7.80283-1.21043 3.57212 2.33956Z" fill="currentColor"/><path d="M7 10H5C5 12.7614 7.23858 15 10 15C12.7614 15 15 12.7614 15 10H13C13 11.6569 11.6569 13 10 13C8.3431 13 7 11.6569 7 10Z" fill="currentColor"/></svg>';
    }
    private static function flag($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    private static function escape($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
    private static function secret()
    {
        $secret=(string)Settings::Get('captcha_altcha_secret','');
        if(strlen($secret)>=32)return $secret;
        $secret=self::randomHex(32);
        $error=Settings::Set('captcha_altcha_secret',$secret);
        if($error)throw new RuntimeException((string)$error);
        return $secret;
    }
}
