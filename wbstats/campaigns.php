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

 
defined('WB_PATH') OR die(header('Location: ../index.php'));
$c = $stats->getCampaigns();


?>
<?php 
foreach($c as $campaign => $data) {
	foreach ($data as $content => $val) {
	  if(stripos($content,"Not identified") === false) {
		print ( '<div class="full">');
		print ( '<h3>'.htmlspecialchars((string) $campaign, ENT_QUOTES, 'UTF-8').' &rarr; '.htmlspecialchars((string) $content, ENT_QUOTES, 'UTF-8').'</h3>');
		
		print ( '<table class="res" width="100%" border="0" cellpadding="5" cellspacing="0">' );
		print ( '<tr>');	
		print ( '<th style="width:60px;">'.htmlspecialchars($WS['CAMPAIGN_SOURCE'], ENT_QUOTES, 'UTF-8').'</th>');
		print ( '<th style="width:190px;">'.htmlspecialchars($WS['CAMPAIGN_MEDIUM'], ENT_QUOTES, 'UTF-8').'</th>');
		print ( '<th>'.htmlspecialchars($WS['CAMPAIGN_CONTENT'], ENT_QUOTES, 'UTF-8').'</th>');
		print ( '<th style="text-align:center;width:110px">'.htmlspecialchars($WS['CAMPAIGN_FIRST_DATE'], ENT_QUOTES, 'UTF-8').'</th>');
		print ( '<th style="text-align:center;width:110px">'.htmlspecialchars($WS['CAMPAIGN_LAST_DATE'], ENT_QUOTES, 'UTF-8').'</th>');
		print ( '<th style="text-align:center;width:50px">'.htmlspecialchars($WS['VISITORS'], ENT_QUOTES, 'UTF-8').'</th>');
		print ( '<th style="text-align:center;width:50px">'.htmlspecialchars($WS['CAMPAIGN_BOUNCES'], ENT_QUOTES, 'UTF-8').'</th>');
		print ( '<th style="text-align:center;width:50px">'.htmlspecialchars($WS['PAGES'], ENT_QUOTES, 'UTF-8').'</th>');
		print ( '<th style="text-align:center;width:50px">'.htmlspecialchars($WS['CAMPAIGN_AVERAGE'], ENT_QUOTES, 'UTF-8').'</th>');
		print ( '<tr>');
		foreach ($val as $medium => $detail) {
			print ( '<tr>');	
			print ( '<td>'.htmlspecialchars((string) $detail['source'], ENT_QUOTES, 'UTF-8').'</td>');
			print ( '<td>'.htmlspecialchars((string) $medium, ENT_QUOTES, 'UTF-8').'</td>');
			print ( '<td style="white-space:nowrap;">'.htmlspecialchars((string) $content, ENT_QUOTES, 'UTF-8').'</td>');
			print ( '<td style="text-align:center;">'.fdate($detail['first']).'</td>');	
			print ( '<td style="text-align:center;">'.fdate($detail['last']).'</td>');	
			print ( '<td style="text-align:center;">'.$detail['totalcount'].'</td>');	
			print ( '<td style="text-align:center;">'.$detail['bounces'].' <small>('.$detail['bounce_perc'].'%)</small></td>');	
			print ( '<td style="text-align:center;">'.$detail['pages'].'</td>');	
			print ( '<td style="text-align:center;">'.$detail['pages_visit'].'</td>');	
			print ( '<tr>');	
		}
		print ( '</table>' );
		print ( '</div>' );
	  }
	}
}

function fdate($d) {
	$d = substr($d,0,4).'-'.substr($d,4,2).'-'.substr($d,6,2);
	
	return $d;
}
