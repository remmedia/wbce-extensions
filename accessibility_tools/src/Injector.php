<?php
defined('WB_PATH') or die('No direct access');

final class WbceAccessibilityToolsInjector
{
    private static $registered=false;

    public static function register()
    {
        if(self::$registered||PHP_SAPI==='cli')return;
        self::$registered=true;

        if(!function_exists('wbce_add_filter')){
            $bridge=WB_PATH.'/modules/wbce_hook_bridge/preinit.php';
            if(is_file($bridge))require_once $bridge;
        }

        if(function_exists('wbce_add_filter')){
            wbce_add_filter('frontend.page.output',array(__CLASS__,'inject'),90);
            if(defined('WBCE_HOOK_BRIDGE_EMULATES_HOOKS')&&WBCE_HOOK_BRIDGE_EMULATES_HOOKS&&class_exists('WbceHookBridge')&&method_exists('WbceHookBridge','enableFrontendOutputHooks')){
                WbceHookBridge::enableFrontendOutputHooks();
                return;
            }
            return;
        }

        // Letzter, klar abgegrenzter Fallback für WBCE 1.6.8 ohne Hook-Bridge.
        // Er wird unter WBCE 1.7 und bei vorhandener Bridge nicht gestartet und
        // kann später gemeinsam mit legacy/ entfernt werden.
        require_once dirname(__DIR__).'/legacy/OutputBuffer.php';
        WbceAccessibilityToolsLegacyOutputBuffer::start();
    }

    public static function inject($html)
    {
        $html=(string)$html;
        if($html===''||stripos($html,'</body>')===false||strpos($html,'data-wbce-accessibility-tools')!==false)return $html;
        $tags=self::tags();if($tags==='')return $html;
        $launcher=self::launcher();
        $position=strripos($html,'</body>');
        return substr($html,0,$position).$tags.$launcher.substr($html,$position);
    }

    public static function tags()
    {
        require_once __DIR__.'/Config.php';require_once dirname(__DIR__).'/Language.php';$config=WbceAccessibilityToolsConfig::get();if(empty($config['enabled']))return '';$texts=accessibility_tools_texts();$config['translations']=$texts['frontend_translations'];$config['open_close']=$texts['open_close'];$config['reset_all']=$texts['reset_all'];
        $base=rtrim(WB_URL,'/').'/modules/accessibility_tools/assets/';$json=json_encode($config,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);if(!is_string($json))return '';
        return '<span data-wbce-accessibility-tools data-config="'.htmlspecialchars($json,ENT_QUOTES,'UTF-8').'" hidden></span>'
            .'<link rel="stylesheet" href="'.htmlspecialchars($base.'runtime.css?v=1.3.20',ENT_QUOTES,'UTF-8').'">'
            .'<script defer src="'.htmlspecialchars($base.'runtime.js?v=1.3.20',ENT_QUOTES,'UTF-8').'"></script>'
            .'<script defer src="'.htmlspecialchars($base.'accessibility-menu.min.js?v=1.3.20',ENT_QUOTES,'UTF-8').'"></script>'
            .'<script defer src="'.htmlspecialchars($base.'i18n.js?v=1.3.20',ENT_QUOTES,'UTF-8').'"></script>';
    }

    private static function launcher()
    {
        require_once __DIR__.'/Config.php';require_once dirname(__DIR__).'/Language.php';$config=WbceAccessibilityToolsConfig::get();if(empty($config['enabled']))return '';$texts=accessibility_tools_texts();
        $desktop=htmlspecialchars((string)$config['position_desktop'],ENT_QUOTES,'UTF-8');$mobile=htmlspecialchars((string)$config['position_mobile'],ENT_QUOTES,'UTF-8');
        return '<button id="wbce-at-quick" type="button" data-desktop="'.$desktop.'" data-mobile="'.$mobile.'" data-current="'.$desktop.'" aria-label="'.htmlspecialchars($texts['open_tools'],ENT_QUOTES,'UTF-8').'"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M8 4.143A1.071 1.071 0 1 0 8 2a1.071 1.071 0 0 0 0 2.143m-4.668 1.47 3.24.316v2.5l-.323 4.585A.383.383 0 0 0 7 13.14l.826-4.017c.045-.18.301-.18.346 0L9 13.139a.383.383 0 0 0 .752-.125L9.43 8.43v-2.5l3.239-.316a.38.38 0 0 0-.047-.756Z"/><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0M1 8a7 7 0 1 1 14 0A7 7 0 0 1 1 8"/></svg></button>';
    }
}
