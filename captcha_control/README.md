# captcha_control — Captcha Integration Guide

> WBCE CMS 1.7, compatible with 1.6.8 through the Hook Bridge.  
> `call_captcha()` and the global provider API are available on every page load —  
> no `require_once` needed in new modules.

---

## Global provider API for modules

Existing WBCE modules can continue to render the selected CAPTCHA with:

```php
call_captcha('all', '', $sectionId);
```

New modules should use the provider-neutral API. The same unique section ID
must be passed while rendering and validating:

```php
if (wbce_captcha_is_enabled('signup')) {
    wbce_captcha_render('all', '', $sectionId, array('purpose' => 'my_module'));
}

if (!wbce_captcha_verify(null, $sectionId, array('purpose' => 'my_module'))) {
    // Reject the submission and display the form again.
}
```

The API automatically uses the globally selected provider. On WBCE 1.7 and
1.6.8, `wbce_hook_bridge` provides the same calls.

---

## How It Works

The control module uses the installed and selected CAPTCHA provider. Providers
register through the shared hook API, so modules never need provider-specific
code. If no provider module is available, the built-in self-hosted ALTCHA
fallback keeps forms protected without contacting a third party.

`captcha_control/initialize.php` runs on every page load and registers:
- The `Captcha` class (via `WbAuto::AddFile`)
- The `call_captcha()` helper function

Both are available everywhere without any `require_once`.

---

## Quick Start

### 1. Render in the form

```php
// Outputs the selected provider (+ honeypot if ASP is enabled in settings).
// Place it inside your <form> element, before the submit button.
if (Captcha::isEnabled()) {
    call_captcha();
}
```

```html
<form method="post">
    <!-- your fields -->
    <?php if (Captcha::isEnabled()): ?>
        <?php call_captcha(); ?>
    <?php endif; ?>
    <button type="submit">Send</button>
</form>
```

### 2. Verify on POST

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Captcha::isEnabled() && !Captcha::verify($_POST['captcha'] ?? '')) {
        // Captcha failed — show error, do not process form
    }
    // … process form
}
```

That's it. `Captcha::verify()` delegates to the selected provider and also
checks the honeypot (if ASP is enabled) in one call.

---

## Helper Methods — `isEnabled()` / `isAspEnabled()`

Use these instead of reading constants directly. They support an optional  
module-level override that takes precedence over the global setting.

```php
Captcha::isEnabled()        // global ENABLED_CAPTCHA
Captcha::isAspEnabled()     // global ENABLED_ASP
```

### Module-level override

A module with its own captcha setting can pass it as override.  
`null` means "fall back to the global setting":

```php
// Three-state DB column: NULL = use global, 0 = force off, 1 = force on
$local = $settings['use_captcha'];   // int|null from module DB

if (Captcha::isEnabled($local !== null ? (bool)$local : null)) {
    call_captcha();
}
```

| `$override` value | Behaviour                        |
|-------------------|----------------------------------|
| `null`            | Use global `ENABLED_CAPTCHA`     |
| `true`            | Force on — ignores global        |
| `false`           | Force off — ignores global       |

Same pattern applies to `Captcha::isAspEnabled(?bool $override)`.

---

## Full Example — Contact Form

```php
<?php
// view.php (PageType module) or any frontend form handler

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Captcha check first
    if (Captcha::isEnabled() && !Captcha::verify($_POST['captcha'] ?? '')) {
        $error = L_('MESSAGE:MOD_FORM_INCORRECT_CAPTCHA');
    }

    // 2. Your validation
    $message = trim($_POST['message'] ?? '');
    if ($error === '' && $message === '') {
        $error = 'Please enter a message.';
    }

    // 3. Process if valid
    if ($error === '') {
        // … send email, save to DB, etc.
        $success = true;
    }
}
?>

<?php if ($success): ?>
    <p>Thank you!</p>
<?php else: ?>
    <?php if ($error): ?><p class="error"><?= h($error) ?></p><?php endif; ?>
    <form method="post">
        <textarea name="message"></textarea>
        <?php if (Captcha::isEnabled()): ?>
            <?php call_captcha(); ?>
        <?php endif; ?>
        <button type="submit">Send</button>
    </form>
<?php endif; ?>
```

---

## Multiple Captchas on One Page

If a page has more than one form with a captcha, use the `$sec_id` parameter  
to keep the session tokens separate:

```php
// Form 1
call_captcha('all', '', 'contact');

// Form 2
call_captcha('all', '', 'newsletter');
```

Pass the same `$sec_id` to `verify()`:

```php
Captcha::verify($_POST['captcha'] ?? '', 'contact');
Captcha::verify($_POST['captcha'] ?? '', 'newsletter');
```

---

## `call_captcha()` Parameters

```php
call_captcha(
    string  $action        = 'all',    // see table below
    string  $style         = '',       // unused, kept for compatibility
    string  $sec_id        = '',       // suffix for multiple captchas per page
    ?string $type_override = null      // retained for legacy call compatibility
);
```

| `$action` value             | Output                                                           |
|-----------------------------|------------------------------------------------------------------|
| `'all'`                     | Full provider widget (default)                                   |
| `'widget'`                  | Same as `'all'` — no table wrapper                               |
| `'input'`                   | Only the hidden sync `<input>` + JS listener — no visible widget |
| `'text'`                    | Provider-specific explanatory text, when supported               |
| `'image'`, `'image_iframe'` | Treated as `'all'` (legacy action names)                         |

---

## Honeypot (Advanced Spam Protection)

When **ASP** is enabled, `call_captcha()` automatically renders an invisible  
honeypot field alongside the ALTCHA widget. `Captcha::verify()` checks it  
automatically — no extra code needed.

### Separate honeypot placement

If your form layout requires the honeypot at a different position than the  
ALTCHA widget (e.g. at the very top of the form), render them independently:

```php
// Top of form — honeypot, hidden
echo Captcha::isAspEnabled() ? Captcha::renderHoneypot() : '';

// … visible fields …

// Near submit button — ALTCHA widget only (ASP already rendered above)
// Use action 'input' to suppress the automatic honeypot inside call_captcha()
if (Captcha::isEnabled()) {
    ob_start(); call_captcha('all'); $widget = ob_get_clean();
    echo $widget;
}
```

`Captcha::renderHoneypot(string $sec_id = '')` returns the honeypot HTML as  
a string and sets the session timestamp used by the timing check in `verify()`.

---

## Legacy: `require_once` the old shim

Older modules may have this line:

```php
require_once WB_PATH . '/include/captcha/captcha.php';
```

**Do not use this pattern in new modules.** `call_captcha()` and `Captcha` are  
already available without it. The shim file exists only for backward compatibility  
with existing modules and **must not be deleted** — `require_once` on a missing  
file causes a fatal error regardless of whether the function is already defined.

When updating an existing module, simply remove the `require_once` line.  
Nothing else needs to change.

---

## Settings Reference

All settings are managed via **Tools → Captcha Control** and stored in `{TP}settings`.

| Constant           | Type   | Default  | Description                  |
|--------------------|--------|----------|------------------------------|
| `ENABLED_CAPTCHA`  | bool   | `true`   | Master on/off switch         |
| `ENABLED_ASP`      | bool   | `true`   | Honeypot extra layer         |
| `CAPTCHA_TYPE`     | string | `altcha` | Selected installed provider  |

---

*WBCE CMS — https://wbce.org — GNU GPL2*
