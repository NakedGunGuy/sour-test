<?php
$index = $index ?? 0;
$label = $label ?? 'Tab';
?>
<div class="tab-panel" data-tab-index="<?= e((string) $index) ?>">
    <button class="tab-button" type="button" onclick="switchTab(this, <?= e((string) $index) ?>)"><?= e($label) ?></button>
    <div class="tab-content">
        <?= $slot ?>
    </div>
</div>
