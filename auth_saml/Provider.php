<?php
final class WbceAuthSamlProvider implements WbceAuthenticationProviderInterface
{
    public function getId() { return 'auth_saml'; }
    public function getName() { return 'SAML 2.0'; }
    private function settings(): array { $v = Settings::get('auth_saml_settings', []); return is_array($v) ? $v : []; }
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
        // This provider requires its protocol-specific browser or callback flow.
        // It deliberately never falls back to the local password provider.
        return false;
    }
}
