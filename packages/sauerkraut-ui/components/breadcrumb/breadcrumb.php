<?php
/** @var array $items Array of ['label' => string, 'url' => string|null] */
$items = $items ?? [];
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
    <ol class="breadcrumb-list">
        <?php foreach ($items as $index => $item): ?>
            <?php $isLast = $index === count($items) - 1; ?>
            <li class="breadcrumb-item<?= $isLast ? ' breadcrumb-active' : '' ?>">
                <?php if (!$isLast && !empty($item['url'])): ?>
                    <a href="<?= e($item['url']) ?>" class="breadcrumb-link"><?= e($item['label']) ?></a>
                <?php else: ?>
                    <span><?= e($item['label']) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
