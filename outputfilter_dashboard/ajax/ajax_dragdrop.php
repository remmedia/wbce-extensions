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
/*      Drag'N'Drop Position
 *      This file is based on the mechanism used in Module "mpform"
**/

$admin_header = FALSE;
require __DIR__ . '/bootstrap.php';

// include module.functions.php
include_once(WB_PATH . '/framework/module.functions.php');

// load outputfilter-functions
require_once(dirname(dirname(__FILE__))."/functions.php");


$aJsonRespond = array();
$aJsonRespond['success'] = false;
$aJsonRespond['message'] = '';
$aJsonRespond['icon'] = '';

if (!isset($_POST['action'], $_POST['id']) || !is_scalar($_POST['action']) || !is_array($_POST['id']))
{
    opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_INVALID'), 400);
}
 else
{
    $aRows = array_values($_POST['id']);
    // Sanitize variables
    $action = (string) $_POST['action'];
    if ($action === 'updatePosition' && count($aRows) > 0 && count($aRows) <= 1000)
    {
		if (!opf_db_run_query('START TRANSACTION')) {
			opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_SAVE_FAILED'), 500);
		}
        $i = array();
        $i_keys = array();
        if (count(array_unique(array_map('strval', $aRows))) !== count($aRows)) {
			opf_db_run_query('ROLLBACK');
            opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_INVALID'), 400);
        }
        foreach(opf_get_types() as $type => $typename){
            $i[$type]=1;
            $i_keys[]=$type;
        }

        foreach ($aRows as $recID) {
            if (!is_scalar($recID)) {
				opf_db_run_query('ROLLBACK');
                opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_INVALID'), 400);
            }
            $id = $admin->checkIDKEY((string) $recID, 0, 'key', true);
            if ((int) $id <= 0) {
				opf_db_run_query('ROLLBACK');
                opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_INVALID'), 400);
            }
            $filter = opf_get_data($id);
            if (!is_array($filter) || !isset($filter['type'], $i[$filter['type']])) {
				opf_db_run_query('ROLLBACK');
                opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_INVALID'), 400);
            }
            $type = $filter['type'];
            // now we sanitize array
            $qstring ="UPDATE `".TABLE_PREFIX."mod_outputfilter_dashboard`"
               . " SET `position` = '".$i[$type]."'"
               . " WHERE `id` = ".intval($id)." ";
            if(!opf_db_run_query($qstring)) {
                $aJsonRespond['success'] = false;
				opf_db_run_query('ROLLBACK');
                opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_SAVE_FAILED'), 500);
            }
            $i[$type]++;
        }
		if (!opf_db_run_query('COMMIT')) {
			opf_db_run_query('ROLLBACK');
			opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_SAVE_FAILED'), 500);
		}
    }else{
        opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_INVALID'), 400);
    }

    $aJsonRespond['icon'] = 'dialog-close.gif';
    opf_ajax_reply(true, opf_ajax_text('TXT_AJAX_ORDER_SAVED'));
}
