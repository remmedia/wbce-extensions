<?php
if(defined('WB_PATH')&&is_file(WB_PATH.'/modules/mailer/src/MailerRegistry.php')){require_once WB_PATH.'/modules/mailer/src/MailerRegistry.php';if(!WbceMailerRegistry::canUninstall('amazon_ses'))die('Der aktive oder letzte Mail-Provider kann nicht deinstalliert werden.');}
