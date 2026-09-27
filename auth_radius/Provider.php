<?php
final class WbceAuthRadiusProvider implements WbceAuthenticationProviderInterface
{
    public function getId() { return 'auth_radius'; }
    public function getName() { return 'RADIUS'; }
    private function settings(): array { $v = Settings::get('auth_radius_settings', []); return is_array($v) ? $v : []; }
    public function provisionUser(array $credentials, $database): ?array
    {
        $s=$this->settings();
        if (empty($s['allow_local_user_creation']) || empty($s['default_group_id'])) return null;
        $username=trim((string)($credentials['username']??''));
        if ($username==='' || !$this->authenticate([], $credentials)) return null;
        $groupId=(int)$s['default_group_id'];
        if (!(int)$database->fetchValue('SELECT `group_id` FROM `{TP}groups` WHERE `group_id` = ?', [$groupId])) return null;
        $display=str_replace('{username}', $username, trim((string)($s['default_display_name'] ?? '{username}')));
        $domain=ltrim(trim((string)($s['default_email_domain'] ?? '')), '@');
        $email=$domain !== '' ? $username.'@'.$domain : '';
        $language=strtoupper(substr((string)($s['default_language'] ?? (defined('DEFAULT_LANGUAGE') ? DEFAULT_LANGUAGE : 'EN')),0,2));
        $timezone=(string)($s['default_timezone'] ?? ''); if ($timezone !== '' && !in_array($timezone, DateTimeZone::listIdentifiers(), true)) $timezone='';
        $database->insertRow('{TP}users', ['group_id'=>$groupId,'groups_id'=>(string)$groupId,'active'=>1,'username'=>$username,'display_name'=>$display,'email'=>$email,'language'=>$language,'timezone'=>$timezone,'password'=>password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),'signup_timestamp'=>time(),'signup_confirmcode'=>'External provider provisioned']);
        if ($database->hasError()) return null; $id=(int)$database->lastInsertId();
        return $id>0 ? $database->fetchRow('SELECT * FROM `{TP}users` WHERE `user_id` = ?',[$id]) : null;
    }
    public function authenticate(array $user, array $credentials)
    {
        $s = $this->settings();
        if (empty($s['configured']) || empty($s['enabled'])) return false;
        if (!function_exists('radius_auth_open') || empty($s['host']) || empty($s['secret'])) return false;
        $radius=@radius_auth_open(); if (!$radius) return false;
        @radius_add_server($radius, (string)$s['host'], (int)($s['port'] ?: 1812), (string)$s['secret'], 5, 3);
        @radius_create_request($radius, RADIUS_ACCESS_REQUEST); @radius_put_attr($radius, RADIUS_USER_NAME, (string)($credentials['username'] ?? ''));
        @radius_put_attr($radius, RADIUS_USER_PASSWORD, (string)($credentials['password'] ?? '')); $result=@radius_send_request($radius); @radius_close($radius); return $result === RADIUS_ACCESS_ACCEPT;
    }
}
