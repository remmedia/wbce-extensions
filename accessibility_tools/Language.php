<?php
defined('WB_PATH') or die('No direct access');
function accessibility_tools_texts(){
    static $cache=array();$lang=defined('LANGUAGE')?strtoupper(substr(preg_replace('/[^A-Za-z]/','',(string)LANGUAGE),0,2)):'EN';if(isset($cache[$lang]))return $cache[$lang];$texts=require __DIR__.'/languages/EN.php';$file=__DIR__.'/languages/'.$lang.'.php';if($lang!=='EN'&&is_file($file))$texts=array_merge($texts,require $file);return $cache[$lang]=$texts;
}
