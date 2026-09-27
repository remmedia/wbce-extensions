<?php
/**
 * WebsiteBaker CMS AdminTool: wbSeoTool
 *
 * tool.php
 * 
 * @platform    CMS WebsiteBaker 2.8.x
 * @package     wbSeoTool
 * @author      Christian M. Stefan (Stefek)
 * @copyright   Christian M. Stefan
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 */

// prevent this file from being accessed directly
if(!defined('WB_PATH')) exit('Direct access is not permitted.');
require_once __DIR__.'/compat.php';

// user needs permission for admintools OR pages
if(!$admin->get_permission('admintools') || !$admin->get_permission('pages')) {
	exit(isset($TEXT['INSUFFICIENT_PRIVILEGES']) ? $TEXT['INSUFFICIENT_PRIVILEGES'] : 'Insufficient privileges.');
}

// Load Language Files
if(LANGUAGE_LOADED) {
	if(LANGUAGE != 'EN'){
		// load EN language file in case foreign languages lack of key=>value pairs
		require_once((dirname(__FILE__)) . '/languages/EN.php');
	}
	$sLangFile = (dirname(__FILE__)) . '/languages/' . LANGUAGE . '.php';
	require_once(!file_exists($sLangFile) ? (dirname(__FILE__)) . '/languages/EN.php' : $sLangFile );	
}

//	GET TOOL SETTINGS FROM DB (Json Array)
$jsonSettings = wb_seo_db_value($database, "SELECT `settings_json` FROM `".TABLE_PREFIX."mod_page_seo_tool`");
$aSettings = json_decode($jsonSettings, TRUE);

if(!defined('REWRITE_URL') && $aSettings['rewriteUrl']['use'] == TRUE ){
	define('REWRITE_URL', $aSettings['rewriteUrl']['dbString']);
}
if(!defined('KEYWORDS_CONFIG') && $aSettings['keywordsConfig']['use'] == TRUE ){
	define('KEYWORDS_CONFIG', $aSettings['keywordsConfig']['wordReplace']);
}
if(!defined('USE_FLAGS') && isset($aSettings['bUseFlags']) && $aSettings['bUseFlags'] == TRUE ){
	define('USE_FLAGS', $aSettings['bUseFlags']);
}
$bUseRemainingChars = $aSettings['bUseRemainingChars'];
$sCounterText = str_replace('{COUNTER_REMAINING}',($bUseRemainingChars == 1 ? $TOOL_TEXT['COUNTER_REMAINING'] : ''), $TOOL_TEXT['COUNTER_STRING'] );
$aSeoFtan = array();
parse_str($admin->getFTAN(false), $aSeoFtan);

// Load Functions File
$sFunctionsFile = dirname(__FILE__).'/functions.php';	
if(file_exists($sFunctionsFile)){
	require_once($sFunctionsFile);
}
#debug_dump($aSettings);
?>
<noscript><?php echo $TOOL_TEXT['NOSCRIPT_MESSAGE']; ?></noscript>
<script type="text/javascript" language="javascript">
	var CLICK2EDIT = <?php echo json_encode($TOOL_TEXT['CLICK2EDIT'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
	var CANCEL     = <?php echo json_encode($TEXT['CANCEL'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
	var MODULE_URL = <?php echo json_encode(WB_URL.'/modules/'.basename(__DIR__), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
	var SEO_FTAN = <?php echo json_encode($aSeoFtan, JSON_UNESCAPED_SLASHES); ?>;
	var sCounterText = <?php echo json_encode($sCounterText, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
	var iTitleCount_optimum = <?php echo $aSettings['iTitleCount']['optimum']; ?>;
	var iTitleCount_minimum = <?php echo $aSettings['iTitleCount']['minimum']; ?>;
	var iDescriptionCount_optimum = <?php echo $aSettings['iDescriptionCount']['optimum']; ?>;
	var iDescriptionCount_minimum = <?php echo $aSettings['iDescriptionCount']['minimum']; ?>;
	var iDescriptionCount_use = <?php echo $aSettings['iDescriptionCount']['use']; ?>;
	var iTitleCount_use = <?php echo $aSettings['iTitleCount']['use']; ?>;
</script>
<?php

$toolUrl = ADMIN_URL.'/admintools/tool.php?tool='.basename(dirname(__FILE__));
$position = isset($_GET["pos"]) ? $_GET["pos"] : '';

$sFileName = '';
switch ($position) {
	// no switch yet
	case 'config':
		$sFileName = 'modify_config';
	break;
	default:
		$sFileName = 'view_pageTree';
	break;
}
$sFile = dirname(__FILE__).'/'.$sFileName.'.php';
if(file_exists($sFile)) {
	require_once($sFile);
} else {
	echo sprintf($TOOL_TEXT['FILE_NOT_FOUND'], '<code>'.htmlspecialchars($sFileName.'.php', ENT_QUOTES, 'UTF-8').'</code>');
}
