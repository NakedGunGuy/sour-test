<?php
$classes = ['btn', 'btn-icon'];
if (!empty($variant)) $classes[] = "btn-{$variant}";
?>
<button class="<?= e(implode(' ', $classes)) ?>" type="<?= e($type ?? 'button') ?>"><?php if (!empty($icon)): ?><span class="btn-icon-symbol"><?= $icon ?></span><?php endif; ?><?= $slot ?></button>
