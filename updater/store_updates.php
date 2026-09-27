<?php
require '../../config.php'; require_once WB_PATH.'/framework/Admin.php';
require_once __DIR__.'/update_transaction.php';
$admin=new admin('Admintools','admintools',false,false);
header('Content-Type: application/json; charset=utf-8');header('X-Content-Type-Options: nosniff');header('Cache-Control: no-store');
if(!$admin->is_authenticated()||!$admin->isAdmin()){http_response_code(403);echo json_encode(array('error'=>'Zugriff verweigert.'));exit;}
global $database;
$table=TABLE_PREFIX.'mod_store_sources';$check=$database->query("SHOW TABLES LIKE '".$database->escapeString($table)."'");if(!$check||$check->numRows()===0){echo json_encode(array('updates'=>array(),'error'=>'Es sind keine Store-Quellen eingerichtet.'));exit;}
// Use the established Store client whenever it is available. It applies the
// same HTTPS, DNS and token handling as the Store administration itself.
$storeClientFile=WB_PATH.'/modules/store/HttpClient.php';
if(is_file($storeClientFile))require_once $storeClientFile;
function updater_store_fetch($url,$token){
    if(class_exists('WbceRepositoryHttpClient')){
        $client=new WbceRepositoryHttpClient();
        // The Store client already validates its local snapshot. Prefer it for
        // a short interval: a slow remote Store must not keep the Updater UI
        // waiting just to rediscover the same package list.
        $json=$client->cachedJson($url,$token,45);
        if(!is_array($json)) $json=$client->json($url,$token);
        if(!is_array($json)||!is_array($json['packages']??null))throw new RuntimeException('Ungültiger Store-Katalog');
        return $json;
    }
    $ch=curl_init($url);if(!$ch)throw new RuntimeException('cURL nicht verfügbar');
    curl_setopt_array($ch,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>25,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_USERAGENT=>'WBCE-Updater/1.0',CURLOPT_HTTPHEADER=>$token!==''?array('Authorization: Bearer '.$token):array()));
    $body=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);if(PHP_VERSION_ID<80500)curl_close($ch);if(!is_string($body)||$code<200||$code>=300)throw new RuntimeException('Store nicht erreichbar');$json=json_decode($body,true);if(!is_array($json)||!is_array($json['packages']??null))throw new RuntimeException('Ungültiger Store-Katalog');return $json;
}
$current=defined('WBCE_VERSION')?WBCE_VERSION:'';$offers=array();$diagnostics=array();
// Use WBCE's session abstraction. Direct $_SESSION keys are not retained by
// every supported session replacement, which made the automatic timer lose its
// Store offer before download.php could validate it.
$offerStore=updater_transaction_session_get('WBCE_UPDATER_STORE_OFFERS',array());
if(!is_array($offerStore))$offerStore=array();
$offerStore=array_filter($offerStore,function($offer){return is_array($offer)&&!empty($offer['expires'])&&(int)$offer['expires']>=time();});
$sources=$database->query("SELECT `id`,`name`,`catalog_url`,`access_token` FROM `{TP}mod_store_sources` WHERE `active`=1");
while($sources&&($source=$sources->fetchRow(MYSQLI_ASSOC))){try{$catalog=updater_store_fetch($source['catalog_url'],$source['access_token']);$catalogHost=strtolower((string)parse_url($source['catalog_url'],PHP_URL_HOST));foreach($catalog['packages'] as $package){if(!is_array($package)||($package['type']??'')!=='cms'||($package['slug']??'')!=='wbce-cms'||!is_string($package['version']??null)||!preg_match('/^[a-f0-9]{64}$/i',(string)($package['sha256']??''))||(int)($package['size']??0)<1||(int)$package['size']>536870912||!filter_var($package['download_url']??'',FILTER_VALIDATE_URL)||parse_url($package['download_url'],PHP_URL_SCHEME)!=='https'||strtolower((string)parse_url($package['download_url'],PHP_URL_HOST))!==$catalogHost||($current!==''&&version_compare($package['version'],$current,'<=')))continue;$delta=null;foreach((array)($package['deltas']??array()) as $candidate){if(!is_array($candidate))continue;$from=(string)($candidate['from_version']??($candidate['source_version']??''));$deltaUrl=updater_delta_download_url($candidate,$catalogUrl);if(!updater_delta_is_preferred($current,$from,(string)($package['version']??''))||!preg_match('/^[a-f0-9]{64}$/i',(string)($candidate['sha256']??''))||(int)($candidate['size']??0)<1||!filter_var($deltaUrl,FILTER_VALIDATE_URL)||parse_url($deltaUrl,PHP_URL_SCHEME)!=='https'||strtolower((string)parse_url($deltaUrl,PHP_URL_HOST))!==$catalogHost)continue;$delta=array('url'=>$deltaUrl,'sha256'=>strtolower($candidate['sha256']),'size'=>(int)$candidate['size'],'from_version'=>$from);break;}$updateType=$delta!==null?'diff':'full';$id=bin2hex(random_bytes(16));$offerStore[$id]=array('url'=>$package['download_url'],'sha256'=>strtolower($package['sha256']),'version'=>$package['version'],'size'=>(int)$package['size'],'token'=>$source['access_token'],'delta'=>$delta,'update_type'=>$updateType,'expires'=>time()+900);$offers[]=array('offer_id'=>$id,'version'=>$package['version'],'name'=>$package['name']??'WBCE CMS','source'=>$source['name'],'published_at'=>$package['created_at']??null,'checksum'=>$package['sha256'],'download_url'=>$package['download_url'],'update_type'=>$updateType,'download_size'=>(int)($delta['size']??$package['size']),'delta_available'=>$delta!==null);}}catch(Throwable $error){$diagnostics[]=(string)$source['name'].': '.$error->getMessage();}}
updater_transaction_session_set('WBCE_UPDATER_STORE_OFFERS',$offerStore);
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
usort($offers,function($a,$b){return version_compare($b['version'],$a['version']);});$response=array('updates'=>$offers);if(!$offers&&$diagnostics)$response['error']='Keine Store-Antwort konnte verarbeitet werden: '.implode(' | ',$diagnostics);echo json_encode($response,JSON_UNESCAPED_UNICODE);
