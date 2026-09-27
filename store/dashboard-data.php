<?php
require_once dirname(__DIR__, 2).'/config.php';
$admin = new Admin('Start', 'start', false);
require_once __DIR__.'/Language.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (!$admin->get_permission('admintools')) {
    http_response_code(403);
    echo json_encode(array('ok'=>false,'message'=>wbce_store_text('dashboard_forbidden')));
    exit;
}
require_once __DIR__.'/UpdateChecker.php';
$ftan = method_exists($admin,'getFTAN') ? (string)$admin->getFTAN() : '';
$ftanName = $ftanValue = '';
if (preg_match('/name=["\']([^"\']+)["\'][^>]*value=["\']([^"\']*)["\']/i', $ftan, $match)) {
    $ftanName = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
    $ftanValue = html_entity_decode($match[2], ENT_QUOTES, 'UTF-8');
}
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
try {
    // This endpoint runs asynchronously after the dashboard is visible. Store
    // Servers provide a cached catalogue snapshot, so this also reflects a
    // newly published package without delaying the dashboard itself.
    $report = WbceStoreUpdateChecker::report($database);
    $updates = array();
    foreach ($report['compatible'] as $update) {
        $package=$update['package'];$source=$update['source'];
        $name=isset($package['name'])?(string)$package['name']:(string)$package['slug'];
        $updates[]=array(
            'name'=>$name,'version'=>(string)$package['version'],'source_id'=>(int)$source['id'],
            'package_ref'=>$package['type'].'|'.$package['slug'].'|'.$package['version'],
            'details'=>wbce_store_text('dashboard_update_details',array('installed'=>$update['installed'],'version'=>$package['version'],'store'=>$source['name'])),
            'ftan_name'=>$ftanName,'ftan_value'=>$ftanValue,
        );
    }
    $count=count($updates);
    echo json_encode(array('ok'=>true,'updates'=>$updates,'incompatible'=>count($report['incompatible']),'summary'=>wbce_store_text($count===1?'dashboard_update_count_one':'dashboard_update_count_many',array('count'=>$count))),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    http_response_code(503);
    echo json_encode(array('ok'=>false,'message'=>wbce_store_text('dashboard_load_failed')),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
