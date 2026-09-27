<?php

/** Validate an update archive before any entry is extracted or repacked. */
function updater_validate_archive(ZipArchive $zip, array $messages = array())
{
    $fail = static function ($key, $fallback, $value = null) use ($messages) {
        $text = isset($messages[$key]) ? (string)$messages[$key] : $fallback;
        return array('success' => false, 'message' => $value === null ? $text : sprintf($text, $value));
    };
    $count = (int)$zip->numFiles;
    if ($count < 1 || $count > 50000) {
        return $fail('ERROR_ARCHIVE_ENTRY_LIMIT', 'The archive contains too many entries.');
    }
    $expanded = 0;
    for ($index = 0; $index < $count; $index++) {
        $stat = $zip->statIndex($index);
        if (!is_array($stat) || !isset($stat['name'])) {
            return $fail('ERROR_ARCHIVE_INVALID_ENTRY', 'The archive contains an unreadable entry.');
        }
        $name = str_replace('\\', '/', (string)$stat['name']);
        if ($name === '' || strpos($name, "\0") !== false || $name[0] === '/'
            || preg_match('/^[A-Za-z]:\//', $name) || preg_match('#(?:^|/)\.\.(?:/|$)#', $name)) {
            return $fail('EXEC_SEC_BAD_PATH', 'Invalid archive path: %s', $name);
        }
        $size = max(0, (int)($stat['size'] ?? 0));
        $compressed = max(0, (int)($stat['comp_size'] ?? 0));
        $expanded += $size;
        if ($expanded > 2147483648 || ($size > 10485760 && $compressed > 0 && $size / $compressed > 200)) {
            return $fail('ERROR_ARCHIVE_EXPANDED_LIMIT', 'The unpacked archive exceeds the safety limit.');
        }
        $opsys = 0;
        $attributes = 0;
        if ($zip->getExternalAttributesIndex($index, $opsys, $attributes)
            && $opsys === ZipArchive::OPSYS_UNIX && ((($attributes >> 16) & 0170000) === 0120000)) {
            return $fail('ERROR_ARCHIVE_SYMLINK', 'Symbolic links are not allowed in update archives.');
        }
    }
    return array('success' => true, 'message' => '');
}
