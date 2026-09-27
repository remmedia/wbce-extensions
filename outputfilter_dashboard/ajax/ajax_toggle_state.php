<?php

/**
 *
 * @category        tool
 * @package         Outputfilter Dashboard
 * @version         1.6.3
 * @authors         Thomas "thorn" Hornik <thorn@nettest.thekk.de>, Christian M. Stefan (Stefek) <stefek@designthings.de>, Martin Hecht (mrbaseman) <mrbaseman@gmx.de>
 * @copyright       (c) 2009,2010 Thomas "thorn" Hornik, 2010-2023 Christian M. Stefan (Stefek), 2016-2023 Martin Hecht (mrbaseman)
 * @link            https://github.com/mrbaseman/outputfilter_dashboard
 * @link            https://addons.wbce.org/pages/addons.php?do=item&item=53
 * @link            https://forum.wbce.org/viewtopic.php?id=176
 * @license         GNU General Public License, Version 3
 * @platform        WBCE 1.x
 * @requirements    PHP 7.4 - 8.2
 *
 * This file is part of OutputFilter-Dashboard, a module for WBCE and Website Baker CMS.
 *
 * OutputFilter-Dashboard is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OutputFilter-Dashboard is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OutputFilter-Dashboard. If not, see <http://www.gnu.org/licenses/>.
 *
 **/
require __DIR__ . '/bootstrap.php';

if (!isset($_POST['idkey'], $_POST['purpose'], $_POST['action'])
    || !is_scalar($_POST['idkey']) || !is_scalar($_POST['purpose']) || !is_scalar($_POST['action'])) {
    opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_INVALID'), 400);
}
$iFilterID = (string) $_POST['idkey'];

// Sanitize variables
$purpose = (string) $_POST['purpose'];
if ($purpose === 'toggle_status' && in_array((string) $_POST['action'], array('0', '1'), true)) {
    require WB_PATH.'/modules/outputfilter_dashboard/functions_outputfilter.php';
    require_once WB_PATH.'/modules/outputfilter_dashboard/functions.php';

    $iId = $admin->checkIDKEY($iFilterID, 0, 'POST', true);
    if($iId == 0){
        opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_INVALID'), 400);
    }
    $iActive = (int) $_POST['action'];
    $filter = opf_get_data($iId);
    $alwaysActive = include WB_PATH . '/modules/outputfilter_dashboard/allways_active_array.php';
    if (!is_array($filter) || in_array((string) ($filter['funcname'] ?? ''), $alwaysActive, true)) {
        opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_PROTECTED'), 403);
    }

    if(!opf_set_active($iId, $iActive)) {
        opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_SAVE_FAILED'), 500);
    } elseif ((bool) opf_is_active((string) $filter['name']) !== (bool) $iActive) {
        opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_SAVE_FAILED'), 500);
    } else {
        opf_ajax_reply(true, opf_ajax_text('TXT_AJAX_STATUS_SAVED'));
    }


}else{
    opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_INVALID'), 400);
}
