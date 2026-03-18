<?php
$variant = $variant ?? 'info';
$classes = ['toast', "toast-{$variant}"];
$duration = $duration ?? 5000;
$id = 'toast-' . uniqid();
?>
<div id="<?= e($id) ?>" class="<?= e(implode(' ', $classes)) ?>" data-duration="<?= e((string) $duration) ?>">
    <div class="toast-content"><?= $slot ?></div>
    <button class="toast-dismiss" onclick="dismissToast('<?= e($id) ?>')" type="button">&times;</button>
</div>
