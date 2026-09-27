<?php
/*      Drag'N'Drop Position
**/

header('Content-Type: application/json; charset=UTF-8');
require_once('../../../config.php');
require_once(WB_PATH.'/framework/class.admin.php');
require_once dirname(__DIR__).'/functions.inc.php';
$aJsonRespond = array();
$aJsonRespond['success'] = false;
$aJsonRespond['message'] = '';
$aJsonRespond['icon'] = '';


if (!isset($_POST['action']) || !isset($_POST['post_id']) && !isset($_POST['group_id']) && !isset($_POST['img_id'])) {
    $aJsonRespond['message'] = $MOD_NEWS_IMG['AJAX_INVALID_REQUEST'];
    exit(json_encode($aJsonRespond));
} else {
    // check if user has permissions to access the news_img module
    $admin = new admin('Pages', 'pages_modify', false, false);
    if (!($admin->is_authenticated() && $admin->get_permission('news_img', 'module'))) {
        http_response_code(403);
        $aJsonRespond['message'] = $MOD_NEWS_IMG['AJAX_PERMISSION_DENIED'];
        exit(json_encode($aJsonRespond));
    }
    if (!$admin->checkFTAN()) {
        http_response_code(403);
        $aJsonRespond['message'] = $MOD_NEWS_IMG['AJAX_SECURITY_FAILED'];
        exit(json_encode($aJsonRespond));
    }

    // Sanitize variables
    $action = $admin->add_slashes($_POST['action']);
    if ($action == "updatePosition") {
        if (isset($_POST['post_id'])) {
            $aRows = $_POST['post_id'];
            $i = count($aRows);
            foreach ($aRows as $recID) {
                if(!defined('CAT_PATH')) {
                    $id = $admin->checkIDKEY($recID, 0, 'key', true);
                    if (defined('WB_VERSION') && (version_compare(WB_VERSION, '2.8.3', '>'))) {
                        $id = $recID;
                    }
                } else {
                    $id = intval($recID);
                }
                if ($id<=0) {
                    $aJsonRespond['message'] = $MOD_NEWS_IMG['AJAX_INVALID_VALUE'];
                    exit(json_encode($aJsonRespond));
                }
                // now we sanitize array
                $database->query(
                     "UPDATE `".TABLE_PREFIX."mod_news_img_posts`"
                   . " SET `position` = '".$i."'"
                   . " WHERE `post_id` = ".intval($id)." "
                );
                $i--;
            }
        }
        if (isset($_POST['group_id'])) {
            $aRows = $_POST['group_id'];
            $i = 1;
            foreach ($aRows as $recID) {
                if(!defined('CAT_PATH')) {
                $id = $admin->checkIDKEY($recID, 0, 'key', true);
                if (defined('WB_VERSION') && (version_compare(WB_VERSION, '2.8.3', '>'))) {
                    $id = $recID;
                    }
                } else {
                    $id = intval($recID);
                }
                if ($id<=0) {
                    $aJsonRespond['message'] = $MOD_NEWS_IMG['AJAX_INVALID_VALUE'];
                    exit(json_encode($aJsonRespond));
                }
                // now we sanitize array
                $database->query("UPDATE `".TABLE_PREFIX."mod_news_img_groups`"
               . " SET `position` = '".$i."'"
               . " WHERE `group_id` = ".intval($id)." ");
                $i++;
            }
        }
        if (isset($_POST['img_id'])) {
            $aRows = $_POST['img_id'];
            $i = 1;
            foreach ($aRows as $recID) {
                $id = $admin->checkIDKEY($recID, 0, 'key', true);
                if (defined('WB_VERSION') && (version_compare(WB_VERSION, '2.8.3', '>'))) {
                    $id = $recID;
                }
                if ($id<=0) {
                    $aJsonRespond['message'] = $MOD_NEWS_IMG['AJAX_INVALID_VALUE'];
                    exit(json_encode($aJsonRespond));
                }
                // now we sanitize array
                $database->query("UPDATE `".TABLE_PREFIX."mod_news_img_img`"
               . " SET `position` = '".$i."'"
               . " WHERE `id` = ".intval($id)." ");
                $i++;
            }
        }
        if ($database->is_error()) {
            $aJsonRespond['success'] = false;
            $aJsonRespond['message'] = $MOD_NEWS_IMG['AJAX_DB_FAILED'];
            $aJsonRespond['icon'] = 'cancel.gif';
            exit(json_encode($aJsonRespond));
        }
    } else {
        $aJsonRespond['message'] = $MOD_NEWS_IMG['AJAX_INVALID_ACTION'];
        exit(json_encode($aJsonRespond));
    }

    $aJsonRespond['icon'] = 'ajax-loader.gif';
    $aJsonRespond['message'] = $MOD_NEWS_IMG['AJAX_SORT_SAVED'];
    $aJsonRespond['success'] = true;
    $aJsonRespond['ftan'] = $admin->getFTAN();
    exit(json_encode($aJsonRespond));
}
