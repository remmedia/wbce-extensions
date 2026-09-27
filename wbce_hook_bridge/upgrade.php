<?php
if (!defined('WB_PATH')) { return; }
if (isset($database) && is_object($database) && is_callable(array($database,'query'))) { $database->query("UPDATE `{TP}addons` SET `function`='preinit,initialize,snippet' WHERE `directory`='wbce_hook_bridge' AND `type`='module'"); }
foreach(array('factor.php','factor.css','TwigBridge.php','src/AuthFactorManager.php','src/AuthFactorProviderInterface.php','src/CaptchaManager.php') as $obsolete){$path=__DIR__.'/'.$obsolete;if(is_file($path))@unlink($path);}
