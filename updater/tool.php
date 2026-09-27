<?php
/**
 * Updater - Main Interface
 *
 * Hauptoberfläche für den Updateren
 *
 * @category    module
 * @package     updater
 * @version     1.0.2
 * @author      WBCE Community
 * @copyright   2026 WBCE Community
 * @license     MIT License
 *
 * WICHTIG: Diese Datei wird vom WBCE Admin-Tools Framework eingebunden.
 * Folgendes ist bereits verfügbar:
 * - $admin (Admin-Objekt, Header bereits ausgegeben)
 * - $modulePath, $languagePath, $returnUrl, $toolDir, $toolName
 * - config.php, framework-Klassen, Sprachdateien
 */

defined('WB_PATH') or die("This file can't be accessed directly!");

// Load module language file (framework loads core languages, but not module-specific)
$langFile = (file_exists(__DIR__ . '/languages/' . LANGUAGE . '.php'))
    ? __DIR__ . '/languages/' . LANGUAGE . '.php'
    : __DIR__ . '/languages/EN.php';
require_once $langFile;
require_once __DIR__ . '/config_defaults.php';
require_once __DIR__ . '/TwigView.php';
require_once __DIR__ . '/update_transaction.php';

// Get current WBCE version from database
// $database is globally available through the framework
global $database;
if (!isset($database) || !is_object($database) || !method_exists($database, 'query')) {
    echo '<div class="error">'.htmlspecialchars((string)($LANG['ERROR_DATABASE_UNAVAILABLE'] ?? 'The database is unavailable.'), ENT_QUOTES, 'UTF-8').'</div>';
    return;
}

// The dashboard release-note widget posts here rather than to an external
// module URL.  This stays inside the authenticated backend request path and
// therefore retains the regular FTAN even when global request filtering is
// active.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
    && (string) ($_POST['dashboard_action'] ?? '') === 'dismiss_update_changelog') {
    if (!$admin->checkFTAN()) {
        echo '<div class="error">Die Sicherheitsprüfung ist fehlgeschlagen.</div>';
        return;
    }
    $changelogVersion = trim((string) Settings::GetDb('wbce_update_changelog_version', ''));
    if ($changelogVersion !== '') {
        Settings::Set('wbce_update_changelog_dismissed_version', $changelogVersion);
    }
    unset($_SESSION['WBCE_UPDATE_CHANGELOG']);
    $dashboardUrl = htmlspecialchars(ADMIN_URL . '/start/index.php', ENT_QUOTES, 'UTF-8');
    echo '<script>window.location.replace(' . json_encode(ADMIN_URL . '/start/index.php') . ');</script>'
        . '<p><a href="' . $dashboardUrl . '">Zurück zum Dashboard</a></p>';
    return;
}

// Security: Validate and sanitize TABLE_PREFIX to prevent SQL injection
$safe_table_prefix = preg_replace('/[^a-zA-Z0-9_]/', '', TABLE_PREFIX);

// Security: Use parameterized query (escape values)
$setting_name = $database->escapeString('wbce_version');
$result = $database->query("SELECT value FROM " . $safe_table_prefix . "settings WHERE name='" . $setting_name . "'");
if ($result && (!method_exists($result, 'error') || !$result->error()) && $result->numRows() > 0) {
    $row = $result->fetchRow(MYSQLI_ASSOC);
    $current_version = $row['value'];
} else {
    $current_version = 'Unknown';
}

// Check if Backup Center is installed (using escaped values)
$addon_dir = $database->escapeString('backup_center');
$addon_type = $database->escapeString('module');
$result = $database->query("SELECT * FROM " . $safe_table_prefix . "addons WHERE directory='" . $addon_dir . "' AND type='" . $addon_type . "'");
$backup_center_installed = ($result && (!method_exists($result, 'error') || !$result->error()) && $result->numRows() > 0);

// Tool disabled check
if ($updater_disabled) {
    echo '<div class="alert-warning" style="padding:20px;">';
    echo '<strong>' . htmlspecialchars($LANG['TOOL_DISABLED']) . '</strong><br>';
    echo htmlspecialchars($LANG['TOOL_DISABLED_INFO']);
    echo '</div>';
    return;
}

// Separate CSRF token for the repeated chunk requests. The regular FTAN is
// intentionally kept unused until the assembled package is submitted.
$chunk_upload_token = '';
try { $chunk_upload_token = bin2hex(random_bytes(32)); } catch (Throwable $exception) { }
updater_transaction_session_set('WBCE_UPDATER_CHUNK_TOKEN', $chunk_upload_token);
if ($chunk_upload_token !== '') {
    $chunkTokenPath = WB_PATH . '/temp/.wbce-updater-chunk-token-' . hash('sha256', $chunk_upload_token) . '.json';
    @file_put_contents($chunkTokenPath, json_encode(array('expires' => time() + 3600)), LOCK_EX);
    @chmod($chunkTokenPath, 0600);
}
ob_start();

// Backup detection: scan WB_PATH/backups for recent ZIP files
$backup_found = false;
$backup_found_text = '';
$backups_dir = WB_PATH . (defined('BACKUP_DATA_DIR') ? BACKUP_DATA_DIR : '/backups/');
if (is_dir($backups_dir)) {
    $zip_files = glob($backups_dir . '/*.zip');
    if (!empty($zip_files)) {
        // Only consider files >= 100 KB (real backups, not stubs)
        $zip_files = array_filter($zip_files, function($f) { return filesize($f) >= 102400; });
        if (!empty($zip_files)) {
            usort($zip_files, function($a, $b) { return filemtime($b) - filemtime($a); });
            $latest = $zip_files[0];
            $age_days = (int) round((time() - filemtime($latest)) / 86400);
            $size_mb  = round(filesize($latest) / 1024 / 1024, 1);
            $age_str  = ($age_days === 0)
                ? $LANG['BACKUP_FOUND_TODAY']
                : sprintf($LANG['BACKUP_FOUND_DAYS_AGO'], $age_days);
            $count    = count($zip_files);
            $prefix   = ($count > 1) ? $LANG['BACKUP_FOUND_MULTIPLE'] : $LANG['BACKUP_FOUND_HINT'];
            try { $backupTimezone = new DateTimeZone(defined('DEFAULT_TIMEZONE') ? (string)DEFAULT_TIMEZONE : date_default_timezone_get()); } catch (Throwable $ignored) { $backupTimezone = new DateTimeZone('Europe/Berlin'); }
            $backupTimestamp = new DateTime('@'.filemtime($latest)); $backupTimestamp->setTimezone($backupTimezone);
            $latest_backup_display_time = $backupTimestamp->format('d.m.Y H:i');
            $backup_found = true;
            $backup_found_text = $prefix . ' <em>' . htmlspecialchars(basename($latest))
                . ' (' . $size_mb . ' MB, ' . $age_str . ')'
                . ($count > 1 ? ', ' . sprintf($LANG['BACKUP_FOUND_MORE'], $count - 1) . ')' : '') . '</em>';
        }
    }
}

// A one-time marker lets the page wait until the download response finished.
try { $backup_download_token = bin2hex(random_bytes(24)); } catch (Throwable $ignored) { $backup_download_token = sha1(uniqid('', true)); }
$_SESSION['updater_backup_download'] = array('token'=>$backup_download_token,'complete'=>false,'expires'=>time()+3600);
// Add custom CSS
?>
<script>
// CRITICAL: Define ADMIN_URL early for WBCE 1.4.3 compatibility
if (typeof ADMIN_URL === 'undefined') {
    window.ADMIN_URL = '<?php echo ADMIN_URL; ?>';
}
</script>
<link rel="stylesheet" href="<?php echo WB_URL; ?>/modules/updater/css/backend.css?v=1.0.57">

<div class="wbce-updater-container">
        <h1><?php echo htmlspecialchars($LANG['TOOL_NAME']); ?></h1>
        <p class="version-display"><strong><?php echo htmlspecialchars($LANG['CURRENT_VERSION']); ?>:</strong> <?php echo htmlspecialchars($current_version); ?></p>

        <!-- Backup step -->
        <div class="backup-section">
            <h3 class="section-title-backup">Backup</h3>
            <div class="backup-warning">
                <label class="updater-switch-row" for="enable_maintenance">
                    <span class="updater-switch"><input type="checkbox" id="enable_maintenance" checked><i aria-hidden="true"></i></span>
                    <span><?php echo $LANG['MAINTENANCE_MODE']; ?></span>
                </label>
                <input type="checkbox" id="backup_confirmed" hidden>
                <div class="updater-backup-grid" id="updater-backup-actions">
                    <button type="button" class="updater-backup-card" onclick="runUpdaterBackup()"><strong>🔄 Backup erstellen und herunterladen</strong><span>Erstellt eine lokale Sicherung und lädt sie danach automatisch herunter.</span></button>
                    <?php if (!$backup_found): ?>
                    <button type="button" class="updater-backup-card" onclick="confirmExistingBackup()"><strong>✓ Ich habe ein manuelles Backup</strong><span>Ich bestätige, dass eine aktuelle Sicherung vorhanden ist.</span></button>
                    <?php endif; ?>
                    <button type="button" id="continue-without-backup" class="updater-backup-card" onclick="continueWithoutBackup()"><strong>→ Ohne Backup fortfahren</strong><span>Das Update ohne neue Sicherung starten.</span></button>
                </div>
            </div>
        </div>

        <!-- Update source selection -->
        <div id="update-source-section" class="updates-section updater-source-selection" hidden>
            <h3 id="update-source-title" class="section-title-gray"><?php echo htmlspecialchars($LANG['UPDATE_SOURCE_TITLE'] ?? 'Update-Quelle wählen'); ?></h3>
            <div id="loading" class="loading-indicator" role="status" aria-label="<?php echo htmlspecialchars($LANG['LOADING'] ?? 'Lade Updates', ENT_QUOTES, 'UTF-8'); ?>">
                <span class="source-loading-spinner" aria-hidden="true"></span>
            </div>
            <div id="updater-source-grid" class="updater-source-grid" hidden>
                <button type="button" class="updater-source-card is-selected" id="store-source-button" onclick="startAvailableStoreUpdate()" hidden>
                    <strong>🏪 <?php echo htmlspecialchars($LANG['STORE_UPDATE_TITLE'] ?? 'Aus dem Store laden'); ?></strong>
                    <span><?php echo htmlspecialchars($LANG['STORE_UPDATE_DESCRIPTION'] ?? 'Geprüfte WBCE-Version auswählen und automatisch vorbereiten.'); ?></span>
                </button>
                <button type="button" class="updater-source-card" id="manual-source-button" onclick="selectManualUpload()">
                    <strong>📤 <?php echo htmlspecialchars($LANG['MANUAL_UPDATE_TITLE'] ?? 'Manuelles Update einspielen'); ?></strong>
                    <span><?php echo htmlspecialchars($LANG['MANUAL_UPLOAD_DESCRIPTION']); ?></span>
                </button>

                <!-- Manual upload remains part of the selected update source. -->
                <div id="manual-upload-section" class="updater-manual-upload" hidden>
            <form id="upload-form" method="post" action="<?php echo WB_URL; ?>/modules/updater/upload.php" enctype="multipart/form-data" onsubmit="return handleUploadSubmit(event);">
                <?php echo $admin->getFTAN(); ?>

                <div class="upload-controls">
                    <input type="file"
                           name="update_files[]"
                           id="update_files"
                           accept=".zip,.sha256,.sha,.checksum,application/zip,text/plain"
                           class="file-input"
                           multiple
                           onchange="enableUploadButton()"
                           aria-label="<?php echo htmlspecialchars($LANG['SELECT_UPDATE_FILES'] ?? 'Update-ZIP und SHA-256-Prüfsumme auswählen', ENT_QUOTES, 'UTF-8'); ?>">
                    <label class="updater-upload-picker" for="update_files">
                        <span class="updater-upload-picker-button"><?php echo htmlspecialchars($LANG['SELECT_FILES_BUTTON'] ?? 'Dateien auswählen', ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="updater-upload-picker-label" id="update_files_label"><?php echo htmlspecialchars($LANG['SELECT_UPDATE_FILES'] ?? 'Update-ZIP und SHA-256-Prüfsumme auswählen', ENT_QUOTES, 'UTF-8'); ?></span>
                    </label>

                    <button type="submit"
                            id="upload-button"
                            class="btn-primary"
                            disabled>
                        📤 <?php echo $LANG['UPLOAD_AND_PREPARE']; ?>
                    </button>
                </div>

                <input type="hidden" name="backup_confirmed_upload" id="form_backup_confirmed_upload">
                <input type="hidden" name="enable_maintenance_upload" id="form_enable_maintenance_upload">
                <input type="hidden" name="target_version_upload" id="form_target_version_upload">
                <input type="hidden" name="chunk_upload_id" id="chunk_upload_id" value="">

            </form>
            <script>
            window.wbceUpdaterMaxPackageBytes = <?php echo (int) WBCE_UPDATER_MAX_UPLOAD_SIZE; ?>;
            window.phpUploadMaxBytes = 2 * 1024 * 1024;
            window.phpPostMaxBytes = 2 * 1024 * 1024;
            window.wbceUpdaterChunkToken = <?php echo json_encode($chunk_upload_token, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            window.wbceUpdaterChunkUrl = '<?php echo WB_URL; ?>/modules/updater/chunk_upload.php';
            </script>
                </div>
            </div>
            <div id="store-countdown" class="updater-store-countdown" hidden aria-live="polite"></div>
            <div id="updates-container"></div>
        </div>

        <?php if (!empty($updater_custom_source_url)): ?>
        <!-- Custom Source Section -->
        <div class="updates-section" style="margin-top: 30px; border-left: 4px solid #f39c12;">
            <h3 class="section-title-gray">⚠️ <?php echo htmlspecialchars($LANG['CUSTOM_SOURCE_TITLE']); ?></h3>
            <div style="background:#fff3cd; border:1px solid #ffc107; border-radius:4px; padding:12px 15px; margin-bottom:15px; font-size:13px;">
                <strong><?php echo htmlspecialchars($LANG['CUSTOM_SOURCE_CONFIGURED']); ?></strong><br>
                <code style="word-break:break-all;"><?php echo htmlspecialchars($updater_custom_source_url); ?></code>
            </div>
            <button type="button"
                    class="btn-download download-button"
                    onclick="prepareCustomSourceUpdate()"
                    disabled>
                📥 <?php echo htmlspecialchars($LANG['CUSTOM_SOURCE_BUTTON']); ?>
            </button>
        </div>
        <?php endif; ?>

        <!-- Update Form (hidden) -->
        <form id="update-form" method="post" action="<?php echo WB_URL; ?>/modules/updater/download.php" style="display:none;">
            <?php echo $admin->getFTAN(); ?>
            <input type="hidden" name="download_url" id="form_download_url">
            <input type="hidden" name="target_version" id="form_target_version">
            <input type="hidden" name="checksum" id="form_checksum">
            <input type="hidden" name="store_offer" id="form_store_offer">
            <input type="hidden" name="backup_confirmed" id="form_backup_confirmed">
            <input type="hidden" name="enable_maintenance" id="form_enable_maintenance">
            <input type="hidden" name="force_full" id="form_force_full" value="0">
        </form>

    <script>
        // Current version for comparison
        const currentVersion = '<?php echo $current_version; ?>';

        /**
         * Opens Backup Center in new window
         */
        function openBackupCenter() {
            const backupWindow = window.open(
                '<?php echo ADMIN_URL; ?>/admintools/tool.php?tool=backup_center',
                '_blank',
                'width=1000,height=800'
            );

        }

        /**
         * Enable/disable update buttons based on backup confirmation
         */
        function enableUpdateButton() {
            const checkbox = document.getElementById('backup_confirmed');
            const buttons = document.querySelectorAll('.download-button');

            buttons.forEach(button => {
                button.disabled = !checkbox.checked;
            });

            // Upload button is handled separately by enableUploadButton()
            // It only depends on file selection, not checkbox state
        }

        /**
         * Load available updates from GitHub
         */
        function loadAvailableUpdates() {
            const container = document.getElementById('updates-container');
            const loading = document.getElementById('loading');
            const sourceGrid = document.getElementById('updater-source-grid');
            const manualButton = document.getElementById('manual-source-button');
            const storeButton = document.getElementById('store-source-button');
            const sourceTitle = document.getElementById('update-source-title');
            const sourceTitleDefault = <?php echo json_encode($LANG['UPDATE_SOURCE_TITLE'] ?? 'Update-Quelle wählen'); ?>;
            if (sourceTitle) sourceTitle.textContent = '<?php echo addslashes($LANG['LOADING'] ?? 'Lade Updates'); ?>…';

            loading.style.display = 'block';
            sourceGrid.hidden = true;
            manualButton.hidden = true;
            storeButton.hidden = true;
            container.innerHTML = '';

            return fetch('<?php echo WB_URL; ?>/modules/updater/store_updates.php', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            })
            .then(response => {
                // First check if response is ok
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('Server error (' + response.status + '): ' + (text || 'Unknown error'));
                    });
                }
                // Get text first to check for empty response
                return response.text();
            })
            .then(text => {
                // Check if response is empty
                if (!text || text.trim() === '') {
                    throw new Error('Der Update-Dienst hat keine Antwort geliefert. Bitte Updater-Version und PHP-Fehlerprotokoll prüfen.');
                }
                // Try to parse JSON
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON:', text.substring(0, 500));
                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                }
            })
            .then(data => {
                if (sourceTitle) sourceTitle.textContent = sourceTitleDefault;
                loading.style.display = 'none';
                sourceGrid.hidden = false;
                manualButton.hidden = false;

                if (data.error) {
                    // Check if error is a timeout/gateway error
                    let errorHtml = '<div class="error-box">' +
                        '<?php echo $LANG['ERROR_LOADING_UPDATES']; ?>: ' +
                        data.error;

                    if (data.error.includes('Timeout') || data.error.includes('Gateway')) {
                        errorHtml += '<br><br><em><?php echo $LANG['GITHUB_TIMEOUT_HINT']; ?></em>';
                    }

                    errorHtml += '</div>';
                    container.innerHTML = errorHtml;
                    return;
                }

                // Show cache info if cached data is used
                let cacheInfo = '';
                if (data.cached && data.cache_age) {
                    const minutes = Math.floor(data.cache_age / 60);
                    const ageStr = minutes < 1 ? '< 1 Min.' : minutes + ' Min.';
                    cacheInfo = '<div class="info-box" style="margin-bottom: 15px;">' +
                        '<?php echo str_replace('%s', '\' + ageStr + \'', $LANG['CACHED_DATA_INFO']); ?>' +
                        '</div>';
                }

                if (data.updates && data.updates.length > 0) {
                    document.getElementById('store-source-button').hidden = false;
                    document.getElementById('updater-source-grid').classList.add('has-store-update');
                    container.innerHTML = cacheInfo;
                    displayUpdates(data.updates);
                } else {
                    document.getElementById('store-source-button').hidden = true;
                    document.getElementById('updater-source-grid').classList.remove('has-store-update');
                    container.innerHTML = '';
                }
            })
            .catch(error => {
                if (sourceTitle) sourceTitle.textContent = sourceTitleDefault;
                loading.style.display = 'none';
                sourceGrid.hidden = false;
                manualButton.hidden = false;
                storeButton.hidden = true;
                sourceGrid.classList.remove('has-store-update');
                container.innerHTML = '<div class="error-box">' +
                    '<?php echo $LANG['ERROR_LOADING_UPDATES']; ?>: ' +
                    error.message + '</div>';
                console.error('Error:', error);
            });
        }

        /**
         * Keep Store releases in a deterministic, newest-first order.  The
         * Store may contain several development builds of the same CMS line;
         * hiding or reordering them can otherwise select an older build.
         */
        function markUpdatesVisibility(updates) {
            return updates
                .filter(update => isNewerVersion(currentVersion, update.version))
                .map(update => ({...update, parsed: parseVersion(update.version), hidden: false}))
                .sort((left, right) => compareVersions(right.version, left.version));
        }

        /**
         * Parse version string into object
         */
        function parseVersion(versionStr) {
            const match = String(versionStr || '').match(/^v?(\d+)(?:\.(\d+))?(?:\.(\d+))?(?:-dev\.(\d+))?/i);
            return {
                major: match ? Number(match[1]) : 0,
                minor: match ? Number(match[2] || 0) : 0,
                patch: match ? Number(match[3] || 0) : 0,
                development: match && match[4] !== undefined ? Number(match[4]) : null
            };
        }

        function compareVersions(left, right) {
            const a = parseVersion(left), b = parseVersion(right);
            for (const part of ['major', 'minor', 'patch']) {
                if (a[part] !== b[part]) return a[part] > b[part] ? 1 : -1;
            }
            // A final version is newer than its corresponding development build.
            if (a.development === null && b.development !== null) return 1;
            if (a.development !== null && b.development === null) return -1;
            if (a.development !== null && b.development !== null && a.development !== b.development) {
                return a.development > b.development ? 1 : -1;
            }
            return 0;
        }

        /**
         * Toggle visibility of hidden updates
         */
        function toggleHiddenUpdates() {
            const container = document.getElementById('updates-container');
            const hiddenItems = container.querySelectorAll('.update-hidden');
            const toggleBtns = container.querySelectorAll('.toggle-hidden-btn');
            const isCurrentlyHidden = hiddenItems.length > 0 && hiddenItems[0].style.display === 'none';

            hiddenItems.forEach(item => {
                item.style.display = isCurrentlyHidden ? 'block' : 'none';
            });

            toggleBtns.forEach(btn => {
                btn.textContent = isCurrentlyHidden
                    ? '<?php echo $LANG['HIDE_ADDITIONAL_UPDATES']; ?>'
                    : '<?php echo $LANG['SHOW_ADDITIONAL_UPDATES']; ?>';
            });
        }

        /**
         * Display available updates
         */
        function displayUpdates(updates) {
            const container = document.getElementById('updates-container');
            container.innerHTML = '';

            // Mark updates with visibility according to rules
            const allUpdates = markUpdatesVisibility(updates);

            // Count hidden updates
            const hiddenCount = allUpdates.filter(u => u.hidden).length;

            // The first entry is always the newest Store version and is the
            // default target for both the button and the ten-second timer.
            allUpdates.forEach(update => {
                update.riskLevel = calculateRiskLevel(currentVersion, update.version);
            });
            const recommendedUpdate = allUpdates.length ? allUpdates[0] : null;
            const otherUpdates = allUpdates.slice(1);

            // Display recommended update
            if (recommendedUpdate) {
                const recSection = document.createElement('div');
                recSection.className = 'recommended-section';
                recSection.innerHTML = '<h3 class="section-title-green"><?php echo $LANG['RECOMMENDED_UPDATE']; ?></h3>';
                recSection.appendChild(createUpdateCard(recommendedUpdate, true, false));
                container.appendChild(recSection);
            }

            // Display other updates section
            if (otherUpdates.length > 0) {
                const otherSection = document.createElement('div');
                otherSection.className = 'other-updates-section';
                otherSection.innerHTML = '<h3 class="section-title-gray"><?php echo $LANG['OTHER_UPDATES']; ?></h3>';

                // Add toggle button at the top if there are hidden updates
                if (hiddenCount > 0) {
                    const toggleBtnTop = document.createElement('button');
                    toggleBtnTop.type = 'button';
                    toggleBtnTop.className = 'btn-toggle toggle-hidden-btn';
                    toggleBtnTop.textContent = '<?php echo $LANG['SHOW_ADDITIONAL_UPDATES']; ?>';
                    toggleBtnTop.onclick = toggleHiddenUpdates;
                    otherSection.appendChild(toggleBtnTop);

                    const countInfo = document.createElement('span');
                    countInfo.className = 'hidden-count';
                    countInfo.textContent = ' (' + hiddenCount + ' <?php echo $LANG['HIDDEN_UPDATES']; ?>)';
                    otherSection.appendChild(countInfo);
                }

                otherUpdates.forEach(update => {
                    otherSection.appendChild(createUpdateCard(update, false, update.hidden));
                });

                // Add toggle button at the bottom if there are hidden updates
                if (hiddenCount > 0) {
                    const toggleBtnBottom = document.createElement('button');
                    toggleBtnBottom.type = 'button';
                    toggleBtnBottom.className = 'btn-toggle toggle-hidden-btn';
                    toggleBtnBottom.textContent = '<?php echo $LANG['SHOW_ADDITIONAL_UPDATES']; ?>';
                    toggleBtnBottom.onclick = toggleHiddenUpdates;
                    otherSection.appendChild(toggleBtnBottom);
                }

                container.appendChild(otherSection);
            }

            // Check if no updates were found
            if (!recommendedUpdate && otherUpdates.length === 0) {
                container.innerHTML = '<div class="info-box">' +
                    '<strong>✓ <?php echo $LANG['NO_UPDATES_AVAILABLE']; ?></strong><br>' +
                    '<?php echo $LANG['UP_TO_DATE']; ?>' +
                    '</div>';
            }

            // Enable/disable buttons based on backup checkbox
            enableUpdateButton();
            window.updaterRecommendedStoreUpdate = recommendedUpdate || allUpdates.find(function(update) { return !update.hidden; }) || null;
            scheduleStoreUpdate(window.updaterRecommendedStoreUpdate);
        }

        let storeCountdownTimer = null;
        function startAvailableStoreUpdate() {
            const update = window.updaterRecommendedStoreUpdate;
            if (!update) { loadAvailableUpdates(); return; }
            scheduleStoreUpdate(update);
        }
        function scheduleStoreUpdate(update) {
            const countdown = document.getElementById('store-countdown');
            if (storeCountdownTimer) { window.clearInterval(storeCountdownTimer); storeCountdownTimer = null; }
            if (!countdown || !update) { if (countdown) countdown.hidden = true; return; }
            // Keep the automatic-start information with the action it controls.
            const actions = document.querySelector('.recommended-section .update-actions');
            if (actions && countdown.parentElement !== actions) actions.appendChild(countdown);
            let remaining = 10;
            const render = function() {
                countdown.hidden = false;
                countdown.innerHTML = '<?php echo addslashes($LANG['STORE_COUNTDOWN'] ?? 'Das Store-Update startet in {seconds} Sekunden.'); ?>'.replace('{seconds}', remaining)
                    + ' <button type="button" class="btn-secondary" id="store-countdown-cancel"><?php echo addslashes($LANG['STORE_COUNTDOWN_CANCEL'] ?? 'Abbrechen'); ?></button>';
                const cancel = document.getElementById('store-countdown-cancel');
                if (cancel) cancel.onclick = function() { window.clearInterval(storeCountdownTimer); storeCountdownTimer = null; countdown.hidden = true; };
            };
            render();
            storeCountdownTimer = window.setInterval(function() {
                remaining -= 1;
                if (remaining > 0) { render(); return; }
                window.clearInterval(storeCountdownTimer); storeCountdownTimer = null; countdown.hidden = true;
                if (!document.getElementById('backup_confirmed').checked) {
                    updaterToast(<?php echo json_encode($LANG['ERROR_BACKUP_NOT_CONFIRMED']); ?>, true);
                    return;
                }
                prepareUpdate(update.download_url || '', update.version, update.riskLevel || 'patch', update.checksum || '', update.offer_id || '', true);
            }, 1000);
        }

        function formatFileSize(bytes) {
            const value = Number(bytes) || 0;
            if (value < 1024) return value + ' B';
            if (value < 1048576) return (value / 1024).toFixed(1) + ' KB';
            return (value / 1048576).toFixed(1) + ' MB';
        }

        /**
         * Create update card element
         */
        function createUpdateCard(update, isRecommended, isHidden = false) {
            const div = document.createElement('div');
            div.className = 'update-item risk-' + update.riskLevel + (isHidden ? ' update-hidden' : '');

            // Hide element initially if marked as hidden
            if (isHidden) {
                div.style.display = 'none';
            }

            const riskLabels = {
                'patch': '<?php echo $LANG['RISK_PATCH']; ?>',
                'minor': '<?php echo $LANG['RISK_MINOR']; ?>',
                'major': '<?php echo $LANG['RISK_MAJOR']; ?>'
            };

            const riskIcons = {
                'patch': '✓',
                'minor': '⚠️',
                'major': '🛑'
            };

            div.innerHTML = `
                <div class="update-header">
                    <div class="update-version-title">
                        <h4>${currentVersion} → ${update.version}</h4>
                        <span class="badge badge-${update.riskLevel}">${riskIcons[update.riskLevel]} ${riskLabels[update.riskLevel]}</span>
                    </div>
                </div>
                <div class="update-content">
                    <h5 class="update-name">${escapeHtml(update.name)}</h5>
                    <p class="update-date"><?php echo $LANG['RELEASED']; ?>: ${formatDate(update.published_at)}</p>
                    <p class="update-package-type ${update.update_type === 'diff' ? 'is-diff' : 'is-full'}">${update.update_type === 'diff' ? '<?php echo addslashes($LANG['UPDATE_TYPE_DIFF']); ?>' : '<?php echo addslashes($LANG['UPDATE_TYPE_FULL']); ?>'}${Number(update.download_size) > 0 ? ' · ' + formatFileSize(update.download_size) : ''}</p>
                    ${update.body ? '<div class="update-description">' + escapeHtml(update.body.substring(0, 250)) + ' ...</div>' : ''}
                    ${update.html_url ? '<p class="update-link"><a href="' + escapeHtml(update.html_url) + '" target="_blank"><?php echo $LANG['VIEW_DETAILS']; ?></a></p>' : ''}
                </div>
                <div class="update-actions">
                    <button type="button"
                            class="btn-download download-button"
                            onclick="prepareUpdate('${escapeHtml(update.download_url || '')}', '${escapeHtml(update.version)}', '${update.riskLevel}', '${escapeHtml(update.checksum || '')}', '${escapeHtml(update.offer_id || '')}')"
                            disabled>
                        📥 <?php echo $LANG['DOWNLOAD_PREPARE']; ?>
                    </button>
                    ${update.update_type === 'diff' ? `<button type="button" class="btn-secondary download-button" onclick="prepareUpdate('${escapeHtml(update.download_url || '')}', '${escapeHtml(update.version)}', '${update.riskLevel}', '${escapeHtml(update.checksum || '')}', '${escapeHtml(update.offer_id || '')}', false, true)" disabled>📦 <?php echo $LANG['DOWNLOAD_FULL_VERSION']; ?></button>` : ''}
                </div>
            `;

            return div;
        }

        /**
         * Check if target version is newer than current version
         */
        function isNewerVersion(current, target) {
            return compareVersions(target, current) > 0;
        }

        /**
         * Calculate risk level based on version comparison
         *
         * Risikostufen:
         * - major (rot): Major-Version unterschiedlich ODER Minor-Differenz > 1
         * - minor (gelb): Minor-Differenz == 1
         * - patch (grün): Nur Patch-Version unterschiedlich
         */
        function calculateRiskLevel(current, target) {
            const currentParts = parseVersion(current);
            const targetParts = parseVersion(target);

            // Major version change (1.x → 2.x) = major risk
            if (currentParts.major !== targetParts.major) return 'major';

            // Minor version difference
            const minorDiff = targetParts.minor - currentParts.minor;

            // Minor-Sprung > 1 (z.B. 1.5 → 1.7) = major risk (rot)
            if (minorDiff > 1) return 'major';

            // Minor-Sprung == 1 (z.B. 1.5 → 1.6) = minor risk (gelb)
            if (minorDiff === 1) return 'minor';

            // Patch version change (1.6.3 → 1.6.5) = patch risk (grün)
            return 'patch';
        }

        /**
         * Prepare update by submitting form
         */
        async function prepareUpdate(downloadUrl, targetVersion, riskLevel, checksum, storeOffer, automatic = false, forceFull = false) {
            // A deliberate card selection must always win over the automatic
            // recommendation. Do not let its countdown replace this target.
            if (!automatic && storeCountdownTimer) { window.clearInterval(storeCountdownTimer); storeCountdownTimer = null; }
            if (!automatic) { const countdown = document.getElementById('store-countdown'); if (countdown) countdown.hidden = true; }
            // Extra confirmation for minor and major updates
            if (!automatic && riskLevel === 'minor') {
                if (!(await updaterConfirm(<?php echo json_encode($LANG['CONFIRM_MINOR_UPDATE']); ?>))) {
                    return;
                }
            } else if (!automatic && riskLevel === 'major') {
                if (!(await updaterConfirm(<?php echo json_encode($LANG['CONFIRM_MAJOR_UPDATE']); ?>))) {
                    return;
                }
            }

            // Fill form and submit
            document.getElementById('form_download_url').value = downloadUrl || '';
            document.getElementById('form_store_offer').value = storeOffer || '';
            document.getElementById('form_target_version').value = targetVersion;
            document.getElementById('form_checksum').value = checksum || '';
            document.getElementById('form_backup_confirmed').value =
                document.getElementById('backup_confirmed').checked ? '1' : '0';
            document.getElementById('form_enable_maintenance').value =
                document.getElementById('enable_maintenance').checked ? '1' : '0';
            document.getElementById('form_force_full').value = forceFull ? '1' : '0';

            await downloadStoreUpdate();
        }


        function updaterMaintenanceIndicator(enabled) {
            document.querySelectorAll('.wbcemm-link, .wbcemm').forEach(function(node) { const link = node.closest ? node.closest('.wbcemm-link') : null; (link || node).remove(); });
            if (!enabled) return;
            const host = document.querySelector('#topNavigation .navbar-text, #topNavigation [data-maintenance-indicator]');
            if (!host) return;
            const link = document.createElement('a'); link.className = 'wbcemm-link'; link.href = '<?php echo ADMIN_URL; ?>/admintools/tool.php?tool=maintainance_mode'; link.title = 'Wartungsmodus-Einstellungen'; link.setAttribute('aria-label', link.title); link.innerHTML = '<span class="fa fa-wrench wbcemm" aria-hidden="true"></span>';
            host.appendChild(document.createTextNode(' ')); host.appendChild(link);
        }

        async function downloadStoreUpdate() {
            const form = document.getElementById('update-form');
            const overlayText = document.querySelector('#loading-overlay .loading-text');
            const overlaySubtext = document.querySelector('#loading-overlay .loading-subtext');
            const overlayProgress = document.getElementById('overlay-upload-progress');
            const progressBar = overlayProgress.querySelector('span');
            const progressText = overlayProgress.querySelector('strong');
            const prepareLog = document.getElementById('updater-prepare-log');
            const prepareLogItems = document.getElementById('updater-prepare-log-items');
            if (overlayText) overlayText.textContent = 'Update wird aus dem Store geladen …';
            if (overlaySubtext) overlaySubtext.textContent = 'Das Update-Paket wird geprüft und vorbereitet.';
            prepareLog.hidden = false; prepareLogItems.replaceChildren();
            overlayProgress.hidden = false; progressBar.style.width = '0%'; progressText.textContent = '0 %';
            showLoadingSpinner();
            try {
                const request = new FormData(form); request.append('stream', '1');
                const response = await fetch(form.action, {method:'POST', body:request, credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest', 'Accept':'application/x-ndjson'}});
                if (!response.body) throw new Error('Das Store-Update konnte nicht vorbereitet werden.');
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/x-ndjson')) {
                    const answer = (await response.text()).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                    throw new Error(answer ? 'Die Update-Vorbereitung wurde abgewiesen: ' + answer.slice(0, 240) : 'Die Update-Vorbereitung wurde abgewiesen. Bitte die Seite neu laden und erneut versuchen.');
                }
                const reader = response.body.getReader(), decoder = new TextDecoder(); let buffer = '', prepared = null, protocolNoise = [];
                while (true) {
                    const chunk = await reader.read(); if (chunk.done) break;
                    buffer += decoder.decode(chunk.value, {stream:true}); const lines = buffer.split('\n'); buffer = lines.pop();
                    for (const line of lines) {
                        const payload = line.trim().replace(/^\uFEFF/, '');
                        if (!payload) continue;
                        let event;
                        try { event = JSON.parse(payload); } catch (parseError) {
                            // PHP notices or a proxy banner before the NDJSON stream must not
                            // discard a valid prepared update that follows.
                            protocolNoise.push(payload.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim());
                            continue;
                        }
                        if (event.message) { if (overlaySubtext) overlaySubtext.textContent = event.message; const item=document.createElement('li');item.textContent=event.message;prepareLogItems.appendChild(item); }
                        if (Array.isArray(event.items)) event.items.forEach(function(label) { const item=document.createElement('li'); item.textContent=label; item.className='is-ready'; prepareLogItems.appendChild(item); });
                        if (Number.isFinite(Number(event.percent))) { progressBar.style.width=Number(event.percent)+'%';progressText.textContent=Number(event.percent)+' %'; }
                        if (event.state === 'error') throw new Error(event.message || 'Das Store-Update konnte nicht vorbereitet werden.');
                        if (event.ok) { prepared = event; if (event.maintenance_enabled === true) updaterMaintenanceIndicator(true); }
                    }
                }
                if (buffer.trim()) { let event; try { event = JSON.parse(buffer); } catch (parseError) { throw new Error('Die Update-Vorbereitung lieferte eine unvollständige Serverantwort. Bitte erneut versuchen.'); } if (event.state === 'error') throw new Error(event.message || 'Das Store-Update konnte nicht vorbereitet werden.'); if (event.ok) { prepared = event; if (event.maintenance_enabled === true) updaterMaintenanceIndicator(true); } }
                if (!prepared || !prepared.update_token) {
                    const detail = protocolNoise.filter(Boolean).join(' ').slice(0, 300);
                    throw new Error(detail ? 'Die Update-Vorbereitung wurde nicht bestätigt: ' + detail : 'Das Store-Update wurde nicht bestätigt.');
                }
                startUpdateExecution(prepared);
            } catch (error) {
                hideLoadingSpinner();
                // Alte Delta-Archive aus dem Store enthielten noch nicht alle
                // Dateien, die der WBCE-Installer zwingend benötigt. Die
                // Vollversion ist derselbe freigegebene Zielstand und kann
                // deshalb ohne eine zweite Benutzeraktion übernommen werden.
                const message = error && error.message ? String(error.message) : '';
                const retryAsFull = document.getElementById('form_force_full').value !== '1'
                    && message.indexOf('Diff-Update enthält kein WBCE Update-Script') !== -1;
                if (retryAsFull) {
                    if (overlayText) overlayText.textContent = 'Älteres Diff-Update erkannt – Vollversion wird geladen …';
                    if (overlaySubtext) overlaySubtext.textContent = 'Die Vollversion wird automatisch vorbereitet.';
                    await prepareUpdate(
                        document.getElementById('form_download_url').value,
                        document.getElementById('form_target_version').value,
                        'patch',
                        document.getElementById('form_checksum').value,
                        document.getElementById('form_store_offer').value,
                        true,
                        true
                    );
                    return;
                }
                updaterToast(message || 'Das Store-Update konnte nicht vorbereitet werden.', true);
            }
        }

        function updaterExecutionProgress(percent, message, updateProcessText) {
            const progress = document.getElementById('overlay-upload-progress');
            const bar = progress.querySelector('span'); const label = progress.querySelector('strong');
            const value = Math.max(0, Math.min(100, Number(percent) || 0));
            progress.hidden = false; bar.style.width = value + '%'; label.textContent = value + ' %';
            // Detailtexte werden in updaterExecutionStep()/updaterExecutionFile()
            // gerendert, damit der Kopfbereich ruhig bleibt.
        }
        function updaterExecutionStep(message) {
            if (!message) return;
            message = String(message).replace(/^Schritt\s+\d+\s*:\s*/i, '');
            const steps = document.getElementById('updater-prepare-log-items');
            steps.querySelectorAll('.is-current').forEach(function(row) { row.classList.remove('is-current'); row.classList.add('is-complete'); });
            const row = document.createElement('li'); row.className = 'updater-task is-current'; row.textContent = message; steps.appendChild(row);
            steps.scrollTop = steps.scrollHeight;
        }
        function updaterExecutionFile(message) {
            if (!message) return;
            const files = document.getElementById('updater-active-change-items');
            const row = document.createElement('li'); row.className = 'updater-file'; row.textContent = message; files.appendChild(row); files.scrollTop = files.scrollHeight;
        }
        async function startUpdateExecution(prepared) {
            const progress = document.getElementById('overlay-upload-progress');
            const bar = progress.querySelector('span'); const label = progress.querySelector('strong');
            const overlayText = document.querySelector('#loading-overlay .loading-text');
            const overlaySubtext = document.querySelector('#loading-overlay .loading-subtext');
            const prepareLog = document.getElementById('updater-prepare-log');
            const prepareLogTitle = prepareLog.querySelector('strong');
            const stepItems = document.getElementById('updater-prepare-log-items');
            const fileBox = document.getElementById('updater-active-changes');
            const fileItems = document.getElementById('updater-active-change-items');
            if (overlayText) overlayText.textContent = 'Update-Prozess wird ausgeführt …';
            // Prozessmeldungen erscheinen ausschließlich im Block „Aktuelle Aufgabe“.
            if (overlaySubtext) { overlaySubtext.textContent = ''; overlaySubtext.hidden = true; }
            progress.hidden = false; bar.style.width = '0%'; label.textContent = '0 %';
            stepItems.replaceChildren(); fileItems.replaceChildren();
            const backupResult = document.getElementById('updater-backup-result');
            if (backupResult) backupResult.hidden = true;
            prepareLog.hidden = false; prepareLogTitle.textContent = 'Aktuelle Aufgabe'; fileBox.hidden = false;
            updaterExecutionStep('Update-Prozess wird vorbereitet');
            const form = new FormData();
            [['update_token', prepared.update_token], ['version', prepared.version], ['embedded', '1']].forEach(function(pair) { form.append(pair[0], pair[1]); });
            let latestEventId = 0, recoveryToken = '', executionError = '';
            const applyProgressEvent = function(event) {
                if (!event || (event.id && Number(event.id) <= latestEventId)) return;
                if (event.id) latestEventId = Number(event.id);
                if (event.type === 'wbce-updater-complete') { recoveryToken = event.recovery_token || ''; return; }
                if (event.type === 'wbce-updater-error') { executionError = event.message || 'Die Dateiausführung wurde vom Server abgebrochen.'; return; }
                if (event.type !== 'wbce-updater-progress') return;
                const percent = Number(event.percent) || 0;
                updaterExecutionProgress(percent, event.message || 'Update wird ausgeführt …', event.kind !== 'file');
                if (event.kind === 'file') updaterExecutionFile(event.message || 'Datei wird aktualisiert');
                else if (event.kind === 'step') updaterExecutionStep(event.message || 'Update-Schritt');
            };
            const pollProgress = async function() {
                try {
                    const request = new FormData(); request.append('update_token', prepared.update_token); request.append('after', String(latestEventId));
                    const reply = await fetch('<?php echo WB_URL; ?>/modules/updater/progress.php', {method:'POST', body:request, credentials:'same-origin', cache:'no-store'});
                    const data = await reply.json(); if (data && Array.isArray(data.events)) data.events.forEach(applyProgressEvent);
                } catch (ignore) { /* The stream remains the secondary display path. */ }
            };
            const progressTimer = window.setInterval(pollProgress, 450); pollProgress();
            try {
                const response = await fetch('<?php echo WB_URL; ?>/modules/updater/execute_update.php', {method:'POST', body:form, credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest'}});
                if (!response.ok || !response.body) {
                    const detail = response.body ? (await response.text()).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() : '';
                    throw new Error(detail ? 'Die Update-Ausführung wurde abgewiesen: ' + detail.slice(0, 300) : 'Die Update-Ausführung konnte nicht gestartet werden.');
                }
                const reader = response.body.getReader(), decoder = new TextDecoder(); let stream = '', tail = '', lastPercent = 0;
                const consumeEvent = function(encoded) {
                    try { applyProgressEvent(JSON.parse(atob(encoded))); } catch (ignore) { /* Incomplete markers stay in the buffer. */ }
                };
                const consume = function() {
                    let match, consumedUntil = 0;
                    const marker = /<!--WBCE_UPDATER_EVENT\s+([A-Za-z0-9+/=]+)-->/g;
                    while ((match = marker.exec(stream)) !== null) {
                        consumeEvent(match[1]);
                        consumedUntil = marker.lastIndex;
                    }
                    const possiblePartial = stream.lastIndexOf('<!--WBCE_UPDATER_EVENT');
                    const preserveFrom = possiblePartial >= consumedUntil ? possiblePartial : stream.length;
                    tail = (tail + stream.slice(0, preserveFrom)).slice(-32768);
                    stream = preserveFrom < stream.length ? stream.slice(preserveFrom) : '';
                };
                while (true) {
                    const chunk = await reader.read(); if (chunk.done) break;
                    stream += decoder.decode(chunk.value, {stream:true});
                    consume();
                }
                stream += decoder.decode(); consume();
                tail = (tail + stream).slice(-32768);
                if (executionError) throw new Error(executionError);
                if (!/^[a-f0-9]{64}$/i.test(recoveryToken)) {
                    const lastTask = stepItems.lastElementChild ? stepItems.lastElementChild.textContent.trim() : 'keine Rückmeldung';
                    const serverText = tail.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                    const excerpt = serverText ? serverText.slice(-500) : 'keine lesbare Serverausgabe';
                    throw new Error('Die Update-Ausführung wurde nicht vom Server abgeschlossen. Letzte Aufgabe: ' + lastTask + '. HTTP-Status: ' + response.status + '. Serverausgabe: ' + excerpt);
                }
                updaterExecutionProgress(100, 'Datenbank-Update wird abgeschlossen …'); updaterExecutionStep('CMS-Datenbank wird aktualisiert');
                const finalize = new FormData(); finalize.append('backup_confirmed', 'confirmed'); finalize.append('updater_session_recovery', recoveryToken);
                const finalResponse = await fetch('<?php echo WB_URL; ?>/install/update.php', {method:'POST', body:finalize, credentials:'same-origin'});
                if (!finalResponse.ok) throw new Error('Der CMS-Update-Abschluss ist fehlgeschlagen.');
                // Read the actual installer rows while the database update is
                // running, rather than discarding the asynchronous response.
                if (finalResponse.body) {
                    const dbReader = finalResponse.body.getReader();
                    const dbDecoder = new TextDecoder(); let dbBuffer = ''; const dbSeen = new Set();
                    const showDatabaseDetail = function(fragment) {
                        // Older CMS packages can still render the release
                        // notes in install/update.php. They belong to the
                        // dashboard after completion, never in this overlay.
                        fragment = String(fragment).replace(/<details\b[^>]*\bupdate-changes\b[^>]*>[\s\S]*?<\/details\s*>/i, '');
                        const node = document.createElement('div'); node.innerHTML = fragment;
                        const text = (node.textContent || '').replace(/\s+/g, ' ').trim();
                        if (!text || text.length < 3 || dbSeen.has(text)) return;
                        dbSeen.add(text); if (dbSeen.size > 80) return;
                        updaterExecutionStep('Datenbank: ' + text.slice(0, 240));
                    };
                    while (true) {
                        const chunk = await dbReader.read(); if (chunk.done) break;
                        dbBuffer += dbDecoder.decode(chunk.value, {stream:true});
                        let end;
                        while ((end = dbBuffer.search(/<\/tr\s*>/i)) >= 0) {
                            const row = dbBuffer.slice(0, end + 5); dbBuffer = dbBuffer.slice(end + 5);
                            showDatabaseDetail(row);
                        }
                    }
                    dbBuffer += dbDecoder.decode(); if (dbBuffer) showDatabaseDetail(dbBuffer);
                }
                // The installer is finished. Disable only the maintenance mode
                // that this updater enabled, even when the updated CMS uses a
                // different session implementation.
                const completion = new FormData();
                completion.append('updater_session_recovery', recoveryToken);
                completion.append('expected_version', prepared.version || '');
                const completionResponse = await fetch('<?php echo WB_URL; ?>/modules/updater/complete_update.php', {
                    method: 'POST', body: completion, credentials: 'same-origin', cache: 'no-store'
                });
                let completionData = null;
                try { completionData = await completionResponse.json(); } catch (ignore) { }
                if (!completionResponse.ok || !completionData || !completionData.ok) {
                    throw new Error((completionData && completionData.message) || 'Der Wartungsmodus konnte nach dem Update nicht deaktiviert werden.');
                }
                if (completionData.disabled) updaterMaintenanceIndicator(false);
                window.location.replace('<?php echo ADMIN_URL; ?>/start/index.php?update=completed');
            } catch (error) { hideLoadingSpinner(); updaterToast(error.message || 'Die Update-Ausführung ist fehlgeschlagen.', true); }
            finally { window.clearInterval(progressTimer); }
        }

        <?php if (!empty($updater_custom_source_url)): ?>
        /**
         * Download from custom (non-official) update source
         */
        async function prepareCustomSourceUpdate() {
            const customUrl = <?php echo json_encode($updater_custom_source_url); ?>;
            const warningMsg = <?php echo json_encode(sprintf($LANG['CUSTOM_SOURCE_WARNING'], $updater_custom_source_url)); ?>;
            const confirmMsg = <?php echo json_encode($LANG['CUSTOM_SOURCE_CONFIRM']); ?>;

            if (!(await updaterConfirm(warningMsg))) return;
            if (!(await updaterConfirm(confirmMsg))) return;

            document.getElementById('form_download_url').value = customUrl;
            document.getElementById('form_target_version').value = '';
            document.getElementById('form_checksum').value = '';
            document.getElementById('form_backup_confirmed').value =
                document.getElementById('backup_confirmed').checked ? '1' : '0';
            document.getElementById('form_enable_maintenance').value =
                document.getElementById('enable_maintenance').checked ? '1' : '0';
            document.getElementById('form_force_full').value = '0';

            showLoadingSpinner();
            HTMLFormElement.prototype.submit.call(document.getElementById('update-form'));
        }
        <?php endif; ?>

        /**
         * Format date string
         */
        function formatDate(value) {
            // Store catalogues use Unix seconds; Date() expects milliseconds.
            const numeric = typeof value === 'number' || /^\d+$/.test(String(value || '')) ? Number(value) : NaN;
            const date = Number.isFinite(numeric)
                ? new Date(numeric > 0 && numeric < 100000000000 ? numeric * 1000 : numeric)
                : new Date(value || '');
            if (Number.isNaN(date.getTime())) return '–';
            return date.toLocaleString('<?php echo LANGUAGE; ?>', {
                year: 'numeric', month: 'long', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        }

        /**
         * Escape HTML
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function runUpdaterBackup() {
            const overlayText=document.querySelector('#loading-overlay .loading-text'), subtext=document.querySelector('#loading-overlay .loading-subtext'), progress=document.getElementById('overlay-upload-progress'), bar=progress.querySelector('span'), label=progress.querySelector('strong'), resultBox=document.getElementById('updater-backup-result'), resultList=document.getElementById('updater-backup-result-items');
            let waitingForDownload=false;
            showLoadingSpinner(); if(resultBox){resultBox.hidden=false;resultList.replaceChildren();} progress.hidden=false; bar.style.width='0%'; label.textContent='0 %'; if(overlayText)overlayText.textContent='Backup wird erstellt …';
            try { const data=new FormData(); const ftan=document.querySelector('#upload-form input[name]'); if(ftan)data.append(ftan.name,ftan.value); data.append('enable_maintenance',document.getElementById('enable_maintenance').checked?'1':'0'); const response=await fetch('<?php echo WB_URL; ?>/modules/updater/create_backup.php',{method:'POST',body:data,credentials:'same-origin'}); if(!response.ok||!response.body)throw new Error('Backup konnte nicht gestartet werden.'); const reader=response.body.getReader(),decoder=new TextDecoder();let buffer='',done=false;while(true){const chunk=await reader.read();if(chunk.done)break;buffer+=decoder.decode(chunk.value,{stream:true});const lines=buffer.split('\n');buffer=lines.pop();for(const line of lines){if(!line.trim())continue;const event=JSON.parse(line);const percent=Number(event.percent)||0;bar.style.width=percent+'%';label.textContent=percent+' %';if(subtext)subtext.textContent=event.message||'';if(event.ok===false)throw new Error(event.message||'Backup fehlgeschlagen.');if(event.ok===true){done=true;if(resultList)(event.message||'Backup erfolgreich erstellt.').split(';').map(function(item){return item.trim();}).filter(Boolean).forEach(function(item){const row=document.createElement('li');row.textContent=item;resultList.appendChild(row);});}}}if(!done)throw new Error('Backup wurde nicht bestätigt.');document.getElementById('backup_confirmed').checked=true; enableUpdateButton();
                const status=await fetch('<?php echo WB_URL; ?>/modules/updater/backup_status.php',{credentials:'same-origin'}).then(r=>r.json());
                if(!status.ok)throw new Error('Die Backup-Datei konnte nicht für den Download vorbereitet werden.');
                waitingForDownload=true; if(overlayText)overlayText.textContent='Backup wird heruntergeladen …'; if(subtext)subtext.textContent='Der Download wird vorbereitet. Bitte dieses Fenster geöffnet lassen.'; progress.hidden=false; bar.style.width='100%'; label.textContent='100 %';
                const frame=document.createElement('iframe'); frame.hidden=true; frame.setAttribute('aria-hidden','true'); frame.src=status.url; document.body.appendChild(frame); const skip=document.getElementById('continue-without-backup');if(skip)skip.remove();backupDownloadStarted(status.url,frame); updaterToast('Backup erfolgreich erstellt. Der Download wurde gestartet.',false);
            }catch(error){updaterToast(error.message||'Backup fehlgeschlagen.',true);}finally{if(!waitingForDownload){progress.hidden=true;hideLoadingSpinner();}}
        }
        function backupDownloadStarted(url,frame) { const token=new URL(url,window.location.href).searchParams.get('token'); if(!token)return; const timer=window.setInterval(async function(){try{const state=await fetch('<?php echo WB_URL; ?>/modules/updater/backup_download_status.php?token='+encodeURIComponent(token),{credentials:'same-origin'}).then(r=>r.json());if(state.complete||state.aborted){window.clearInterval(timer);if(frame)frame.remove();const progress=document.getElementById('overlay-upload-progress');if(progress)progress.hidden=true;hideLoadingSpinner();if(state.complete){unlockUpdateSources();}else{updaterToast('Der Backup-Download wurde abgebrochen.',true);}}}catch(e){}},900); }
        function unlockUpdateSources() {
            const section=document.getElementById('update-source-section');
            if(!section)return;
            section.hidden=false;
            loadAvailableUpdates().then(function() {
                // The Store card exists now, so the automatic countdown can
                // start reliably after the completed backup download.
                startAvailableStoreUpdate();
            });
            section.scrollIntoView({behavior:'smooth',block:'start'});
        }
        function confirmExistingBackup() { document.getElementById('backup_confirmed').checked=true; enableUpdateButton(); unlockUpdateSources(); }
        async function continueWithoutBackup() { if(await updaterConfirm('Ohne aktuelles Backup fortfahren?')) { document.getElementById('backup_confirmed').checked=true; enableUpdateButton(); unlockUpdateSources(); } }
        function selectManualUpload() { const section=document.getElementById('manual-upload-section'); if(section){section.hidden=false;section.scrollIntoView({behavior:'smooth',block:'nearest'});} }
        /**
         * Jump to manual upload section
         */
        function jumpToUpload() { selectManualUpload();
            const uploadSection = document.getElementById('manual-upload-section');
            if (uploadSection) {
                uploadSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        /**
         * Enable upload button when file is selected
         */
        function enableUploadButton() {
            const fileInput = document.getElementById('update_files');
            const uploadButton = document.getElementById('upload-button');

            const uploadLabel = document.getElementById('update_files_label');
            const selectedFiles = Array.from(fileInput.files || []);
            if (uploadLabel) {
                uploadLabel.textContent = selectedFiles.length
                    ? selectedFiles.map(function(file) { return file.name; }).join(', ')
                    : <?php echo json_encode($LANG['SELECT_UPDATE_FILES'] ?? 'Update-ZIP und SHA-256-Prüfsumme auswählen'); ?>;
            }
            // Enable upload button if file is selected (backup check happens on submit)
            uploadButton.disabled = selectedFiles.length === 0;
        }

        /**
         * Handle upload form submission
         */
        async function handleUploadSubmit(event) {
            event.preventDefault();
            const backupCheckbox = document.getElementById('backup_confirmed');
            const fileInput = document.getElementById('update_files');
            const uploadButton = document.getElementById('upload-button');

            // Check if backup is confirmed
            if (!backupCheckbox.checked) {
                updaterToast(<?php echo json_encode($LANG['ERROR_BACKUP_NOT_CONFIRMED']); ?>, true);
                return false;
            }

            // Check if file is selected
            if (fileInput.files.length === 0) {
                updaterToast(<?php echo json_encode($LANG['ERROR_NO_FILE_UPLOADED']); ?>, true);
                return false;
            }

            const file = Array.from(fileInput.files).find(function(candidate){ return /\.zip$/i.test(candidate.name); });
            if (!file) { updaterToast(<?php echo json_encode($LANG['ERROR_NO_FILE_UPLOADED']); ?>, true); return false; }
            const shaFile = Array.from(fileInput.files).find(function(candidate){ return /\.(sha256|sha|checksum)$/i.test(candidate.name); });
            const fileSize = file.size;

            if (!/\.zip$/i.test(file.name)) {
                updaterToast(<?php echo json_encode($LANG['ERROR_ZIP_ONLY']); ?>.replace('%s', file.name), true);
                return false;
            }
            if (fileSize > window.wbceUpdaterMaxPackageBytes) {
                updaterToast(<?php echo json_encode($LANG['ERROR_FILE_TOO_LARGE_MB']); ?>.replace('%s', Math.round(window.wbceUpdaterMaxPackageBytes / 1024 / 1024)), true);
                return false;
            }

            // Set hidden form fields from checkboxes and inputs
            document.getElementById('form_backup_confirmed_upload').value =
                backupCheckbox.checked ? '1' : '0';
            document.getElementById('form_enable_maintenance_upload').value =
                document.getElementById('enable_maintenance').checked ? '1' : '0';
            const versionInput = document.getElementById('upload_target_version');
            document.getElementById('form_target_version_upload').value =
                versionInput ? versionInput.value.trim() : '';

            // Update spinner overlay text for upload context
            const overlayText = document.querySelector('#loading-overlay .loading-text');
            const overlaySubtext = document.querySelector('#loading-overlay .loading-subtext');
            if (overlayText)    overlayText.textContent = '⏳ <?php echo $LANG['LOADING_UPLOAD']; ?>...';
            if (overlaySubtext) overlaySubtext.textContent = '<?php echo $LANG['UPLOAD_PLEASE_WAIT']; ?>';

            showLoadingSpinner();
            uploadButton.disabled = true;

                const overlayProgress = document.getElementById('overlay-upload-progress');
                const overlayProgressBar = overlayProgress.querySelector('span');
                const overlayProgressText = overlayProgress.querySelector('strong');
                const prepareLog = document.getElementById('updater-prepare-log');
                const prepareLogItems = document.getElementById('updater-prepare-log-items');
                overlayProgress.hidden = false;
                prepareLog.hidden = false;
                prepareLogItems.replaceChildren();
            overlayProgressBar.style.width = '0%';
            overlayProgressText.textContent = '0 %';

            // Keep every request safely below both PHP request limits. The
            // additional reserve accounts for multipart form-data overhead.
            const configuredLimits = [window.phpUploadMaxBytes, window.phpPostMaxBytes]
                .map(Number).filter(function(limit) { return Number.isFinite(limit) && limit > 0; });
            const requestLimit = configuredLimits.length ? Math.min.apply(Math, configuredLimits) : 2 * 1024 * 1024;
            const chunkSize = Math.min(1024 * 1024, Math.max(64 * 1024, Math.floor(requestLimit * 0.6)));
            const chunkCount = Math.ceil(fileSize / chunkSize);

            async function chunkRequest(action, values, blob) {
                const data = new FormData();
                data.append('action', action);
                data.append('token', window.wbceUpdaterChunkToken);
                Object.keys(values || {}).forEach(function(key) { data.append(key, values[key]); });
                if (blob) data.append('chunk', blob, 'chunk.bin');
                const response = await fetch(window.wbceUpdaterChunkUrl, {
                    method: 'POST', body: data, credentials: 'same-origin',
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });
                const text = await response.text();
                let result;
                try { result = JSON.parse(text); } catch (error) {
                    const detail = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 180);
                    throw new Error('Ungültige Serverantwort' + (detail ? ': ' + detail : ' (HTTP ' + response.status + ')') + '.');
                }
                if (!response.ok || !result.ok) throw new Error(result.message || '<?php echo addslashes($LANG['ERROR_UPLOAD_FAILED']); ?>');
                return result;
            }

            try {
                const initialized = await chunkRequest('init', {
                    name: file.name,
                    size: String(fileSize),
                    chunks: String(chunkCount)
                });
                const uploadId = initialized.upload_id;

                for (let index = 0; index < chunkCount; index++) {
                    const start = index * chunkSize;
                    const end = Math.min(start + chunkSize, fileSize);
                    await chunkRequest('chunk', {
                        upload_id: uploadId,
                        index: String(index)
                    }, file.slice(start, end));
                    const percent = Math.round(((index + 1) / chunkCount) * 100);
                    overlayProgressBar.style.width = percent + '%';
                    overlayProgressText.textContent = percent + ' %';
                    uploadButton.textContent = '⏳ Upload ' + percent + ' %';
                }

                await chunkRequest('complete', { upload_id: uploadId });
                document.getElementById('chunk_upload_id').value = uploadId;
                fileInput.disabled = true;
                uploadButton.textContent = '⏳ <?php echo $LANG['LOADING_UPLOAD']; ?>...';
                if (overlayText) overlayText.textContent = '⏳ <?php echo $LANG['UPLOAD_FILES_PREPARED']; ?>';
                if (overlaySubtext) overlaySubtext.textContent = '<?php echo $LANG['UPDATE_RUNNING_HINT']; ?>';
                const prepareData = new FormData(document.getElementById('upload-form'));
                if (shaFile) prepareData.append('sha_file', shaFile, shaFile.name);
                prepareData.append('stream', '1');
                const preparedResponse = await fetch(document.getElementById('upload-form').action, {
                    method: 'POST', body: prepareData, credentials: 'same-origin',
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });
                if (!preparedResponse.ok || !preparedResponse.body) throw new Error('<?php echo addslashes($LANG['ERROR_UPLOAD_FAILED']); ?>');
                const reader = preparedResponse.body.getReader();
                const decoder = new TextDecoder();
                let pending = '';
                let prepared = null;
                while (true) {
                    const part = await reader.read();
                    if (part.done) break;
                    pending += decoder.decode(part.value, {stream: true});
                    const lines = pending.split('\n'); pending = lines.pop();
                    lines.forEach(function(line) {
                        if (!line.trim()) return;
                        try {
                            const event = JSON.parse(line);
                            if (event.message && overlaySubtext) overlaySubtext.textContent = event.message;
                            if (event.message) {
                                const item = document.createElement('li'); item.textContent = event.message; prepareLogItems.appendChild(item);
                            }
                            if (Array.isArray(event.items)) event.items.forEach(function(label) {
                                const item = document.createElement('li'); item.textContent = label; item.className = 'is-ready'; prepareLogItems.appendChild(item);
                            });
                            if (Number.isFinite(Number(event.percent))) {
                                overlayProgressBar.style.width = Number(event.percent) + '%';
                                overlayProgressText.textContent = Number(event.percent) + ' %';
                            }
                            if (event.state === 'error') throw new Error(event.message || '<?php echo addslashes($LANG['ERROR_UPLOAD_FAILED']); ?>');
                            if (event.ok) { prepared = event; if (event.maintenance_enabled === true) updaterMaintenanceIndicator(true); }
                        } catch (eventError) { if (eventError instanceof Error) throw eventError; }
                    });
                }
                if (pending.trim()) prepared = JSON.parse(pending);
                if (!prepared || !prepared.ok) throw new Error('<?php echo addslashes($LANG['ERROR_UPLOAD_FAILED']); ?>');
                startUpdateExecution(prepared);
            } catch (error) {
                hideLoadingSpinner();
                uploadButton.disabled = false;
                uploadButton.textContent = '📤 <?php echo $LANG['UPLOAD_AND_PREPARE']; ?>';
                updaterToast(error.message || <?php echo json_encode($LANG['ERROR_UPLOAD_FAILED']); ?>, true);
            }
            return false;
        }

        /**
         * Show loading spinner overlay
         */
        function showLoadingSpinner() {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.classList.add('active');
            }
        }

        /**
         * Hide loading spinner overlay
         */
        function hideLoadingSpinner() {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.classList.remove('active');
            }
        }

        function updaterToast(message, error) {
            const region = document.getElementById('updater-toasts');
            if (!region) return;
            const item = document.createElement('div');
            item.className = 'updater-toast' + (error ? ' error' : '');
            item.textContent = message;
            region.appendChild(item);
            window.setTimeout(() => item.remove(), 7000);
        }

        function updaterConfirm(message) {
            const dialog = document.getElementById('updater-confirm');
            if (!dialog || typeof dialog.showModal !== 'function') return Promise.resolve(false);
            dialog.querySelector('[data-confirm-text]').textContent = message;
            dialog.returnValue = '';
            dialog.showModal();
            return new Promise(resolve => dialog.addEventListener('close', () => resolve(dialog.returnValue === 'confirm'), {once: true}));
        }

        window.addEventListener('DOMContentLoaded', function() { document.getElementById('backup_confirmed').addEventListener('change', function(){ if(this.checked) unlockUpdateSources(); }); });
    </script>

    <!-- Loading Spinner Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <div class="loading-text"><?php echo $LANG['LOADING_DOWNLOAD']; ?>...</div>
            <div class="loading-subtext">
                <?php echo $LANG['DOWNLOAD_PLEASE_WAIT']; ?>
            </div>
            <div id="overlay-upload-progress" class="chunk-upload-progress overlay-upload-progress" hidden>
                <div class="chunk-upload-progress-bar"><span></span></div>
                <strong>0 %</strong>
            </div>
            <div id="updater-backup-result" class="updater-prepare-log" hidden><strong>Backup-Ergebnis</strong><ul id="updater-backup-result-items"></ul></div>
            <div id="updater-prepare-log" class="updater-prepare-log" hidden>
                <strong><?php echo htmlspecialchars($LANG['UPLOAD_FILES_PREPARED']); ?></strong>
                <ul id="updater-prepare-log-items"></ul>
            </div>
            <div id="updater-active-changes" class="updater-execution-log updater-active-changes" hidden>
                <strong>Verarbeitete Dateien und Aktionen</strong><ul id="updater-active-change-items"></ul>
            </div>

        </div>
    </div>
    <div id="updater-toasts" class="updater-toasts" aria-live="polite"></div>
    <dialog id="updater-confirm" class="updater-confirm"><form method="dialog"><h3><?php echo htmlspecialchars($LANG['CONFIRM']); ?></h3><p data-confirm-text></p><div class="updater-confirm-actions"><button value="cancel" class="btn-secondary updater-confirm-cancel"><?php echo htmlspecialchars($LANG['CANCEL']); ?></button><button value="confirm" class="btn-primary updater-confirm-accept"><?php echo htmlspecialchars($LANG['CONFIRM']); ?></button></div></form></dialog>
</div> <!-- end wbce-updater-container -->
<?php
$updaterLegacyContent = ob_get_clean();
WbceCompatibleTwigView::display(__DIR__.'/templates', 'tool.twig', array('content'=>$updaterLegacyContent,'module_version'=>$module_version), $updaterLegacyContent);
// Footer wird vom Framework ausgegeben - hier NICHT $admin->print_footer() aufrufen!
