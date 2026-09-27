<?php
if (!class_exists('WbceCompatibleTwigView', false)) {
    final class WbceCompatibleTwigView {
        public static function display($directory, $template, array $context, $fallbackHtml = '') {
            if (function_exists('getTwig')) {
                try { getTwig(rtrim($directory, '/').'/')->load($template)->display($context); return; }
                catch (Throwable $ignored) { }
            }
            echo $fallbackHtml;
        }
    }
}
