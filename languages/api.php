<?php
/** Standalone API endpoint: never render the surrounding admin template. */
$configFile = dirname(dirname(dirname(__FILE__))) . '/config.php';
if (!is_file($configFile)) { http_response_code(500); exit; }
require $configFile;
require_once WB_PATH . '/framework/functions.php';
require_once WB_PATH . '/framework/Database.php';
if (!isset($database) || !($database instanceof Database)) {
    $database = new Database();
}
require_once WB_PATH . '/framework/Admin.php';
require_once __DIR__ . '/LanguageRepository.php';
$admin = new admin('Admintools', 'admintools', false, false);
if (!$admin->is_authenticated() || !$admin->isAdmin()) { http_response_code(403); exit; }

function languages_api_value($value): string { return is_scalar($value) ? trim((string)$value) : ''; }
function languages_api_json(array $payload, int $status = 200): void { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); exit; }

try {
    $repository = new WbceLanguageRepository();
    $action = languages_api_value($_GET['languages_api'] ?? '');
    $cmsLanguage = defined('LANGUAGE') ? strtoupper((string)LANGUAGE) : 'EN';
    if ($action === 'files') {
        $language = strtoupper(languages_api_value($_GET['language'] ?? ''));
        if (!preg_match('/^[A-Z]{2,5}$/', $language)) throw new RuntimeException('Ungültige Sprache.');
        languages_api_json(array('ok'=>true, 'files'=>$repository->files($language)));
    }
    if ($action === 'document') {
        $language = strtoupper(languages_api_value($_GET['language'] ?? ''));
        languages_api_json(array('ok'=>true, 'document'=>$repository->document(languages_api_value($_GET['file'] ?? ''), $language, $cmsLanguage)));
    }
    if ($action === 'save') {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !$admin->checkFTAN()) throw new RuntimeException('Die Sicherheitsprüfung ist fehlgeschlagen. Bitte die Seite neu laden.');
        $repository->save(languages_api_value($_POST['file'] ?? ''), languages_api_value($_POST['key'] ?? ''), (string)($_POST['value'] ?? ''));
        languages_api_json(array('ok'=>true, 'message'=>'Übersetzung gespeichert.'));
    }
    if ($action === 'export') {
        $language = strtoupper(languages_api_value($_GET['language'] ?? ''));
        $export = $repository->exportLanguage($language);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="wbce-language-' . $language . '.json"');
        echo json_encode($export, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
    throw new RuntimeException('Unbekannte Aktion.');
} catch (Throwable $error) { languages_api_json(array('ok'=>false, 'message'=>$error->getMessage()), 400); }
