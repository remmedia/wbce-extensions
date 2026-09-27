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

global $allowed_suffixes;

require_once __DIR__.'/functions.inc.php';

// Get id
if (!isset($_POST['group_id']) or !is_numeric($_POST['group_id'])) {
    header("Location: ".ADMIN_URL."/pages/index.php");
    exit(0);
} else {
    $group_id = intval($_POST['group_id']);
}

// Include WB admin wrapper script
$update_when_modified = true; // Tells script to update when this page was last updated
$admin_header = false;
require WB_PATH.'/modules/admin.php';

if (!defined('CAT_PATH')) {
    if (!$admin->checkFTAN()) {
        if (!mod_nwi_is_async_request()) { $admin->print_header(); }
        mod_nwi_admin_result($admin, false,
            $MESSAGE['GENERIC_SECURITY_ACCESS']
         .' (FTAN)', ADMIN_URL.'/pages/index.php');
        if (!mod_nwi_is_async_request()) { $admin->print_footer(); }
        exit;
    } else {
        if (!mod_nwi_is_async_request()) { $admin->print_header(); }
    }
}

// Validate all fields
if ($admin->get_post('title') == '') {
    mod_nwi_admin_result($admin, false, $MESSAGE['GENERIC_FILL_IN_ALL'], WB_URL.'/modules/news_img/modify_group.php?page_id='.$page_id.'&section_id='.$section_id.'&group_id='.$admin->getIDKEY($group_id).'&tab=g');
    $admin->print_footer();
    exit();
} else {
    $title = mod_nwi_escapeString($admin->get_post('title'));
    $active = mod_nwi_escapeString($admin->get_post('active'));
    $title = strip_tags($title);
}

// Update row
$database->query(sprintf(
    "UPDATE `%smod_news_img_groups` SET `title`='$title', `active`='$active' WHERE `group_id`='$group_id'",
    TABLE_PREFIX
));

// Check if the user uploaded an image or wants to delete one
$stagedImageError = '';
if (!empty($_POST['nwi_staged_image'])) {
    $stagedImageError = mod_nwi_commit_staged_image($_POST['nwi_staged_image'], 'group', $group_id, $section_id);
    if ($stagedImageError !== '') {
        mod_nwi_admin_result($admin, false, $stagedImageError, WB_URL.'/modules/news_img/modify_group.php?page_id='.$page_id.'&section_id='.$section_id.'&group_id='.$admin->getIDKEY($group_id).'&tab=g');
    }
}
if (empty($_POST['nwi_staged_image']) && isset($_FILES['image']['tmp_name']) && $_FILES['image']['tmp_name'] != '') {
    // Get real filename and set new filename
    $filename = $_FILES['image']['name'];
    $suffix   = strtolower(pathinfo($filename,PATHINFO_EXTENSION));
    $new_filename = WB_PATH.MEDIA_DIRECTORY.'/.news_img/image'.$group_id.'.'.$suffix;
    if (!mod_nwi_is_valid_image_upload($_FILES['image'], $allowed_suffixes)) {
        mod_nwi_admin_result($admin, false, $MESSAGE['GENERIC_FILE_TYPE'].' JPG (JPEG), GIF, PNG '.$TEXT['OR'].' WebP', WB_URL.'/modules/news_img/modify_group.php?page_id='.$page_id.'&section_id='.$section_id.'&group_id='.$admin->getIDKEY($group_id).'&tab=g');
    }
    // Make sure the target directory exists
    make_dir(WB_PATH.MEDIA_DIRECTORY.'/.news_img');
    // Upload image
    move_uploaded_file($_FILES['image']['tmp_name'], $new_filename);
    // Check if we need to create a thumb
    $query_settings = $database->query("SELECT `resize_preview`,`crop_preview` FROM `".TABLE_PREFIX."mod_news_img_settings` WHERE `section_id` = '$section_id'");
    $fetch_settings = $query_settings->fetchRow();
    $previewwidth = $previewheight = 0;
    if (substr_count($fetch_settings['resize_preview'], 'x')>0) {
        list($previewwidth, $previewheight) = explode('x', $fetch_settings['resize_preview'], 2);
    }
    if ($previewwidth != 0) {
        // Resize the image
        $thumb_location = WB_PATH.MEDIA_DIRECTORY.'/.news_img/thumb'.$group_id.'.'.$suffix;
        if (list($w, $h) = getimagesize($new_filename)) {
            if ($w>$previewwidth || $h>$previewheight) {
                mod_nwi_image_resize($new_filename, $thumb_location, $previewwidth, $previewheight, $fetch_settings['crop_preview']);
                unlink($new_filename);
                rename($thumb_location, $new_filename);
            }
        }
    }
}
if (isset($_POST['delete_image']) and $_POST['delete_image'] != '') {
    // Try unlinking image
    foreach(array_values(array('png','jpg','jpeg','gif')) as $ext) {
        if (file_exists(WB_PATH.MEDIA_DIRECTORY.'/.news_img/image'.$group_id.'.'.$ext)) {
            unlink(WB_PATH.MEDIA_DIRECTORY.'/.news_img/image'.$group_id.'.'.$ext);
        }
    }
}

if ($database->is_error()) {
    mod_nwi_admin_result($admin, false, $database->get_error(), WB_URL.'/modules/news_img/modify_group.php?page_id='.$page_id.'&section_id='.$section_id.'&group_id='.$admin->getIDKEY($group_id).'&tab=g');
} else {
    mod_nwi_admin_result($admin, true, $TEXT['SUCCESS'], ADMIN_URL.'/pages/modify.php?page_id='.$page_id.'&tab=g');
}

// Print admin footer
$admin->print_footer();
