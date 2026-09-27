<?php
/**
 *
 * @category        modules
 * @package         wrapper
 * @author          WebsiteBaker Project
 * @copyright       2009-2011, Website Baker Org. e.V.
 * @link			http://www.websitebaker2.org/
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WebsiteBaker 2.8.x
 * @requirements    PHP 5.2.2 and higher
 * @version      	$Id: view.php 1538 2011-12-10 15:06:15Z Luisehahne $
 * @filesource		$HeadURL: http://svn.websitebaker2.org/branches/2.8.x/wb/modules/wrapper/install.php $
 * @lastmodified    $Date: 2011-01-10 13:21:47 +0100 (Mo, 10 Jan 2011) $
 *
 */

// Must include code to stop this file being access directly
/* -------------------------------------------------------- */
if(defined('WB_PATH') == false)
{
	// Stop this file being access directly
		die('<head><title>Access denied</title></head><body><h2 style="color:red;margin:3em auto;text-align:center;">Cannot access this file directly</h2></body></html>');
}
/* -------------------------------------------------------- */

// check if module language file exists for the language set by the user (e.g. DE, EN)
if(!file_exists(WB_PATH .'/modules/wrapper/languages/'.LANGUAGE .'.php')) {
	// no module language file exists for the language set by the user, include default module language file EN.php
	require_once(WB_PATH .'/modules/wrapper/languages/EN.php');
} else {
	// a module language file exists for the language defined by the user, load it
	require_once(WB_PATH .'/modules/wrapper/languages/'.LANGUAGE .'.php');
}

// get url
$get_settings = $database->query("SELECT url,height FROM ".TABLE_PREFIX."mod_wrapper WHERE section_id = '$section_id'");
$fetch_settings = $get_settings->fetchRow();
$url = str_replace('[WB_URL]', WB_URL, (string) $fetch_settings['url']);
$urlScheme = parse_url($url, PHP_URL_SCHEME);
if ($urlScheme !== null && $urlScheme !== false && !in_array(strtolower($urlScheme), array('http', 'https'), true)) {
    $url = 'about:blank';
}
$escapedUrl = htmlspecialchars((string) $url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$frameTitle = isset($wb->page['page_title']) ? (string) $wb->page['page_title'] : (string) $url;
$escapedFrameTitle = htmlspecialchars($frameTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$frameHeight = max(1, (int) $fetch_settings['height']);

?>
<iframe src="<?php echo $escapedUrl; ?>" title="<?php echo $escapedFrameTitle; ?>" width="100%" height="<?php echo $frameHeight; ?>" loading="lazy" frameborder="0" scrolling="auto">
<?php echo $MOD_WRAPPER['NOTICE']; ?>
<a href="<?php echo $escapedUrl; ?>" target="_blank" rel="noopener noreferrer"><?php echo $escapedUrl; ?></a>
</iframe>
