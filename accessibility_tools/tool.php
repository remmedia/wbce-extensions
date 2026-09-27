<?php
defined('WB_PATH')&&isset($admin) or die('Access denied');
require_once __DIR__.'/src/Config.php';require_once __DIR__.'/Language.php';
$at=accessibility_tools_texts();
$config=WbceAccessibilityToolsConfig::get($database);$ftan=$admin->getFTAN();
function ate($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
$features=array('invert','grayscale','saturation','links','font_size','line_height','letter_spacing','text_align','contrast','hide_images','hide_video','cursor','position_controls');
?>
<div class="at-wrap" data-saved="<?php echo ate($at['saved']);?>" data-error="<?php echo ate($at['error']);?>">
 <section class="at-intro"><div><p><?php echo ate($at['intro']);?></p></div><label class="at-main-switch wbce-admin-switch"><span><?php echo ate($at['enabled']);?></span><input form="at-form" type="checkbox" name="enabled" value="1" <?php echo $config['enabled']?'checked':'';?>><i class="wbce-admin-switch__control" aria-hidden="true"></i></label></section>
 <form id="at-form" class="at-card content-box wbce-admin-card" method="post" action="<?php echo ate(WB_URL.'/modules/accessibility_tools/ajax.php');?>">
  <span class="at-ftan"><?php echo $ftan;?></span>
  <input type="hidden" name="accessibility_tools_action" value="save">
  <?php $positions=array('top_left','top','top_right','left','right','bottom_left','bottom','bottom_right');?>
  <div class="at-placement-row">
  <?php foreach(array('position_desktop','position_mobile') as $positionSetting){?>
  <fieldset class="at-placement <?php echo $positionSetting==='position_desktop'?'at-placement-desktop':'at-placement-mobile';?>"><legend><?php echo ate($at[$positionSetting]);?></legend><div class="at-placement-grid">
   <?php foreach($positions as $position){?><label class="at-place at-place-<?php echo $position;?>" title="<?php echo ate($at[$position]);?>"><input type="radio" name="<?php echo $positionSetting;?>" value="<?php echo $position;?>" <?php echo $config[$positionSetting]===$position?'checked':'';?>><span></span><b><?php echo ate($at[$position]);?></b></label><?php }?>
  </div></fieldset><?php }?>
  </div>
  <h3><?php echo ate($at['features']);?></h3><div class="at-grid">
  <?php foreach($features as $feature){?><label class="at-feature wbce-admin-card wbce-admin-switch"><span><?php echo ate($at[$feature]);?></span><input type="checkbox" name="<?php echo $feature;?>" value="1" <?php echo $config[$feature]?'checked':'';?>><i class="wbce-admin-switch__control" aria-hidden="true"></i></label><?php }?>
  </div><div class="at-live-status" aria-live="polite"></div>
 </form>
</div><div class="at-toast wbce-admin-toast" role="status" aria-live="polite"></div>
<link rel="stylesheet" href="<?php echo ate(WB_URL.'/modules/accessibility_tools/backend.css?v=1.3.20');?>">
<link rel="stylesheet" href="<?php echo ate(WB_URL.'/modules/accessibility_tools/backend-live.css?v=1.3.20');?>">
<script src="<?php echo ate(WB_URL.'/modules/accessibility_tools/backend.js?v=1.3.20');?>"></script>
