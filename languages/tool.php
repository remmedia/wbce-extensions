<?php
defined('WB_PATH') && isset($admin) or die('Access denied');
require_once __DIR__ . '/LanguageRepository.php';
$repository = new WbceLanguageRepository();
$cmsLanguage = defined('LANGUAGE') ? strtoupper((string)LANGUAGE) : 'EN';

function languages_json($payload, int $status = 200): void { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($payload, JSON_UNESCAPED_UNICODE); exit; }
function languages_value($value): string { return is_scalar($value) ? trim((string)$value) : ''; }

$api = languages_value($_GET['languages_api'] ?? '');
if ($api !== '') {
    try {
        if ($api === 'files') {
            $language = strtoupper(languages_value($_GET['language'] ?? ''));
            if (!preg_match('/^[A-Z]{2,5}$/', $language)) throw new RuntimeException('Ungültige Sprache.');
            languages_json(array('ok'=>true, 'files'=>$repository->files($language)));
        }
        if ($api === 'document') {
            $id = languages_value($_GET['file'] ?? '');
            $language = strtoupper(languages_value($_GET['language'] ?? ''));
            languages_json(array('ok'=>true, 'document'=>$repository->document($id, $language, $cmsLanguage)));
        }
        if ($api === 'export') {
            $language = strtoupper(languages_value($_GET['language'] ?? ''));
            $export = $repository->exportLanguage($language);
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="wbce-language-' . $language . '.json"');
            echo json_encode($export, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }
        if ($api === 'save') {
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !$admin->checkFTAN()) throw new RuntimeException('Die Sicherheitsprüfung ist fehlgeschlagen. Bitte die Seite neu laden.');
            $repository->save(languages_value($_POST['file'] ?? ''), languages_value($_POST['key'] ?? ''), (string)($_POST['value'] ?? ''));
            languages_json(array('ok'=>true, 'message'=>'Übersetzung gespeichert.'));
        }
        throw new RuntimeException('Unbekannte Aktion.');
    } catch (Throwable $error) { languages_json(array('ok'=>false, 'message'=>$error->getMessage()), 400); }
}
$languages = $repository->languages();
$selectedLanguage = strtoupper(languages_value($_GET['languages_language'] ?? ''));
if (!isset($languages[$selectedLanguage])) $selectedLanguage = '';
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars(WB_URL . '/modules/languages/assets/languages.css?v=1.2.2', ENT_QUOTES, 'UTF-8'); ?>">
<section class="languages-tool" data-api="<?php echo htmlspecialchars(WB_URL . '/modules/languages/api.php', ENT_QUOTES, 'UTF-8'); ?>"<?php echo $selectedLanguage !== '' ? ' data-editor-language="' . htmlspecialchars($selectedLanguage, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
  <header class="languages-tool__hero"><div><h2>Sprachen</h2><p>Übersetzungen aus CMS und Modulen bearbeiten.</p></div></header>
  <form class="languages-tool__token" hidden><?php echo $admin->getFTAN(); ?></form>
  <?php if ($selectedLanguage === '') { ?>
  <section class="languages-card languages-overview"><h3>Verfügbare Sprachen</h3><div class="languages-overview__list"><?php foreach ($languages as $code => $name) { $base = ADMIN_URL . '/admintools/tool.php?tool=languages&languages_view=editor&languages_language=' . rawurlencode($code); ?><article><div><strong><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?></small></div><div class="languages-overview__actions"><a class="languages-button languages-button--secondary" href="<?php echo htmlspecialchars(WB_URL . '/modules/languages/api.php?languages_api=export&language=' . rawurlencode($code), ENT_QUOTES, 'UTF-8'); ?>">Herunterladen</a><a class="languages-button" href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>">Bearbeiten</a></div></article><?php } ?></div></section>
  <?php } else { ?>
  <p class="languages-back"><a href="<?php echo htmlspecialchars(ADMIN_URL . '/admintools/tool.php?tool=languages', ENT_QUOTES, 'UTF-8'); ?>">← Alle Sprachen</a></p>
  <section class="languages-card languages-editor">
    <div class="languages-editor__head"><label for="languages-file">Sprachdatei</label><select id="languages-file" disabled><option>Bitte zuerst eine Sprache auswählen</option></select></div>
    <div class="languages-editor__state" aria-live="polite">Bitte Sprache und Sprachdatei auswählen.</div>
    <div class="languages-editor__rows"></div>
  </section>
  <?php } ?>
  <div class="languages-toasts" aria-live="polite"></div>
</section>
<?php if ($selectedLanguage !== '') { ?><script src="<?php echo htmlspecialchars(WB_URL . '/modules/languages/assets/languages.js?v=1.1.5', ENT_QUOTES, 'UTF-8'); ?>"></script><?php } ?>
