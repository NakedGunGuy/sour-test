<?php
$value = $value ?? 0;
$max = $max ?? 100;
$variant = $variant ?? '';
$percentage = $max > 0 ? round(($value / $max) * 100) : 0;
$showLabel = $label ?? false;
$classes = ['progress-bar'];
if ($variant) {
    $classes[] = "progress-bar-{$variant}";
}
?>
<div class="progress" role="progressbar" aria-valuenow="<?= (int) $value ?>" aria-valuemin="0" aria-valuemax="<?= (int) $max ?>">
    <div class="<?= e(implode(' ', $classes)) ?>" style="width: <?= (int) $percentage ?>%">
        <?php if ($showLabel): ?>
            <span class="progress-label"><?= (int) $percentage ?>%</span>
        <?php endif; ?>
    </div>
</div>
