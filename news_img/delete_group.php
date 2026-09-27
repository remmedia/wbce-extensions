<?php
/**
 *
 * @category        modules
 * @package         news_img
 * @author          WBCE Community
 * @copyright       2004-2009, Ryan Djurovich
 * @copyright       2009-2010, Website Baker Org. e.V.
 * @copyright       2019-, WBCE Community
 * @link            https://www.wbce.org/
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WBCE
 *
 */

require_once __DIR__.'/functions.inc.php';

// Include WB admin wrapper script
$update_when_modified = true;
$admin_header = false;
require(WB_PATH.'/modules/admin.php');
if (!mod_nwi_is_async_request()) { $admin->print_header(); }
$group_id = $admin->checkIDKEY('group_id', 0, 'GET');
if (!$group_id){
    if (mod_nwi_is_async_request()) { mod_nwi_admin_result($admin, false, $MESSAGE['GENERIC_SECURITY_ACCESS'], ADMIN_URL.'/pages/index.php'); }
    $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS']
	 .' (IDKEY) '.__FILE__.':'.__LINE__,
         ADMIN_URL.'/pages/index.php');
    $admin->print_footer();
    exit();
}


$group_id = (int) $group_id;
$database->query("UPDATE `".TABLE_PREFIX."mod_news_img_posts` SET `group_id` = '0' where `group_id`='$group_id'");
// Update row
$database->query("DELETE FROM `".TABLE_PREFIX."mod_news_img_groups` WHERE `group_id` = '$group_id'");
// Check if there is a db error, otherwise say successful
$redirect = ADMIN_URL.'/pages/modify.php?page_id='.(int) $page_id.'&tab=g';
mod_nwi_admin_result($admin, !$database->is_error(), $database->is_error() ? $database->get_error() : $TEXT['SUCCESS'], $redirect);

// Print admin footer
$admin->print_footer();
