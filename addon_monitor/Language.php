<?php
defined('WB_PATH') or die('Access denied');
function addon_monitor_texts(){static $texts;if(is_array($texts))return $texts;$language=defined('LANGUAGE')?strtoupper(preg_replace('/[^A-Z]/','',(string)LANGUAGE)):'EN';$base=(array)require __DIR__.'/languages/EN.php';$file=__DIR__.'/languages/'.$language.'.php';return $texts=is_file($file)?array_replace($base,(array)require $file):$base;}
function addon_monitor_t($key){$texts=addon_monitor_texts();return isset($texts[$key])?$texts[$key]:$key;}
