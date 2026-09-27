<?php
/**
 *
 * @category        admintool
 * @package         wbstats
 * @author          Ruud Eisinga - dev4me.com
 * @link			https://dev4me.com/
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WebsiteBaker 2.8.x / WBCE 1.4
 * @requirements    PHP 7 and higher
 * @version         0.2.5.8
 * @lastmodified    November 21, 2025
 *
 */

defined('WB_PATH') OR die(header('Location: ../index.php?foutje'));
$iplist = $stats->getIgnores();
$myip = $stats->_getRealUserIp();

$testlist = '|'.implode("|",$iplist).'|';
$usecopy = strpos($testlist,'|'.$myip.'|') !== false ? 'isthere':'copyip';
$trackingEnabled = true;
$trackingResult = $database->query("SELECT `value` FROM `".TABLE_PREFIX."mod_wbstats_cfg` WHERE `type`='system' AND `name`='enabled' ORDER BY `id` DESC LIMIT 1");
$trackingRow = $trackingResult && method_exists($trackingResult, 'fetchRow') ? $trackingResult->fetchRow() : null;
if ($trackingRow) $trackingEnabled = (string)$trackingRow['value'] === '1';
?>
<div class="third" id="ignore" style="padding: 15px">
	<h3><?=$WS['IGNORES']?>:</h3>
	<form method="post" action="<?php echo WB_URL; ?>/modules/wbstats/save_settings.php" data-wbstats-settings data-success="<?php echo htmlspecialchars($WS['SETTINGS_SAVED'], ENT_QUOTES, 'UTF-8'); ?>" data-error="<?php echo htmlspecialchars($WS['SETTINGS_FAILED'], ENT_QUOTES, 'UTF-8'); ?>">
		<input type="hidden" name="tool" value="wbstats">
		<?php echo $admin->getFTAN(); ?>
		<div class="wbstats-tracking-setting">
			<div><strong><?php echo htmlspecialchars($WS['TRACKING_ENABLED'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($WS['TRACKING_ENABLED_HINT'], ENT_QUOTES, 'UTF-8'); ?></small></div>
			<label class="wbstats-switch"><input type="checkbox" name="tracking_enabled" value="1"<?php echo $trackingEnabled ? ' checked' : ''; ?>><span aria-hidden="true"></span></label>
		</div>
		<p><textarea style="width:100%;height:200px;border:1px solid #aaa;padding:10px;background:#fff;" name="ips" id="ips"><?php echo implode("\n",$iplist) ?></textarea></p>
		<p><input type="submit" class="btn" value="<?php echo $TEXT['SAVE']; ?>" /></p>
	</form>
	<b><?=$WS['MYIP']?>: <a href="#" class="<?=$usecopy?>" data-ip="<?=$myip?>"><u><?=$myip?></u></a></b><br>
</div>
<!--
<div class="third" id="ignore" style="padding: 15px">
	<h3>Instellingen:</h3>
	<form method="post">
		<p>Bewaar history (7,30,60,90,365 dagen)</p>
		
		<p><input type="submit" class="btn" value="<?php echo $TEXT['SAVE']; ?>" /></p>

	</form>
</div>
-->
