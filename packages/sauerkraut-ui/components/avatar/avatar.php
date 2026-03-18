<?php
$name = $name ?? '';
$src = $src ?? '';
$size = $size ?? '';
$classes = ['avatar'];
if ($size) {
    $classes[] = "avatar-{$size}";
}
$initials = $name ? strtoupper(mb_substr($name, 0, 2)) : '?';
?>
<?php if ($src): ?>
    <img class="<?= e(implode(' ', $classes)) ?>" src="<?= e($src) ?>" alt="<?= e($name) ?>">
<?php else: ?>
    <span class="<?= e(implode(' ', $classes)) ?>" title="<?= e($name) ?>"><?= e($initials) ?></span>
<?php endif; ?>
