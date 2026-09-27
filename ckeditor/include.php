<?php
/** CKEditor 5 integration for WBCE CMS. @license GNU GPL-2.0-or-later */
if (count(get_included_files()) === 1) {
    header('HTTP/1.0 404 Not Found');
    exit('HTTP/1.0 404 Not Found');
}

if (!defined('WB_FRONTEND') || WB_FRONTEND !== true) {
    /** Preserve WBCE's established WYSIWYG module API. */
    function show_wysiwyg_editor($name, $id, $content, $width = '100%', $height = '350px', $toolbar = false)
    {
        static $assetsLoaded = false;
        static $instanceNumber = 0;
        $instanceNumber++;

        $editorId = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $id);
        $editorId = ($editorId !== '' ? $editorId : 'wbce_editor') . '_' . $instanceNumber;
        $language = strtolower(defined('LANGUAGE') ? (string) LANGUAGE : 'en');
        $language = preg_replace('/[^a-z-]/', '', $language) ?: 'en';
        $languageFile = __DIR__ . '/languages/' . strtoupper(substr($language, 0, 2)) . '.php';
        if (!is_file($languageFile)) {
            $languageFile = __DIR__ . '/languages/EN.php';
        }
        $CKEDITOR5_TEXT = array();
        require $languageFile;

        $decodedContent = htmlspecialchars_decode((string) $content, ENT_QUOTES);
        $safeContent = htmlspecialchars($decodedContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeName = htmlspecialchars((string) $name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeId = htmlspecialchars($editorId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeWidth = htmlspecialchars((string) $width, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        if (!$assetsLoaded) {
            $assetsLoaded = true;
            echo '<link rel="stylesheet" href="' . WB_URL . '/modules/ckeditor/dist/wbce-ckeditor5.css">';
            echo '<link rel="stylesheet" href="' . WB_URL . '/modules/ckeditor/wbce.css">';
            echo '<script>window.WBCE_CKEDITOR5_QUEUE=window.WBCE_CKEDITOR5_QUEUE||[];</script>';
            echo '<script type="module" src="' . WB_URL . '/modules/ckeditor/dist/wbce-ckeditor5.js"></script>';
        }

        echo '<div class="wbce-ckeditor5-wrap" style="width:' . $safeWidth . '">';
        echo '<textarea name="' . $safeName . '" id="' . $safeId . '">' . $safeContent . '</textarea></div>';

        $config = array(
            'id' => $editorId,
            'height' => (string) $height,
            'language' => substr($language, 0, 2),
            'mediaUrl' => WB_URL . '/modules/elfinder/ef/elfinder_cke.php',
            'pagesUrl' => WB_URL . '/modules/ckeditor/ckeditor/plugins/wblink/pages.php',
            'dropletsUrl' => WB_URL . '/modules/ckeditor/ckeditor/plugins/wbdroplets/pages.php',
            'fontAwesomeUrl' => WB_URL . '/include/font-awesome/css/font-awesome.min.css',
            'labels' => array(
                'media' => $CKEDITOR5_TEXT['MEDIA'],
                'wblink' => $CKEDITOR5_TEXT['WBLINK'],
                'droplet' => $CKEDITOR5_TEXT['DROPLET'],
                'embed' => $CKEDITOR5_TEXT['EMBED'],
                'softHyphen' => $CKEDITOR5_TEXT['SOFT_HYPHEN'],
                'icon' => $CKEDITOR5_TEXT['ICON'],
                'save' => $CKEDITOR5_TEXT['SAVE'],
                'pageId' => $CKEDITOR5_TEXT['PAGE_ID'],
                'dropletName' => $CKEDITOR5_TEXT['DROPLET_NAME'],
                'embedCode' => $CKEDITOR5_TEXT['EMBED_CODE'],
                'iconClass' => $CKEDITOR5_TEXT['ICON_CLASS'],
                'aiGenerated' => isset($GLOBALS['TEXT']['AI_GENERATED']) ? $GLOBALS['TEXT']['AI_GENERATED'] : $CKEDITOR5_TEXT['AI_GENERATED'],
            ),
        );
        echo '<script>window.WBCE_CKEDITOR5_QUEUE.push(' . json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ');</script>';
    }
}
