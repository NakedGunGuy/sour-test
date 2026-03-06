<div class="cms-header">
    <h1>Dashboard</h1>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: var(--space-lg);">
    <?php foreach ($tables as $t): ?>
        <?php open('cms:card') ?>
            <div class="card-body">
                <h3 class="card-title"><?= e($t['displayName']) ?></h3>
                <p class="card-description"><?= (int) $t['count'] ?> record<?= $t['count'] != 1 ? 's' : '' ?></p>
                <div class="card-footer" style="display: flex; gap: var(--space-sm);">
                    <?= component('cms:button/a', ['variant' => 'primary', 'href' => '/cms/' . $t['name']], 'View') ?>
                    <?= component('cms:button/a', ['variant' => 'ghost', 'href' => '/cms/' . $t['name'] . '/create'], 'New') ?>
                </div>
            </div>
        <?php close() ?>
    <?php endforeach; ?>
</div>
