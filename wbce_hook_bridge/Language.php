<?php
if (!defined('WB_PATH')) { return; }
function wbce_hook_bridge_texts($language=null)
{
    static $cache=array();
    $language=strtoupper(substr((string)($language?: (defined('LANGUAGE')?LANGUAGE:'EN')),0,2));
    if(isset($cache[$language]))return $cache[$language];
    $base=require __DIR__.'/languages/EN.php';
    $file=__DIR__.'/languages/'.preg_replace('/[^A-Z]/','',$language).'.php';
    return $cache[$language]=is_file($file)?array_replace($base,require $file):$base;
}
function wbce_hook_bridge_t($key,$language=null)
{
    $texts=wbce_hook_bridge_texts($language);
    return isset($texts[$key])?(string)$texts[$key]:(string)$key;
}
