<?php
require_once '../../config.php';
require_once WB_PATH.'/framework/Admin.php';

$admin = new admin('Admintools', 'admintools', false);
header('Location: '.ADMIN_URL.'/admintools/tool.php?tool=two_factor');
exit;
