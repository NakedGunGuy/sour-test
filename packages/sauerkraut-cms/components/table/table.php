<?php
$classes = ['table'];
if (!empty($class)) $classes[] = $class;
?>
<table<?php if (!empty($id)): ?> id="<?= e($id) ?>"<?php endif; ?> class="<?= e(implode(' ', $classes)) ?>"><?= $slot ?></table>
