<?php
/**
 *
 * @category        admintool
 * @package         wbstats
 * @author          Ruud Eisinga - dev4me.com
 * @link			https://dev4me.com/
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WebsiteBaker 2.8.x / WBCE 1.4
 * @requirements    PHP 7 and higher
 * @version         0.2.5.8
 * @lastmodified    November 21, 2025
 *
 */

// Add to the top of your template (within the <?php)
// include ( WB_PATH.'/modules/wbstats/count.php');

// Add to the /config.php, just before the initialize line
// $referer = $_SERVER['HTTP_REFERER'];

if (!function_exists('wbstats_tracking_enabled')) {
	function wbstats_tracking_enabled() {
		global $database;
		if (!isset($database) || !is_object($database) || !method_exists($database, 'query')) return false;
		$result = $database->query("SELECT `value` FROM `".TABLE_PREFIX."mod_wbstats_cfg` WHERE `type`='system' AND `name`='enabled' ORDER BY `id` DESC LIMIT 1");
		$row = $result && method_exists($result, 'fetchRow') ? $result->fetchRow() : null;
		return !$row || (string)($row['value'] ?? '1') === '1';
	}
}
if (!defined('WBSTATS_COUNTED') && wbstats_tracking_enabled()) {
	define('WBSTATS_COUNTED', true);
	require_once __DIR__.'/class.count.php';
	new counter();
}
?>
