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
if(!isset($_POST['purpose']) || !is_scalar($_POST['purpose']) || (string) $_POST['purpose'] !== 'delete_row') opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_INVALID'), 400);

if (!isset($_POST['idkey']) || !is_scalar($_POST['idkey'])) {
    opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_INVALID'), 400);
}

require WB_PATH.'/modules/outputfilter_dashboard/functions_outputfilter.php';
require_once WB_PATH.'/modules/outputfilter_dashboard/functions.php';

// Sanitize variables

$iFilterIDKEY = (string) $_POST['idkey'];
$iId = $admin->checkIDKEY($iFilterIDKEY, 0, 'POST', true);
if($iId == 0){
    opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_INVALID'), 400);
}
$filter = opf_get_data($iId);
if (!is_array($filter) || ((int) ($filter['userfunc'] ?? 0) !== 1 && trim((string) ($filter['plugin'] ?? '')) === '')) {
    opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_PROTECTED'), 403);
}

if(!opf_unregister_filter($iId)) {
    // we use opf_unregister_filter because it removes the filter
    //   and all its files too, when necessary
    opf_ajax_reply(false, opf_ajax_text('TXT_AJAX_DELETE_FAILED'), 500);

} else {
    // query worked
    opf_ajax_reply(true, opf_ajax_text('TXT_AJAX_DELETED'));
}
