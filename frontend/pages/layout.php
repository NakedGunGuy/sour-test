<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        let t = localStorage.getItem('theme')
            || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.dataset.theme = t;
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Sauerkraut') ?></title>

    <style>
        <?= theme_css('layers.css') ?>
        <?= theme_css('reset.css') ?>
        <?= theme_css('tokens.css') ?>
        <?= \Sauerkraut\View\Component::inlineStyles() ?>
    </style>
    <?= \Sauerkraut\View\Component::linkTags() ?>
    <?= \Sauerkraut\View\Component::scriptTags() ?>
    <?php $inlineJs = \Sauerkraut\View\Component::inlineScripts(); if ($inlineJs): ?>
    <script><?= $inlineJs ?></script>
    <?php endif; ?>
    <script src="https://unpkg.com/htmx.org@2.0.4" defer></script>
    <script src="https://unpkg.com/htmx-ext-head-support@2.0.3" defer></script>
</head>
<body hx-ext="head-support">
    <?= $content ?>
</body>
</html>
