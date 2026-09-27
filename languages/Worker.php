<?php
defined('WB_PATH') or die('No direct access');
require_once __DIR__ . '/LanguageRepository.php';

final class WbceLanguagesWorker
{
    public static function restore(array $configuration = array(), array $task = array()): array
    {
        $restored = (new WbceLanguageRepository())->restoreOverrides();
        return array('message' => $restored > 0 ? $restored . ' Übersetzung(en) wiederhergestellt.' : 'Sprachdateien geprüft; keine Wiederherstellung erforderlich.');
    }
}
