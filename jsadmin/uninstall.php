<?php
/**
 *
 * @category        modules
 * @package         JsAdmin
 * @author          WebsiteBaker Project, modified by Swen Uth for WBCE
 * @copyright       (C) 2006, Stepan Riha, 2009-2011, Website Baker Org. e.V.
 * @link			http://www.websitebaker2.org/
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WebsiteBaker 2.8.x
 * @requirements    PHP 5.2.2 and higher
 * @version         $Id: uninstall.php 1537 2011-12-10 11:04:33Z Luisehahne $
 * @filesource		$HeadURL: svn://isteam.dynxs.de/wb_svn/wb280/tags/2.8.3/wb/modules/jsadmin/uninstall.php $
 * @lastmodified    $Date: 2011-12-10 12:04:33 +0100 (Sa, 10. Dez 2011) $
 *
*/

// prevent this file from being accessed directly
/* -------------------------------------------------------- */
if(defined('WB_PATH') == false)
{
	// Stop this file being access directly
		die('<head><title>Access denied</title></head><body><h2 style="color:red;margin:3em auto;text-align:center;">Cannot access this file directly</h2></body></html>');
}
/* -------------------------------------------------------- */

$table = TABLE_PREFIX ."mod_jsadmin";
require_once __DIR__.'/jsadmin.php';
require __DIR__.'/languages/EN.php';
if(defined('LANGUAGE')&&LANGUAGE!=='EN'&&preg_match('/^[A-Z]{2}$/D',(string)LANGUAGE)&&is_readable(__DIR__.'/languages/'.LANGUAGE.'.php'))require __DIR__.'/languages/'.LANGUAGE.'.php';
if (isset($database) && is_object($database) && method_exists($database, 'query')) {
	$result=$database->query("DROP TABLE IF EXISTS `$table`");
	if(!jsadmin_query_ok($result))throw new RuntimeException($MOD_JSADMIN['TXT_UNINSTALL_FAILED']);
} else {
	throw new RuntimeException($MOD_JSADMIN['TXT_UNINSTALL_FAILED']);
}
