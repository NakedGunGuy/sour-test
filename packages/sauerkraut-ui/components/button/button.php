<?php
$classes = ['btn'];
if (!empty($variant)) $classes[] = "btn-{$variant}";
?>
<button class="<?= e(implode(' ', $classes)) ?>" type="<?= e($type ?? 'button') ?>"><?= $slot ?></button>
