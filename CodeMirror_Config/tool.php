<?php
/**
 *
 * @category        admintool / initialize 
 * @package         CodeMirror_Config
 * @author          Christian M. Stefan
 * @license         http://www.gnu.org/licenses/gpl.html
 * @platform        WBCE 1.5.4

 *
 */

// Must include code to stop this file from being accessed directly
defined('WB_PATH') or die("This file can't be accessed directly!");

// check if user is allowed to use admin-tools (to prevent this file to be called by an unauthorized user e.g. from a code-section)
$admin->get_permission('admintools') or die(header('Location: ../../index.php'));

define('CMC_TOOL_RUNNING', true);

$cmcLanguage = defined('LANGUAGE') ? strtoupper((string) LANGUAGE) : 'EN';
require __DIR__ . '/languages/EN.php';
$cmcFallback = $CMC_LANG;
if ($cmcLanguage !== 'EN' && preg_match('/^[A-Z]{2}$/', $cmcLanguage) && is_readable(__DIR__ . '/languages/' . $cmcLanguage . '.php')) {
    require __DIR__ . '/languages/' . $cmcLanguage . '.php';
}
$CMC_LANG = array_merge($cmcFallback, is_array($CMC_LANG) ? $CMC_LANG : array());

$sSelected = 'wbce-day';
$aToolUri = ADMIN_URL.'/admintools/tool.php?tool='. basename(__DIR__);
$ModUrl = WB_URL . '/modules/' . basename(__DIR__);
$sCodeMirrorPath = __DIR__ . '/codemirror';
$sThemeLoc = $sCodeMirrorPath.'/theme/';

// Get the Config from DB
$aCfg = @unserialize((string) Settings::Get("cmc_cfg", ""), array('allowed_classes' => false));
if (!is_array($aCfg)) $aCfg = array('theme' => 'wbce-day', 'font' => 'Proggy', 'font_size' => 14);
$aCfg=array_merge(array('theme'=>'wbce-day','font'=>'Proggy','font_size'=>14),array_intersect_key($aCfg,array('theme'=>true,'font'=>true,'font_size'=>true)));
registerCodeMirror('code', 'x-php');

// form data fill
$aThemeFiles = list_files_from_dir($sThemeLoc, 'css');if(!is_array($aThemeFiles))$aThemeFiles=array();
$aFontFiles = list_files_from_dir($sCodeMirrorPath.'/fonts', ['woff2', 'woff']);if(!is_array($aFontFiles))$aFontFiles=array();
$aFontSizes = [12, 13, 14, 15, 16, 17, 18];
$themeNames = array_map(function ($file) { return pathinfo($file, PATHINFO_FILENAME); }, $aThemeFiles);
$fontNames = array_map(function ($file) { return pathinfo($file, PATHINFO_FILENAME); }, $aFontFiles);
if (!in_array((string) $aCfg['theme'], $themeNames, true)) $aCfg['theme'] = 'wbce-day';
if (!in_array((string) $aCfg['font'], $fontNames, true)) $aCfg['font'] = in_array('Proggy', $fontNames, true) ? 'Proggy' : (isset($fontNames[0]) ? $fontNames[0] : 'monospace');
$aCfg['font_size'] = (int) $aCfg['font_size'];
if (!in_array($aCfg['font_size'], $aFontSizes, true)) $aCfg['font_size'] = 14;
$cmcTwigOptions = array('autoescape'=>'html','cache'=>false,'strict_variables'=>false,'debug'=>false);
if (function_exists('getTwig')) {
    $oTwig = getTwig(__DIR__ . '/twig/');
} elseif (class_exists('Twig\\Loader\\FilesystemLoader') && class_exists('Twig\\Environment')) {
    $oTwig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader(__DIR__.'/twig'), $cmcTwigOptions);
} elseif (class_exists('Twig_Loader_Filesystem') && class_exists('Twig_Environment')) {
    $oTwig = new \Twig_Environment(new \Twig_Loader_Filesystem(__DIR__.'/twig'), $cmcTwigOptions);
} else {
    throw new RuntimeException($CMC_LANG['twig_unavailable']);
}
$aToTwig = [];
$aToTwig['cfg']             = $aCfg;
$aToTwig['cmc_code_sample'] = cmc_code_sample();
$aToTwig['aThemeFiles']     = $aThemeFiles;
$aToTwig['aFontFiles']      = $aFontFiles;
$aToTwig['aFontSizes']      = $aFontSizes;
$cmcFtan = explode('=', $admin->getFTAN(false), 2);
$aToTwig['FTAN_NAME']       = $cmcFtan[0] ?? 'formtoken';
$aToTwig['FTAN_VALUE']      = $cmcFtan[1] ?? '';
$aToTwig['saveUrl']         = $ModUrl . '/save.php';
$aToTwig['CMC']             = $CMC_LANG;
$oTemplate = $oTwig->load('tool.twig');
$oTemplate->display($aToTwig);

$aJsFiles = [
    $CodeMirror_dir . "lib/codemirror.js",
    $CodeMirror_dir . "mode/javascript/javascript.js",
    $CodeMirror_dir . 'mode/php/php.js',
    $CodeMirror_dir . 'mode/clike/clike.js',
    $CodeMirror_dir . "addon/selection/active-line.js",
    $CodeMirror_dir . "addon/edit/matchbrackets.js",
    $CodeMirror_dir . 'addon/fold/foldcode.js',
    $CodeMirror_dir . 'addon/fold/foldgutter.js',
    $CodeMirror_dir . 'addon/fold/brace-fold.js',
    $CodeMirror_dir . 'addon/fold/comment-fold.js',
    $CodeMirror_dir . 'addon/display/fullscreen.js'
];
I::insertJsFile($aJsFiles, 'BODY TOP+');
I::insertJsFile($ModUrl . '/backend.js', 'BODY BTM-');

// CodeMirror CSS
$aCssFiles = [
    $CodeMirror_dir . 'lib/codemirror.css',          
    $CodeMirror_dir . 'addon/fold/foldgutter.css',          
    $CodeMirror_dir . 'theme/wbce-day.css',          
    $CodeMirror_dir . 'addon/display/fullscreen.css',    
    
    // Load the visual contract from the active admin theme.
    get_url_from_path($admin->correct_theme_source('../css/ACPI_backend.css')),
    get_url_from_path($admin->correct_theme_source('../css/ACPI_content.css')),
    get_url_from_path($admin->correct_theme_source('../css/ACPI_buttons.css'))
];
I::insertCssFile($aCssFiles, 'HEAD TOP+');
I::insertCssFile($ModUrl . '/theme_contract.css', 'HEAD BTM-');
// Load all the CodeMirror Theme CSS Files
foreach($aThemeFiles as $sCssFile)
    I::insertCssFile(get_url_from_path($sThemeLoc).$sCssFile, 'HEAD BTM');

function cmc_code_sample(){
    ob_start();
    ?>
/** 
 * This is a sample of how your code syntax will be highlighted
 */
global $wb, $page_id, $TEXT, $MENU, $HEADING;

$sRetVal = '<div class="login-box">'.PHP_EOL;
// Return a system permission
function get_permission($name, $type = 'system') {
    global $wb;
    // Append to permission type
    $type .= '_permissions';
    // Check if we have a section to check for
    if ($name == 'start') {
        return true;
    } else {
        // Set template permissions var
        $template_permissions = $wb->get_session('TEMPLATE_PERMISSIONS');
        // Return true if system perm = 1
        if (isset($$type) && is_array($$type) && is_numeric(array_search($name, $$type))) {
            if ($type == 'system_permissions') {
                return true;
            }
        }
    }
}
<?php
    return ob_get_clean();
}
