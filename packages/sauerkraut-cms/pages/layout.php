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
    <title><?= e($title ?? 'CMS') ?> — Sauerkraut CMS</title>

    <style>
        <?= theme_css('layers.css') ?>
        <?= theme_css('reset.css') ?>
        <?= theme_css('tokens.css') ?>
        <?= cms_css() ?>
        <?= \Sauerkraut\View\Component::inlineStyles() ?>
    </style>
    <?= \Sauerkraut\View\Component::linkTags() ?>
    <?= \Sauerkraut\View\Component::scriptTags() ?>
    <?php $inlineJs = \Sauerkraut\View\Component::inlineScripts(); if ($inlineJs): ?>
    <script><?= $inlineJs ?></script>
    <?php endif; ?>
</head>
<body>
    <div class="cms-layout">
        <aside class="cms-sidebar">
            <a class="cms-sidebar-brand" href="/cms">CMS</a>
            <nav>
                <ul class="cms-nav-list">
                    <?php foreach ($allTables ?? [] as $t): ?>
                        <li>
                            <a class="cms-nav-link<?= ($table ?? '') === $t['name'] ? ' active' : '' ?>"
                               href="/cms/<?= e($t['name']) ?>">
                                <?= e($t['displayName']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <div style="margin-top: auto; padding-top: var(--space-lg); border-top: 1px solid var(--border);">
                <a href="/" style="color: var(--text-2); font-size: var(--text-sm); text-decoration: none;">&larr; Back to site</a>
            </div>
        </aside>
        <main class="cms-main">
            <?= $content ?>
        </main>
    </div>
</body>
</html>
