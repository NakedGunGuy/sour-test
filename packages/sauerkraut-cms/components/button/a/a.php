<?php
$classes = ['btn'];
if (!empty($variant)) $classes[] = "btn-{$variant}";
?>
<a class="<?= e(implode(' ', $classes)) ?>" href="<?= e($href ?? '#') ?>"><?= $slot ?></a>
