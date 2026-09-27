<?php
defined('WB_PATH') or die('No direct access');

if (function_exists('wbce_add_filter')) {
    wbce_add_filter('frontend.page.output', static function ($html) {
        if (stripos((string) $html, 'data-wbce-ai-generated="true"') === false) {
            return $html;
        }
        $stylesheet = '<link rel="stylesheet" href="' . WB_URL . '/modules/ckeditor/frontend.css">';
        if (stripos($html, '/modules/ckeditor/frontend.css') === false) {
            $html = preg_replace('/<\/head>/i', $stylesheet . '</head>', $html, 1);
        }
        // Inline images cannot render a pseudo element themselves, so wrap only
        // marked images that are not already represented by a marked figure.
        $html = preg_replace_callback(
            '/<img\b(?=[^>]*data-wbce-ai-generated="true")[^>]*>/i',
            static function ($match) {
                $label = '';
                if (preg_match('/data-wbce-ai-label="([^"]*)"/i', $match[0], $labelMatch)) {
                    $label = $labelMatch[1];
                }
                return '<span class="wbce-ai-image wbce-ai-image-inline" data-wbce-ai-label="' . $label . '">' . $match[0] . '</span>';
            },
            $html
        );
        return $html;
    }, 20);
}
