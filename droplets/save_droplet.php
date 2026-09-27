<?php
/**
 * WBCE CMS
 * Way Better Content Editing.
 * Visit https://wbce.org to learn more and to join the community.
 *
 * @copyright Ryan Djurovich (2004-2009)
 * @copyright WebsiteBaker Org. e.V. (2009-2015)
 * @copyright WBCE Project (2015-)
 * @license GNU GPL2 (or any later version)
 */

require_once '../../config.php';

// Include WB admin wrapper script
$admin = new Admin('admintools', 'admintools', false);
require_once dirname(__FILE__) . '/functions.inc.php';
$oMsgBox = new MessageBox();
$asyncRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$asyncReply = static function ($ok, $message, $redirect, $status = 200) use ($admin) {
    http_response_code((int) $status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(array('ok'=>(bool)$ok,'message'=>(string)$message,'redirect'=>(string)$redirect,'ftan'=>$admin->getFTAN()), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
};

$sBackToList = ADMIN_URL.'/admintools/tool.php?tool=droplets';
if (!$admin->checkFTAN()) {
    if ($asyncRequest) $asyncReply(false, $MESSAGE['GENERIC_SECURITY_ACCESS'], $sBackToList, 403);
    $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS'], $sBackToList);
}
// check permission
if ( $admin->get_permission('admintools') == true ){
    // Get id
    if ( isset($_POST['droplet_id']) && is_numeric($_POST['droplet_id']) && !empty($_POST['droplet_id'])) {
        $droplet_id = intval($_POST['droplet_id']);
    } else {
        header( "Location: " . ADMIN_URL . "/pages/index.php" );
    }
} else {   
    $admin->print_header();
    $admin->print_error($MESSAGE['GENERIC_SECURITY_ACCESS'], $sBackToList );   
    $admin->print_footer();
    exit();
}

$sBackURL = ADMIN_URL.'/admintools/tool.php?tool=droplets&do=modify&droplet_id='.$droplet_id;
// Validate all fields
$sName = $admin->add_slashes($admin->get_post('title'));
if($sName == '') {
	if ($asyncRequest) $asyncReply(false, $MESSAGE['GENERIC_FILL_IN_ALL'], $sBackURL, 422);
    $admin->print_header();
    $admin->print_error($MESSAGE['GENERIC_FILL_IN_ALL'], $sBackURL);
    $admin->print_footer();
} else {
    $tags = array('<?php', '?'.'>' , '<?');
    $aUpdate = array(
        'id'            => $droplet_id,
        'name'          => $sName,
        'active'        => (int) $admin->get_post('active'),
        'admin_view'    => (int) $admin->get_post('admin_view'),
        'admin_edit'    => (int) $admin->get_post('admin_edit'),
        'show_wysiwyg'  => (int) $admin->get_post('show_wysiwyg'),
        'description'   => $admin->add_slashes($admin->get_post('description')),
        'code'          => $admin->add_slashes(str_replace($tags, '', $_POST['savecontent'])),
        'comments'      => $admin->add_slashes($admin->get_post('comments')),
        'modified_when' => time(),
        'modified_by'   => (int) $admin->get_user_id(),
    );
    
    $database->updateRow('{TP}mod_droplets', 'id', $aUpdate);
    
    
    if($database->is_error()) {
		if ($asyncRequest) $asyncReply(false, $database->get_error(), $sBackURL, 500);
        $oMsgBox->error($database->get_error());
    } else {
		if ($asyncRequest) {
			$target = isset($_POST['save_back']) ? $sBackToList.'&hilite='.rawurlencode(stripslashes($sName)) : $sBackURL;
			$asyncReply(true, $MESSAGE['RECORD_MODIFIED_SAVED'], $target);
		}
        #$oMsgBox->success($MESSAGE['RECORD_MODIFIED_SAVED']);
    }

    $sGoto = isset($_POST['save_back']) ? $sBackToList.'&hilite='.$sName : $sBackURL;
    $oMsgBox->redirect($sGoto);
}
