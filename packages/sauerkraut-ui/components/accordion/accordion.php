<?php
$id = $id ?? 'accordion-' . uniqid();
$allowMultiple = $multiple ?? false;
?>
<div class="accordion" id="<?= e($id) ?>" data-multiple="<?= $allowMultiple ? 'true' : 'false' ?>">
    <?= $slot ?>
</div>
