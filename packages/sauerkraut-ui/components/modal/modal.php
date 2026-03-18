<?php
$id = $id ?? 'modal';
$size = $size ?? '';
$classes = ['modal-overlay'];
?>
<div id="<?= e($id) ?>" class="<?= e(implode(' ', $classes)) ?>" onclick="closeModalOnOverlay(event)">
    <div class="modal<?= $size ? ' modal-' . e($size) : '' ?>">
        <?php if (!empty($title)): ?>
            <div class="modal-header">
                <h3 class="modal-title"><?= e($title) ?></h3>
                <button class="modal-close" onclick="closeModal('<?= e($id) ?>')" type="button">&times;</button>
            </div>
        <?php endif; ?>
        <div class="modal-body">
            <?= $slot ?>
        </div>
        <?php if (!empty($footer)): ?>
            <div class="modal-footer"><?= $footer ?></div>
        <?php endif; ?>
    </div>
</div>
