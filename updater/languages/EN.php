<?php
/**
 * Updater - English Language File
 *
 * @category    module
 * @package     updater
 * @version     1.0.2
 * @author      WBCE Community
 * @copyright   2026 WBCE Community
 * @license     MIT License
 */

// prevent this file from being accessed directly
if (!defined('WB_PATH')) {
    exit('Direct access to this file is not allowed');
}

$LANG = [
    // General
    'TOOL_NAME' => 'WBCE Update Assistant',
    'MODULE_DESCRIPTION' => 'Guided and verified WBCE update process; requires PHP 8.2 or newer.',
    'CURRENT_VERSION' => 'Installed Version',

    // Backup Section
    'BACKUP_REQUIRED' => 'Backup Required!',
    'BACKUP_BUTTON' => 'Open Backup Center (new window)',
    'BACKUP_CONFIRMED' => 'I have created and downloaded a backup',
    'BACKUP_CENTER_MISSING' => 'Backup Center is not installed!',
    'INSTALL_BACKUP_CENTER' => 'Install Backup Center',
    'BACKUP_COMPLETE_QUESTION' => 'Have you created and downloaded a backup?',

    // Updates Section
    'AVAILABLE_UPDATES' => 'Available Updates',
    'RECOMMENDED_UPDATE' => 'Recommended Update',
    'OTHER_UPDATES' => 'Other Available Updates',
    'CHECK_UPDATES' => 'Check for Updates',
    'LOADING' => 'Loading Updates',
    'LOADING_DOWNLOAD' => 'Downloading update',
    'LOADING_DIFF_DOWNLOAD' => 'Downloading diff update',
    'LOADING_FULL_DOWNLOAD' => 'Downloading full update',
    'UPDATE_TYPE_DIFF' => 'Diff update available',
    'UPDATE_TYPE_FULL' => 'Full update will be downloaded',
    'DOWNLOAD_PLEASE_WAIT' => 'Please wait, this may take several minutes.',
    'LOADING_UPLOAD' => 'Uploading & processing file',
    'UPLOAD_PLEASE_WAIT' => 'Please wait, this may take a few seconds.',
    'UPDATE_RUNNING' => 'Update process is running',
    'UPDATE_RUNNING_HINT' => 'Please do not close this window. Files are being checked and updated.',
    'NO_UPDATES_AVAILABLE' => 'No updates available',
    'UP_TO_DATE' => 'Your WBCE installation is up to date.',
    'SHOW_ADDITIONAL_UPDATES' => 'Show additional versions',
    'HIDE_ADDITIONAL_UPDATES' => 'Hide additional versions',
    'HIDDEN_UPDATES' => 'hidden',
    'CACHED_DATA_INFO' => 'Note: Showing cached data (age: %s). GitHub API was not reachable.',
    'GITHUB_TIMEOUT_HINT' => 'GitHub API not responding (timeout). Please try again later.',

    // Update source selection
    'UPDATE_SOURCE_TITLE' => 'Choose update source',
    'STORE_UPDATE_TITLE' => 'Load and install from Store',
    'STORE_UPDATE_DESCRIPTION' => 'Select a verified WBCE release and prepare it automatically.',
    'MANUAL_UPDATE_TITLE' => 'Install manual update',
    'MANUAL_UPDATE_DESCRIPTION' => 'Install an existing WBCE ZIP file.',
    'STORE_COUNTDOWN' => 'The Store update starts in {seconds} seconds.',
    'STORE_COUNTDOWN_CANCEL' => 'Cancel',

    // Update Actions
    'DOWNLOAD_FULL_VERSION' => 'Download full version',
    'DOWNLOAD_PREPARE' => 'Download & Prepare Update',
    'START_UPDATE_NOW' => 'Start Update Now',
    'VIEW_DETAILS' => 'View Details',
    'RELEASED' => 'Released',

    // Risk Levels
    'RISK_PATCH' => 'Patch Update (safe)',
    'RISK_MINOR' => 'Minor Update (caution)',
    'RISK_MAJOR' => 'Large Update (high risk)',

    // Manual Upload Section
    'MANUAL_UPLOAD_TITLE' => 'Manual Upload',
    'MANUAL_UPLOAD_DESCRIPTION' => 'Upload your own WBCE ZIP file (e.g., custom build or prepared update package)',
    'SELECT_ZIP_FILE' => 'Select ZIP file',
    'SELECT_FILES_BUTTON' => 'Choose files',
    'SELECT_UPDATE_FILES' => 'Select update ZIP and SHA-256 checksum',
    'UPLOAD_AND_PREPARE' => 'Upload & Install',
    'UPLOAD_NOTE' => 'Note: The ZIP file must contain a "wbce" folder with the WBCE installation or directly contain the WBCE files.',
    'MAX_UPLOAD_SIZE' => 'Max. upload size',
    'UPLOAD_SIZE_WARNING' => 'Warning: Upload limit may be too small for WBCE updates!',
    'RECOMMENDED' => 'Recommended',
    'CHUNK_UPLOAD_INFO' => 'Large packages are uploaded automatically in small chunks. The displayed PHP limit therefore does not limit the total ZIP file size.',
    'JUMP_TO_UPLOAD' => 'Jump to manual upload',

    // Upload Success/Error Messages
    'UPLOAD_SUCCESS_TITLE' => 'Upload successful!',
    'UPLOAD_FILES_PREPARED' => 'The following files have been prepared:',
    'ERROR_NO_FILE_UPLOADED' => 'No file uploaded',
    'ERROR_UPLOAD_FAILED' => 'Upload failed',
    'ERROR_INVALID_ZIP' => 'Invalid ZIP file',
    'ERROR_ZIP_TOO_LARGE' => 'ZIP file is too large',
    'ERROR_UPLOAD_PARTIAL' => 'File was only partially uploaded',
    'ERROR_UPLOAD_NO_TMP_DIR' => 'No temporary directory available',
    'ERROR_UPLOAD_CANT_WRITE' => 'Failed to write to disk',

    // Maintenance Mode
    'MAINTENANCE_MODE' => 'Enable maintenance mode (recommended)',
    'MAINTENANCE_ENABLED' => 'Maintenance mode enabled',
    'MAINTENANCE_INFO' => 'Maintenance mode has been successfully activated. The website is now unavailable to regular visitors (administrators can still log in).',
    'MAINTENANCE_DISABLE_INFO' => 'After the update: Deactivate maintenance mode via Admin Tools → Maintenance Mode Switcher or Backend → Settings.',
    'MAINTENANCE_NOT_ACTIVATED' => 'Maintenance mode could not be activated',
    'MAINTENANCE_MANUAL_INFO' => 'Activate maintenance mode manually via the backend module "Maintenance Mode Switcher" (Admin Tools) or Backend → Settings.',
    'WARNING_NO_MAINTENANCE_TEMPLATE' => 'No maintenance page template found. Install the Maintenance Mode Switcher module or create a maintainance.tpl.php file.',
    'MAINTENANCE_ALREADY_ACTIVE' => 'Maintenance mode already active',
    'MAINTENANCE_ALREADY_ACTIVE_INFO' => 'Maintenance mode was already enabled. The website is unavailable to regular visitors.',

    // Confirmations
    'CONFIRM_DOWNLOAD' => 'Do you want to download and prepare the update package now?',
    'CONFIRM_UPLOAD'   => 'Do you want to upload and prepare the ZIP file now?',
    'CONFIRM_MINOR_UPDATE' => 'CAUTION: This is a minor update! Changes may be required. Do you have a current backup and want to continue?',
    'CONFIRM_MAJOR_UPDATE' => 'WARNING: This is a large update (major version or multiple minor levels)! Significant changes and incompatibilities may occur. Please read the release notes carefully and ensure you have a complete backup. Continue?',

    // Success Messages
    'SUCCESS_TITLE' => 'Update files downloaded successfully!',
    'SUCCESS_FILES_DOWNLOADED' => 'The following files have been saved in the root directory:',
    'SUCCESS_UPDATE_PACKAGE' => 'Update package',
    'SUCCESS_UPDATE_SCRIPT' => 'Update script',
    'READY_TO_UPDATE' => 'Everything is ready for the update!',
    'CLICK_BUTTON_TO_START' => 'Click the button below to start the update.',
    'OR_MANUAL' => 'Or call manually',
    'BACK_TO_UPDATER' => 'Back to Update Assistant',
    'WARNINGS_OCCURRED' => 'Warnings during preparation',

    // Error Messages
    'ERROR_TITLE' => 'Download Error',
    'ERROR_OCCURRED' => 'The following errors occurred:',
    'ERROR_FTAN' => 'Security check failed',
    'ERROR_NO_URL' => 'No download URL specified',
    'ERROR_BACKUP_NOT_CONFIRMED' => 'Please confirm that you have created a backup!',
    'ERROR_NO_WRITE_PERMISSION' => 'No write permissions in root directory! Please check file permissions.',
    'ERROR_DOWNLOAD_FAILED' => 'Download of update package failed',
    'ERROR_DOWNLOAD_EMPTY' => 'The update server returned no usable package.',
    'ERROR_DOWNLOAD_NOT_ZIP' => 'The update server response is not a valid ZIP package.',
    'ERROR_SAVE_FAILED' => 'Saving update package failed',
    'ERROR_REPACK_FAILED' => 'ZIP repack failed',
    'ERROR_UNZIP_DOWNLOAD_FAILED' => 'Download of update script failed',
    'ERROR_UNZIP_SAVE_FAILED' => 'Saving update script failed',
    'ERROR_CONFIG_READ_FAILED' => 'Could not read config.php',
    'ERROR_CONFIG_WRITE_FAILED' => 'Could not write config.php',
    'ERROR_LOADING_UPDATES' => 'Error loading updates',
    'WARNING_MAINTENANCE_FAILED' => 'Could not enable maintenance mode',

    // PHP Compatibility
    'PHP_COMPATIBLE' => 'PHP compatible',
    'PHP_INCOMPATIBLE' => 'PHP incompatible',
    'PHP_EOL_WARNING' => 'Your PHP version is end-of-life',
    'PHP_CURRENT' => 'Current PHP version',
    'PHP_REQUIRED' => 'Required PHP version',
    'PHP_RECOMMENDED' => 'Recommended PHP version',
    'ERROR_PHP_REQUIREMENTS_LOAD' => 'The PHP requirements could not be loaded.',
    'ERROR_PHP_REQUIREMENTS_MISSING' => 'No PHP requirements were found for WBCE %s.',
    'ERROR_PHP_TOO_OLD' => 'PHP %s is too old. Minimum required: %s',
    'ERROR_PHP_TOO_NEW' => 'PHP %s is too new. Maximum supported: %s',
    'WARNING_PHP_EOL_DETAIL' => 'PHP %s reached end-of-life on %s. Security updates are no longer provided.',
    'CONFIRM_PHP_INCOMPATIBLE' => 'WARNING: Your PHP version (%s) is NOT compatible with WBCE %s!

Required: PHP %s - %s
Recommended: PHP %s

The update may cause errors. Please update PHP first.

Continue anyway?',

    // Backup Detection
    'BACKUP_FOUND_HINT' => 'Backup found in /backups directory:',
    'BACKUP_FOUND_MULTIPLE' => 'Backups found in /backups directory (latest:',
    'BACKUP_FOUND_TODAY' => 'today',
    'BACKUP_FOUND_DAYS_AGO' => '%d day(s) ago',
    'BACKUP_FOUND_MORE' => '+%d more',
    'ADMINISTRATION' => 'Administration',

    // Custom Source
    'CUSTOM_SOURCE_TITLE' => 'Custom Update Source',
    'CUSTOM_SOURCE_CONFIGURED' => 'A custom update source is configured:',
    'CUSTOM_SOURCE_BUTTON' => 'Download update from custom source',
    'CUSTOM_SOURCE_WARNING' => "WARNING: You are using a NON-OFFICIAL update source!\n\nSource: %s\n\nThis package has NOT been reviewed by the WBCE Community. You are solely responsible for the security and correctness of the update package.\n\nContinue?",
    'CUSTOM_SOURCE_CONFIRM' => 'Do you want to download and prepare the update package from the custom source now?',

    // Tool Disabled
    'TOOL_DISABLED' => 'The update tool is disabled.',
    'TOOL_DISABLED_INFO' => 'Set $updater_disabled to false in user_config.php to re-enable the tool.',

    // Checksums
    'CHECKSUM_VALIDATED' => 'Download successfully validated',
    'ERROR_CHECKSUM_MISMATCH' => 'Checksum does not match! Download may be corrupted or manipulated.',
    'WARNING_NO_CHECKSUM' => 'No checksum available - download cannot be validated',
    'WARNING_CHECKSUM_DISABLED' => 'WARNING: Checksum verification is disabled. The integrity of the downloaded file cannot be guaranteed.',
    'WARNING_CHECKSUM_DISABLED_MANUAL' => 'NOTE: Automatic checksum verification is disabled. Please verify the checksum manually!',
    'CHECKSUM_INFO' => 'SHA256 Checksum',
    'CHECKSUM_VERIFY_INFO' => 'Compare this checksum with the official release checksum before proceeding.',

    // Upload Validation
    'ERROR_ZIP_ONLY'          => 'Only ZIP files allowed. Uploaded file: %s',
    'ERROR_INVALID_MIME_TYPE' => 'Invalid file type. Only ZIP files allowed. Detected type: %s',
    'ERROR_FILE_TOO_LARGE_MB' => 'File too large. Maximum allowed: %s MB',
    'ERROR_ARCHIVE_ENTRY_LIMIT' => 'The archive contains too many entries.',
    'ERROR_ARCHIVE_INVALID_ENTRY' => 'The archive contains an unreadable entry.',
    'ERROR_ARCHIVE_EXPANDED_LIMIT' => 'The unpacked archive exceeds the 2 GB safety limit.',
    'ERROR_ARCHIVE_SYMLINK' => 'Symbolic links are not allowed in update archives.',
    'REPACK_SOURCE_OPEN_FAILED' => 'Source ZIP could not be opened: %s',
    'REPACK_ROOT_NOT_FOUND' => 'The WBCE root folder could not be detected.',
    'REPACK_TARGET_OPEN_FAILED' => 'Target ZIP could not be created: %s',
    'REPACK_ENTRY_READ_FAILED' => 'Archive entry could not be read: %s',
    'REPACK_ENTRY_ADD_FAILED' => 'Archive entry could not be added: %s',
    'REPACK_NO_FILES' => 'No files were found below: %s',
    'REPACK_SUCCESS' => '%d files were repacked successfully.',
    'REPACK_FROM_PATH' => 'Source: %s',
    'REPACK_ERROR_COUNT' => '%d errors',

    // Execute Update – Step labels
    'EXEC_TITLE'              => 'Update Execution',
    'EXEC_STEP1'              => 'Step 1: Checking PHP compatibility...',
    'EXEC_STEP2'              => 'Step 2: Checking update package...',
    'EXEC_STEP3'              => 'Step 3: Extracting update package...',
    'EXEC_STEP4'              => 'Step 4: Checking WBCE update script...',
    'EXEC_STEP5'              => 'Step 5: Cleanup...',

    // Execute Update – PHP compatibility
    'EXEC_PHP_SKIPPED'        => 'No target version specified, PHP check skipped',
    'EXEC_PHP_CANNOT_CHECK'   => 'PHP compatibility cannot be checked: %s – Continuing with update.',
    'EXEC_PHP_INCOMPAT'       => 'WARNING: PHP incompatibility detected!',
    'EXEC_PHP_CURRENT'        => 'Current PHP version:',
    'EXEC_PHP_REQUIRED_FOR'   => 'Required for WBCE %s:',
    'EXEC_PHP_RECOMMENDED'    => 'Recommended:',
    'EXEC_PHP_CONTINUE_HINT'  => 'The update stopped before replacing files. Please change the PHP version first.',
    'EXEC_PHP_COMPATIBLE_MSG' => 'PHP %s is compatible with WBCE %s',
    'EXEC_PHP_COMPAT_WARN'    => 'PHP %s is not compatible with WBCE %s (required: %s – %s). The update was not executed.',

    // Execute Update – Package & Script
    'EXEC_ZIP_FOUND'          => 'wbceup.zip found (%s MB)',
    'EXEC_ZIP_MISSING'        => 'Update package (wbceup.zip) not found!',
    'EXEC_SCRIPT_FOUND'       => 'install/update.php found',
    'EXEC_SCRIPT_MISSING'     => 'WBCE update script (install/update.php) not found!',
    'EXEC_ZIP_DELETED'        => 'wbceup.zip deleted',
    'EXEC_ZIP_DELETE_FAILED'  => 'wbceup.zip could not be deleted (non-critical)',
    'EXEC_FILES_EXTRACTED'    => '%d files safely extracted',
    'EXEC_EXTRACT_FAILED'     => 'Extraction failed: %s',
    'EXEC_EXTRACT_WRITE_FAILED' => 'The update package could not be extracted completely into the WBCE directory.',
    'EXEC_ZIP_OPEN_FAILED'    => 'ZIP archive could not be opened. Please check the file.',
    'EXEC_DIR_RESOLVE_ERROR'  => 'Target directory could not be resolved',
    'EXEC_SEC_BAD_PATH'       => 'Security warning: Invalid file path detected in ZIP: %s',
    'EXEC_SEC_ABS_PATH'       => 'Security warning: Absolute path detected in ZIP: %s',
    'EXEC_SEC_TRAVERSAL'      => 'Security warning: Path traversal outside target directory detected',

    // Execute Update – Result page
    'EXEC_SUCCESS_TITLE'       => 'Update package extracted successfully!',
    'EXECUTION_RUNNING'        => 'Applying update',
    'EXEC_WARNINGS_TITLE'      => 'Important Warnings:',
    'EXEC_NEXT_STEP_TITLE'     => 'Next Step:',
    'EXEC_NEXT_STEP_INFO'      => 'The WBCE update script is ready. Click the button below to start the update process.',
    'EXEC_WINDOW_HINT'         => 'Important: The update process may take several minutes. Do not close the window!',
    'EXEC_PHP_CHANGE_REMINDER' => 'After the update: Change the PHP version on your server!',
    'EXEC_START_UPDATE_BTN'    => 'Start WBCE Update Now',
    'EXEC_ERROR_TITLE'         => 'Update Error!',
    'CANCEL'                   => 'Cancel',
    'CONFIRM'                  => 'Confirm',
    'ACTION_FAILED'            => 'Action failed',
    'MAX_PACKAGE_SIZE'         => 'Maximum package size',
    'TARGET_VERSION_HINT'      => 'optional, e.g. 1.7.0',
    'AJAX_INVALID_TOKEN'       => 'Invalid or missing security token.',
    'AJAX_VERSION_MISSING'     => 'No target version specified.',
    'AJAX_VERSION_INVALID'     => 'Invalid version format: %s',
    'ERROR_METHOD_NOT_ALLOWED' => 'This action is only available through a secure form request.',
    'ERROR_UPDATE_AUTH_INVALID' => 'The update authorization is invalid or expired. Please prepare the update again.',
    'ERROR_UNAUTHORIZED' => 'Unauthorized.',
    'ERROR_URL_FOPEN_DISABLED' => 'Retrieving external update data is disabled on this server.',
    'ERROR_UNKNOWN' => 'Unknown error',
    'ERROR_RELEASE_API_STATUS' => 'The update server returned HTTP status %d.',
    'ERROR_RELEASE_API_UNAVAILABLE' => 'The update server is currently unavailable. Please try again later.',
    'ERROR_RELEASE_API_INVALID' => 'The update server returned an invalid response.',
    'CHUNK_SESSION_EXPIRED'    => 'The administrator session has expired.',
    'CHUNK_POST_ONLY'          => 'Only POST requests are allowed.',
    'CHUNK_UPLOAD_SESSION_EXPIRED' => 'The upload session has expired. Please reload the page.',
    'CHUNK_TEMP_DIR_FAILED'    => 'The temporary upload directory could not be created.',
    'CHUNK_INVALID_PACKAGE'    => 'Invalid package details.',
    'CHUNK_TEMP_FILE_FAILED'   => 'The temporary upload file could not be created.',
    'CHUNK_UNKNOWN_UPLOAD'     => 'Unknown or expired upload session.',
    'CHUNK_SEQUENCE_ERROR'     => 'An upload chunk is missing or arrived out of sequence.',
    'CHUNK_TOO_LARGE'          => 'The upload chunk exceeds the allowed package size.',
    'CHUNK_WRITE_FAILED'       => 'The upload chunk could not be stored.',
    'CHUNK_INCOMPLETE'         => 'The uploaded package is incomplete.',
    'CHUNK_FINALIZE_FAILED'    => 'The complete package could not be finalized.',
    'CHUNK_UNKNOWN_ACTION'     => 'Unknown upload action.',
    'CHUNK_TOKEN_FAILED'       => 'A secure upload identifier could not be generated.',
    'ERROR_TRANSACTION_TOKEN'  => 'The secure update authorization could not be generated.',
    'ERROR_DATABASE_UNAVAILABLE' => 'The WBCE database is unavailable.',
];
