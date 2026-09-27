<?php
/**
 * Updater - ZIP Repack Helper
 *
 * Intelligente ZIP-Umpackfunktion für GitHub Releases
 *
 * @category    module
 * @package     updater
 * @version     1.0.2
 * @author      WBCE Community
 * @copyright   2026 WBCE Community
 * @license     MIT License
 */

/**
 * Findet automatisch den WBCE-Unterordner im GitHub ZIP
 *
 * @param ZipArchive $zip Geöffnetes ZIP-Archiv
 * @return string|false Pfad zum WBCE-Ordner oder false
 */
function findWbceFolder($zip) {
    $foundPaths = [];

    // Alle Einträge durchsuchen
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $path = $stat['name'];

        // Suche nach typischen WBCE-Dateien im Root.
        // Diese Pfade existieren garantiert im wbce/-Wurzelverzeichnis und
        // sind spezifisch genug, um nicht versehentlich in Modulen zu treffen.
        // Hinweis: 'config.php' NICHT verwenden – existiert nicht im Release-ZIP
        // (nur config.php.new), aber in Modulen wie ckeditor/filemanager.
        $wbceMarkers = [
            'framework/Admin.php',
            'framework/class.wb.php',
            'admin/admintools/tool.php',
            'install/index.php',
        ];

        foreach ($wbceMarkers as $marker) {
            if (substr($path, -strlen($marker)) === $marker) {
                // Extrahiere den Basis-Pfad (alles vor dem Marker)
                $basePath = substr($path, 0, strrpos($path, $marker));
                $foundPaths[] = $basePath;
            }
        }
    }

    if (empty($foundPaths)) {
        return false;
    }

    // Zähle welcher Pfad am häufigsten vorkommt
    $pathCounts = array_count_values($foundPaths);
    arsort($pathCounts);

    // Der häufigste Pfad ist wahrscheinlich der richtige
    return key($pathCounts);
}

/**
 * Alternative: Suche nach einem Ordner mit einem bestimmten Namen
 *
 * @param ZipArchive $zip Geöffnetes ZIP-Archiv
 * @param string $folderName Name des zu suchenden Ordners (z.B. 'wbce')
 * @return string|false Vollständiger Pfad zum Ordner
 */
function findFolderByName($zip, $folderName = 'wbce') {
    $candidates = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $path = $stat['name'];

        // Suche nach Ordnern, die den Namen enthalten
        if (preg_match('#/?' . preg_quote($folderName, '#') . '/#', $path)) {
            // Extrahiere den Pfad bis einschließlich des gesuchten Ordners
            $pos = strpos($path, $folderName . '/');
            if ($pos !== false) {
                $candidate = substr($path, 0, $pos + strlen($folderName) + 1);
                $candidates[] = $candidate;
            }
        }
    }

    if (empty($candidates)) {
        return false;
    }

    // Nehme den kürzesten Pfad (wahrscheinlich der richtige)
    usort($candidates, function($a, $b) {
        return strlen($a) - strlen($b);
    });

    return $candidates[0];
}

/**
 * Erweiterte ZIP-Umpack-Funktion mit automatischer Pfad-Erkennung
 *
 * @param string $sourceZip Quell-ZIP-Datei
 * @param string $targetZip Ziel-ZIP-Datei
 * @param string|null $subPath Optionaler Unterordner-Pfad (null = auto-detect)
 * @param string $targetFolderName Name des zu suchenden Ordners bei Auto-Detect
 * @return array ['success' => bool, 'message' => string, 'found_path' => string]
 */
function repackZip($sourceZip, $targetZip, $subPath = null, $targetFolderName = 'wbce', array $messages = array()) {
    $zip = new ZipArchive();
    $newZip = new ZipArchive();
    $result = [
        'success' => false,
        'message' => '',
        'found_path' => ''
    ];

    if ($zip->open($sourceZip) !== TRUE) {
        $result['message'] = sprintf($messages['REPACK_SOURCE_OPEN_FAILED'] ?? 'Source ZIP could not be opened: %s', $sourceZip);
        return $result;
    }
    require_once __DIR__ . '/archive_validator.php';
    $validation = updater_validate_archive($zip, $messages);
    if (!$validation['success']) {
        $zip->close();
        $result['message'] = $validation['message'];
        return $result;
    }

    // Auto-Detect: Finde den richtigen Pfad
    if ($subPath === null) {
        // Methode 1: Suche nach typischen WBCE-Dateien
        $detectedPath = findWbceFolder($zip);

        // Methode 2 (Fallback): Suche nach Ordnername
        if ($detectedPath === false) {
            $detectedPath = findFolderByName($zip, $targetFolderName);
        }

        if ($detectedPath === false) {
            $zip->close();
            $result['message'] = $messages['REPACK_ROOT_NOT_FOUND'] ?? 'The WBCE root folder could not be detected.';
            return $result;
        }

        $subPath = $detectedPath;
        $result['found_path'] = $subPath;
    }

    // Pfad normalisieren (muss mit / enden, falls nicht leer)
    $subPath = rtrim($subPath, '/');
    if ($subPath !== '') {
        $subPath .= '/';
    }

    if ($newZip->open($targetZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        $zip->close();
        $result['message'] = sprintf($messages['REPACK_TARGET_OPEN_FAILED'] ?? 'Target ZIP could not be created: %s', $targetZip);
        return $result;
    }

    $filesAdded = 0;
    $errors = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $fullPath = $stat['name'];

        // Prüfen, ob die Datei im gewünschten Unterordner liegt
        if ($subPath === '' || strpos($fullPath, $subPath) === 0) {
            // Neuen Pfad berechnen (den Präfix abschneiden)
            $relativePath = $subPath === '' ? $fullPath : substr($fullPath, strlen($subPath));

            // Security: Check for path traversal attempts
            if (strpos($relativePath, '..') !== false) {
                $errors[] = sprintf($messages['EXEC_SEC_BAD_PATH'] ?? 'Invalid archive path: %s', $relativePath);
                continue;
            }

            // Nur hinzufügen, wenn es kein leerer Ordnername ist
            if ($relativePath !== false && $relativePath !== "" && substr($relativePath, -1) !== '/') {
                $content = $zip->getFromIndex($i);

                if ($content === false) {
                    $errors[] = sprintf($messages['REPACK_ENTRY_READ_FAILED'] ?? 'Archive entry could not be read: %s', $fullPath);
                    continue;
                }

                if ($newZip->addFromString($relativePath, $content)) {
                    $filesAdded++;
                } else {
                    $errors[] = sprintf($messages['REPACK_ENTRY_ADD_FAILED'] ?? 'Archive entry could not be added: %s', $relativePath);
                }
            }
        }
    }

    $newZip->close();
    $zip->close();

    if ($filesAdded === 0) {
        $result['message'] = sprintf($messages['REPACK_NO_FILES'] ?? 'No files were found below: %s', $subPath);
        @unlink($targetZip); // Leeres ZIP löschen
        return $result;
    }

    $result['success'] = true;
    $result['message'] = sprintf($messages['REPACK_SUCCESS'] ?? '%d files were repacked successfully.', $filesAdded)
        . ($subPath !== '' ? ' '.sprintf($messages['REPACK_FROM_PATH'] ?? 'from %s', $subPath) : '');

    if (!empty($errors)) {
        $result['message'] .= ' (' . sprintf($messages['REPACK_ERROR_COUNT'] ?? '%d errors', count($errors)) . ')';
        $result['errors'] = $errors;
    }

    return $result;
}

/**
 * Debug-Funktion: Zeigt die Struktur eines ZIPs
 *
 * @param string $zipPath Pfad zur ZIP-Datei
 * @param int $maxDepth Maximale Verzeichnistiefe (0 = nur erste Ebene)
 * @return array Liste der Einträge
 */
function debugZipStructure($zipPath, $maxDepth = 2) {
    $zip = new ZipArchive();
    $structure = [];

    if ($zip->open($zipPath) !== TRUE) {
        return ['error' => 'ZIP konnte nicht geöffnet werden'];
    }

    for ($i = 0; $i < min($zip->numFiles, 100); $i++) { // Max 100 Einträge zur Sicherheit
        $stat = $zip->statIndex($i);
        $path = $stat['name'];
        $depth = substr_count($path, '/');

        if ($maxDepth === 0 || $depth <= $maxDepth) {
            $structure[] = [
                'path' => $path,
                'size' => $stat['size'],
                'depth' => $depth
            ];
        }
    }

    $zip->close();
    return $structure;
}

// Beispielaufruf mit Auto-Detection:
/*
$result = repackZip(
    'github-download.zip',  // Quell-ZIP
    'wbceup.zip',          // Ziel-ZIP
    null,                  // Auto-detect
    'wbce'                 // Suche nach 'wbce' Ordner
);

if ($result['success']) {
    echo $result['message'];
    echo "\nGefundener Pfad: " . $result['found_path'];
} else {
    echo "Fehler: " . $result['message'];
}
*/

// Beispielaufruf mit festem Pfad (alte Methode):
/*
repackZip(
    'pack.zip',
    'result.zip',
    'WBCE_CMS-1.6.5/wbce/'  // Fester Pfad
);
*/
?>
