<?php
$variant = $variant ?? '';
$classes = ['badge'];
if ($variant) {
    $classes[] = "badge-{$variant}";
}
?>
<span class="<?= e(implode(' ', $classes)) ?>"><?= $slot ?></span>
