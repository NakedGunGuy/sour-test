<?php $type = $type ?? 'success'; ?>
<div class="alert alert-<?= e($type) ?>" role="alert">
    <span class="alert-content"><?= $slot ?></span>
    <?php if (!empty($dismissable)): ?>
        <button class="alert-dismiss" type="button" onclick="dismissAlert(this)">&times;</button>
    <?php endif; ?>
</div>
