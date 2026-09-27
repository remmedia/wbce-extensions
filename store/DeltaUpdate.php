<?php
/** Chooses a matching delta and always falls back to the verified full package. */
final class WbceStoreDeltaUpdate
{
    public static function install($http, $installer, array $source, array $package, $installed)
    {
        if ($installed !== '') {
            require_once __DIR__ . '/DeltaInstaller.php';
            $delta = WbceStoreDeltaInstaller::applicable($package, $installed);
            if ($delta && self::safeDelta($delta, $source['catalog_url'])) {
                try {
                    $temporary = $http->download($delta['download_url'], (int) ($delta['size'] ?? 0), $source['access_token']);
                    try { return $installer->installDelta($temporary, $package, $installed); }
                    finally { @unlink($temporary); }
                } catch (Throwable $ignored) {
                    // A delta is an optimisation. Any unavailable, invalid or
                    // locally inapplicable delta must never prevent an update.
                }
            }
        }
        $temporary = $http->download($package['download_url'], (int) $package['size'], $source['access_token']);
        try { return $installer->install($temporary, $package, false); }
        finally { @unlink($temporary); }
    }
    private static function safeDelta(array $delta, $catalogUrl)
    {
        return isset($delta['size'], $delta['download_url']) && (int) $delta['size'] > 0 && (int) $delta['size'] <= 52428800
            && filter_var($delta['download_url'], FILTER_VALIDATE_URL)
            && parse_url($delta['download_url'], PHP_URL_SCHEME) === 'https'
            && parse_url($delta['download_url'], PHP_URL_HOST) === parse_url($catalogUrl, PHP_URL_HOST);
    }
}
