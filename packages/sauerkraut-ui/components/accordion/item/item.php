<?php
$title = $title ?? '';
$open = $open ?? false;
?>
<div class="accordion-item">
    <button class="accordion-trigger<?= $open ? ' active' : '' ?>" type="button" onclick="toggleAccordion(this)">
        <span><?= e($title) ?></span>
        <span class="accordion-icon">&plus;</span>
    </button>
    <div class="accordion-content<?= $open ? ' active' : '' ?>">
        <div class="accordion-body">
            <?= $slot ?>
        </div>
    </div>
</div>
