<?php
$classes = ['btn'];
if (!empty($variant)) $classes[] = "btn-{$variant}";
?>
<button class="<?= e(implode(' ', $classes)) ?>" type="<?= e($type ?? 'button') ?>"<?php if (!empty($onclick)): ?> onclick="<?= e($onclick) ?>"<?php endif; ?>><?= $slot ?></button>
