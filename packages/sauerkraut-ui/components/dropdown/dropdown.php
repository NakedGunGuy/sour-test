<?php
$id = $id ?? 'dropdown-' . uniqid();
$align = $align ?? 'left';
?>
<div class="dropdown" id="<?= e($id) ?>">
    <button class="dropdown-trigger" type="button" onclick="toggleDropdown('<?= e($id) ?>')">
        <?= $label ?? 'Menu' ?>
    </button>
    <div class="dropdown-menu dropdown-<?= e($align) ?>">
        <?= $slot ?>
    </div>
</div>
