<?php

require __DIR__ . '/bootstrap.php';

if (!isset($_POST['purpose'], $_POST['idkey'])
    || !is_scalar($_POST['purpose']) || !is_scalar($_POST['idkey'])
    || (string) $_POST['purpose'] !== 'convert_filter') {
    opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_INVALID'), 400);
}

require_once WB_PATH . '/modules/outputfilter_dashboard/functions.php';
$id = (int) $admin->checkIDKEY((string) $_POST['idkey'], 0, 'POST', true);
if ($id <= 0) {
    opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_INVALID'), 400);
}

$before = opf_get_data($id);
if (!is_array($before) || (((int) ($before['userfunc'] ?? 0)) !== 1 && trim((string) ($before['plugin'] ?? '')) === '')) {
    opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_PROTECTED'), 403);
}

$convert_message = '';
$convert_ok = false;
$result = include dirname(__DIR__) . '/convert.php';
if (!$result || !$convert_ok) {
    opf_ajax_reply(false, $convert_message !== '' ? $convert_message : opf_ajax_text('TXT_AJAX_CONVERT_FAILED'), 500);
}

$type = trim((string) ($before['plugin'] ?? '')) === '' ? 'plugin' : 'inline';
opf_ajax_reply(true, opf_ajax_text('TXT_AJAX_CONVERTED'), 200, array('type' => $type));
