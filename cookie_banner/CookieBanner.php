<?php
defined('WB_PATH') or die('No direct access');

final class WbceCookieBanner
{
    public static function defaults(): array { return array('enabled'=>'0','provider'=>'','privacy_url'=>'','title'=>'Ihre Privatsphäre','text'=>'Wir verwenden Cookies und vergleichbare Technologien. Sie entscheiden, welche optionalen Kategorien verwendet werden dürfen.','accept'=>'Alle akzeptieren','reject'=>'Nur notwendige','save'=>'Auswahl speichern','external_scripts'=>'','google_analytics_id'=>'','google_tag_manager_id'=>'','matomo_url'=>'','matomo_site_id'=>'','google_analytics_enabled'=>'0','google_tag_manager_enabled'=>'0','matomo_enabled'=>'0','google_ads_id'=>'','meta_pixel_id'=>'','google_ads_enabled'=>'0','meta_pixel_enabled'=>'0','youtube_enabled'=>'0','vimeo_enabled'=>'0','google_maps_enabled'=>'0','openstreetmap_enabled'=>'0'); }
    public static function settings(): array { $raw=class_exists('Settings')?Settings::get('cookie_banner',''):''; $data=is_array($raw)?$raw:json_decode((string)$raw,true); return array_merge(self::defaults(),is_array($data)?$data:array()); }
    public static function directIntegrations(?array $settings=null): array {
        $s=$settings??self::settings();
        $spec=array(
            'google_analytics'=>array('name'=>'Google Analytics','category'=>'analytics','description'=>'Reichweitenmessung und Statistik.','ready'=>!empty($s['google_analytics_enabled'])&&!empty($s['google_analytics_id'])),
            'google_tag_manager'=>array('name'=>'Google Tag Manager','category'=>'analytics','description'=>'Tag-Verwaltung und Analyse.','ready'=>!empty($s['google_tag_manager_enabled'])&&!empty($s['google_tag_manager_id'])),
            'matomo'=>array('name'=>'Matomo','category'=>'analytics','description'=>'Reichweitenmessung und Statistik.','ready'=>!empty($s['matomo_enabled'])&&!empty($s['matomo_url'])&&!empty($s['matomo_site_id'])),
            'google_ads'=>array('name'=>'Google Ads Conversion Tracking','category'=>'marketing','description'=>'Messung von Werbekampagnen und Conversions.','ready'=>!empty($s['google_ads_enabled'])&&!empty($s['google_ads_id'])),
            'meta_pixel'=>array('name'=>'Meta Pixel','category'=>'marketing','description'=>'Messung von Meta-Werbekampagnen und Zielgruppen.','ready'=>!empty($s['meta_pixel_enabled'])&&!empty($s['meta_pixel_id'])),
            'youtube'=>array('name'=>'YouTube','category'=>'media','description'=>'Eingebettete Videos.','ready'=>!empty($s['youtube_enabled'])),
            'vimeo'=>array('name'=>'Vimeo','category'=>'media','description'=>'Eingebettete Videos.','ready'=>!empty($s['vimeo_enabled'])),
            'google_maps'=>array('name'=>'Google Maps','category'=>'maps','description'=>'Eingebettete Karten.','ready'=>!empty($s['google_maps_enabled'])),
            'openstreetmap'=>array('name'=>'OpenStreetMap','category'=>'maps','description'=>'Eingebettete Karten.','ready'=>!empty($s['openstreetmap_enabled'])),
        );
        return $spec;
    }
    public static function categories(): array {
        $items=array('necessary'=>array('id'=>'necessary','name'=>'Notwendig','description'=>'Für grundlegende Funktionen der Website erforderlich.','required'=>true));
        if(function_exists('wbce_apply_array_filters'))$items=wbce_apply_array_filters('cookie_banner.categories',$items);
        foreach($items as $id=>&$item){if(!is_array($item)||!preg_match('/^[a-z0-9_-]{2,64}$/i',(string)$id)){unset($items[$id]);continue;}$item=array_merge(array('id'=>$id,'name'=>$id,'description'=>'','required'=>false,'scripts'=>array()),$item);$item['id']=$id;$item['required']=(bool)$item['required'];$item['scripts']=is_array($item['scripts'])?$item['scripts']:array();}unset($item);
        $scriptCategories=array('analytics'=>array('name'=>'Analyse','description'=>'Reichweitenmessung und Statistik.'),'marketing'=>array('name'=>'Marketing','description'=>'Personalisierte Werbung und Kampagnen.'));
        foreach(preg_split('/\R/',(string)(self::settings()['external_scripts']??'')) as $line){$parts=array_map('trim',explode('|',$line,2));if(count($parts)!==2||!filter_var($parts[1],FILTER_VALIDATE_URL)||stripos($parts[1],'https://')!==0)continue;$id=strtolower($parts[0]);$id=array('analyse'=>'analytics','statistik'=>'analytics','statistics'=>'analytics','werbung'=>'marketing','marketing'=>'marketing','analytics'=>'analytics')[$id]??$id;if(!preg_match('/^[a-z0-9_-]{2,64}$/i',$id))continue;if(!isset($items[$id])){$meta=$scriptCategories[$id]??array('name'=>ucfirst($id),'description'=>'Externe Dienste dieser Kategorie.');$items[$id]=array('id'=>$id,'name'=>$meta['name'],'description'=>$meta['description'],'required'=>false,'scripts'=>array());}$items[$id]['scripts'][]=$parts[1];}
        foreach(self::directIntegrations() as $integration){if(empty($integration['ready']))continue;$id=$integration['category'];if(!isset($items[$id]))$items[$id]=array('id'=>$id,'name'=>array('analytics'=>'Analyse','marketing'=>'Marketing','media'=>'Medien','maps'=>'Karten')[$id]??ucfirst($id),'description'=>$integration['description'],'required'=>false,'scripts'=>array());$items[$id]['direct_integration']=true;}
        foreach($items as &$item)$item['scripts']=array_values(array_unique($item['scripts']));unset($item);return $items;
    }
    public static function settingsSections(): array {
        $sections=array();
        return function_exists('wbce_apply_array_filters') ? wbce_apply_array_filters('cookie_banner.settings.sections',$sections) : $sections;
    }
    public static function providers(): array {
        $items=array();
        return function_exists('wbce_apply_array_filters') ? wbce_apply_array_filters('cookie_banner.providers',$items) : $items;
    }
    public static function render(): string {
        $settings=self::settings(); if(($settings['enabled']??'0')!=='1')return ''; $categories=self::categories(); $providers=self::providers();$provider=$providers[(string)($settings['provider']??'')]??null;if(!is_array($provider))return '';$assets=isset($provider['render'])&&is_callable($provider['render'])?(string)call_user_func($provider['render'],$categories,$settings):'';
        $h=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8'); $json=json_encode(array('categories'=>$categories,'text'=>array_intersect_key($settings,array_flip(array('title','text','accept','reject','save','privacy_url')))),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
        $trackingData=array_intersect_key($settings,array_flip(array('google_analytics_id','google_tag_manager_id','matomo_url','matomo_site_id','google_analytics_enabled','google_tag_manager_enabled','matomo_enabled','google_ads_id','meta_pixel_id','google_ads_enabled','meta_pixel_enabled','youtube_enabled','vimeo_enabled','google_maps_enabled','openstreetmap_enabled')));$trackingData['category_scripts']=array();foreach($categories as $id=>$category)if(!$category['required'])$trackingData['category_scripts'][$id]=array_values((array)$category['scripts']);
        $tracking=json_encode($trackingData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
        $bridge='<script>window.WbceCookieTracking='.($tracking?:'{}').';</script><script src="'.WB_URL.'/modules/cookie_banner/assets/integrations.js?v=1.0.25" defer></script>';
        if ($assets !== '') return $bridge.$assets;
        return $bridge.'<link rel="stylesheet" href="'.WB_URL.'/modules/cookie_banner/assets/banner.css?v=1.0.0"><div id="wbce-cookie-banner" class="wbce-cookie-banner" hidden aria-live="polite"></div><script>window.WbceCookieBanner='.($json?:'{}').';</script><script src="'.WB_URL.'/modules/cookie_banner/assets/banner.js?v=1.0.0" defer></script>';
    }
    public static function protectEmbeds(string $html): string {
        $settings=self::settings();$hosts=array();if(!empty($settings['youtube_enabled']))$hosts['media'][]='(?:www\.)?(?:youtube(?:-nocookie)?\.com|youtu\.be)';if(!empty($settings['vimeo_enabled']))$hosts['media'][]='(?:player\.)?vimeo\.com';if(!empty($settings['google_maps_enabled']))$hosts['maps'][]='(?:www\.)?google\.[^/]+/maps|maps\.google\.[^/]+';if(!empty($settings['openstreetmap_enabled']))$hosts['maps'][]='(?:www\.)?openstreetmap\.org';foreach($hosts as $category=>$patterns){$pattern='~(<iframe\b[^>]*?)\s+src=([\"\'])(https?://(?:'.implode('|',$patterns).')[^\"\']*)\2([^>]*>)~i';$html=preg_replace_callback($pattern,static fn($m)=>$m[1].' data-wbce-cookie-category="'.$category.'" data-wbce-cookie-src='.$m[2].$m[3].$m[2].$m[4],$html)??$html;}return $html;
    }
    public static function inject(string $html): string { if(stripos($html,'</body>')===false)return $html; $html=self::protectEmbeds($html);return preg_replace('~</body>~i',self::render().'</body>',$html,1)?:$html; }
}
