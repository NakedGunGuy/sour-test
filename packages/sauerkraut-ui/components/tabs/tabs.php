<?php
$id = $id ?? 'tabs-' . uniqid();
$activeTab = $active ?? 0;
?>
<div class="tabs" id="<?= e($id) ?>" data-active="<?= e((string) $activeTab) ?>">
    <?= $slot ?>
</div>
