<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=2">
    <title><?=htmlspecialchars(isset($ELFINDER['TITLE']) ? $ELFINDER['TITLE'] : 'Media management', ENT_QUOTES, 'UTF-8')?></title>
    <style>body { margin: 0; }</style>
    <link rel="stylesheet" type="text/css" media="screen" href="../../modules/elfinder/ef/themes/material/css/theme-light.min.css">
    <link rel="stylesheet" type="text/css" media="screen" href="../../modules/elfinder/templates/admin-theme.css">
    <script data-main="../../modules/elfinder/ef/main.wbce.js" src="../../modules/elfinder/ef/js/require.min.js"></script>
    <script>
        // The file manager lives in an iframe; copy only the public theme tokens
        // from the parent document so no module-specific design is required.
        try {
            var wbceParentStyle = window.parent !== window ? window.parent.getComputedStyle(window.parent.document.documentElement) : null;
            ['--wbce-card-bg', '--wbce-card-background', '--wbce-input-bg', '--wbce-input-background', '--wbce-text', '--wbce-text-color', '--wbce-border', '--wbce-border-color', '--wbce-primary', '--wbce-primary-color', '--color-background', '--color-surface', '--color-text', '--color-border', '--color-primary'].forEach(function (name) {
                var value = wbceParentStyle ? wbceParentStyle.getPropertyValue(name).trim() : '';
                if (value) document.documentElement.style.setProperty(name, value);
            });
        } catch (ignore) {}
        define('elFinderConfig', {
            defaultOpts: {
                url: '../../modules/elfinder/ef/php/connector.wbce.php',
                height: Math.max(420, $(window).height() - 250)
            },
            managers: {
                'elfinder': {}
            }
        });
    </script>
</head>
<body class="wbce-elfinder">
    <div id="elfinder"></div>
</body>
</html>
