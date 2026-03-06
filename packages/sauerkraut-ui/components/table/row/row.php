<?php if (!empty($target)): ?><table><tbody><?php endif; ?>
<tr<?php if (!empty($id)): ?> id="<?= e($id) ?>"<?php endif; ?><?php if (!empty($target)): ?> hx-swap-oob="<?= e($swap ?? 'outerHTML') ?>:<?= e($target) ?>"<?php endif; ?>><?= $slot ?></tr>
<?php if (!empty($target)): ?></tbody></table><?php endif; ?>
