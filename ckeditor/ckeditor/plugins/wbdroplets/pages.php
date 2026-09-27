<?php

header('Content-type: application/javascript');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0, false');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');

/*
    This Plugin read files of a directory and outputs
    a javascript array. Output is:

    var DropletSelectBox = new Array(
        new Array( name, link ),
        new Array( name, link )...
    );

    DropletSelectBox will loaded as select options to wbdroplets plugin.

*/

// Include the config file
require('../../../../../config.php');

// Create new admin object
require_once(WB_PATH.'/framework/class.admin.php');
$admin = new admin('Pages', 'pages_modify', false);

$dropletSelectBox = array();
$description = array();
$usage = array();

$array = array();
$sql  = 'SELECT * FROM `'.TABLE_PREFIX.'mod_droplets` ';
$sql .= 'WHERE `active`=1 ';
$sql .= 'ORDER BY `name` ASC';
if ($resRec = $database->query($sql)) {
    if ($resRec->numRows() > 0) {
        while (!false == ($droplet = $resRec->fetchRow())) {
            $title = (string) $droplet['name'];
            $dropletSelectBox[] = array($title, (string) $droplet['name']);
            $description[] = array($title, (string) $droplet['description']);
            $usage[] = array($title, (string) $droplet['comments']);
        }
    }
}
echo 'var DropletSelectBox = '.json_encode($dropletSelectBox, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).";\n";
echo 'var DropletInfoBox = '.json_encode($description, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).";\n";
echo 'var DropletUsageBox = '.json_encode($usage, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).";\n";
