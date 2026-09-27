<?php
final class WbceAuthLdapProvider implements WbceAuthenticationProviderInterface
{
    public function getId() { return 'auth_ldap'; }
    public function getName() { return 'LDAP / Active Directory'; }
    private function settings(): array { $v = Settings::get('auth_ldap_settings', []); return is_array($v) ? $v : []; }
    public function provisionUser(array $credentials, $database): ?array
    {
        $s=$this->settings(); if (empty($s['allow_local_user_creation']) || empty($s['default_group_id'])) return null;
        $username=trim((string)($credentials['username']??'')); if ($username==='' || !$this->authenticate([], $credentials)) return null;
        $groupId=(int)$s['default_group_id'];
        if (!(int)$database->fetchValue('SELECT `group_id` FROM `{TP}groups` WHERE `group_id` = ?', [$groupId])) return null;
        $password=bin2hex(random_bytes(32));
        $display=str_replace('{username}', $username, trim((string)($s['default_display_name'] ?? '{username}')));
        $domain=ltrim(trim((string)($s['default_email_domain'] ?? '')), '@');
        $email=$domain !== '' ? $username.'@'.$domain : '';
        $language=strtoupper(substr((string)($s['default_language'] ?? (defined('DEFAULT_LANGUAGE') ? DEFAULT_LANGUAGE : 'EN')),0,2));
        $timezone=(string)($s['default_timezone'] ?? ''); if ($timezone !== '' && !in_array($timezone, DateTimeZone::listIdentifiers(), true)) $timezone='';
        $database->insertRow('{TP}users', ['group_id'=>$groupId,'groups_id'=>(string)$groupId,'active'=>1,'username'=>$username,'display_name'=>$display,'email'=>$email,'language'=>$language,'timezone'=>$timezone,'password'=>password_hash($password,PASSWORD_DEFAULT),'signup_timestamp'=>time(),'signup_confirmcode'=>'LDAP provisioned']);
        if ($database->hasError()) return null; $id=(int)$database->lastInsertId();
        return $id>0 ? $database->fetchRow('SELECT * FROM `{TP}users` WHERE `user_id` = ?',[$id]) : null;
    }
    public function authenticate(array $user, array $credentials)
    {
        $s = $this->settings();
        if (empty($s['configured']) || empty($s['enabled'])) return false;
        if (!function_exists('ldap_connect') || empty($s['host'])) return false;
        $connection = @ldap_connect((string)$s['host'], (int)($s['port'] ?: 636));
        if (!$connection) return false;
        @ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        if (($s['encryption'] ?? '') === 'starttls') @ldap_start_tls($connection);
        $filter = str_replace('{username}', ldap_escape((string)($credentials['username'] ?? ''), '', LDAP_ESCAPE_FILTER), (string)($s['user_filter'] ?? '(uid={username})'));
        $bindDn=(string)($s['bind_dn'] ?? ''); $bindPassword=(string)($s['bind_password'] ?? '');
        if ($bindDn !== '' && !@ldap_bind($connection, $bindDn, $bindPassword)) return false;
        $search=@ldap_search($connection, (string)($s['base_dn'] ?? ''), $filter, ['dn']); $entries=$search ? @ldap_get_entries($connection, $search) : false;
        if (!is_array($entries) || empty($entries['count']) || $entries['count'] !== 1) return false;
        return @ldap_bind($connection, (string)$entries[0]['dn'], (string)($credentials['password'] ?? ''));
    }
}
