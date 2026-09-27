<?php
final class WbceCapCaptchaProvider
{
    public static function render(array $context)
    {
        $endpoint = rtrim((string)Settings::Get('captcha_cap_endpoint', ''), '/');
        $siteKey = (string)Settings::Get('captcha_cap_site_key', '');
        $secret = (string)Settings::Get('captcha_cap_secret', '');
        $text=WbceCaptchaProviderUi::language(__DIR__,array('not_configured'=>'CAP is not configured yet.','initial'=>'Security check','verifying'=>'Verifying …','solved'=>'Successfully verified'));
        if (!self::validEndpoint($endpoint) || $secret === '') return '<p class="error">'.htmlspecialchars($text['not_configured'],ENT_QUOTES,'UTF-8').'</p>';
        $siteEndpoint = $endpoint . ($siteKey !== '' ? '/' . rawurlencode($siteKey) : '');
        $api = htmlspecialchars($siteEndpoint, ENT_QUOTES, 'UTF-8');
        $asset = htmlspecialchars($endpoint . '/assets/widget.js', ENT_QUOTES, 'UTF-8');
        return '<script type="module" src="' . $asset . '"></script>'
            . '<cap-widget required data-cap-api-endpoint="' . $api . '/" data-cap-i18n-initial-state="'.htmlspecialchars($text['initial'],ENT_QUOTES,'UTF-8').'" data-cap-i18n-verifying-label="'.htmlspecialchars($text['verifying'],ENT_QUOTES,'UTF-8').'" data-cap-i18n-solved-label="'.htmlspecialchars($text['solved'],ENT_QUOTES,'UTF-8').'"></cap-widget>';
    }

    public static function verify($input, array $context)
    {
        $request = isset($context['request']) && is_array($context['request']) ? $context['request'] : array();
        $token = isset($request['cap-token']) ? $request['cap-token'] : (isset($request['cap_token']) ? $request['cap_token'] : '');
        if (!is_scalar($token)) return false;
        $token = trim((string)$token);
        $endpoint = rtrim((string)Settings::Get('captcha_cap_endpoint', ''), '/');
        $secret = (string)Settings::Get('captcha_cap_secret', '');
        if ($token === '' || !self::validEndpoint($endpoint) || $secret === '') return false;
        $siteKey = (string)Settings::Get('captcha_cap_site_key', '');
        $siteEndpoint = $endpoint . ($siteKey !== '' ? '/' . rawurlencode($siteKey) : '');
        $payload = json_encode(array('secret' => $secret, 'response' => $token), JSON_UNESCAPED_SLASHES);
        if (!is_string($payload)) return false;
        $json = self::postJson($siteEndpoint . '/siteverify', $payload);
        $result = is_string($json) ? json_decode($json, true) : null;
        return is_array($result) && !empty($result['success']);
    }

    public static function normalizeEndpoint($value)
    {
        $value=rtrim(trim((string)$value),'/');
        return self::validEndpoint($value)?$value:'';
    }

    private static function validEndpoint($value)
    {
        if(!is_string($value)||filter_var($value,FILTER_VALIDATE_URL)===false)return false;
        $parts=parse_url($value);
        return is_array($parts)&&strtolower((string)($parts['scheme']??''))==='https'&&!empty($parts['host'])&&!isset($parts['user'])&&!isset($parts['pass'])&&!isset($parts['query'])&&!isset($parts['fragment']);
    }

    private static function postJson($url, $payload)
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl === false) return false;
            curl_setopt_array($curl, array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Accept: application/json'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            ));
            $body = curl_exec($curl);
            $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            if (PHP_VERSION_ID < 80500) curl_close($curl);
            return is_string($body) && $status >= 200 && $status < 300 && strlen($body) <= 1048576 ? $body : false;
        }
        $options = array('http' => array('method' => 'POST', 'header' => "Content-Type: application/json\r\nAccept: application/json\r\n", 'content' => $payload, 'timeout' => 8, 'ignore_errors' => false, 'follow_location' => 0));
        $stream = @fopen($url, 'rb', false, stream_context_create($options));
        if (!is_resource($stream)) return false;
        $body = stream_get_contents($stream, 1048577);
        fclose($stream);
        return is_string($body) && strlen($body) <= 1048576 ? $body : false;
    }
}
