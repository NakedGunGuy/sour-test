<?php
$currentPage = $page ?? 1;
$totalPages = $total ?? 1;
$baseUrl = $url ?? '?page=';

if ($totalPages <= 1) {
    return;
}
?>
<nav class="pagination">
    <?php if ($currentPage > 1): ?>
        <a href="<?= e($baseUrl . ($currentPage - 1)) ?>" class="pagination-link">&laquo; Previous</a>
    <?php else: ?>
        <span class="pagination-link pagination-disabled">&laquo; Previous</span>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $currentPage): ?>
            <span class="pagination-link pagination-active"><?= $i ?></span>
        <?php elseif ($i === 1 || $i === $totalPages || abs($i - $currentPage) <= 2): ?>
            <a href="<?= e($baseUrl . $i) ?>" class="pagination-link"><?= $i ?></a>
        <?php elseif (abs($i - $currentPage) === 3): ?>
            <span class="pagination-ellipsis">&hellip;</span>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($currentPage < $totalPages): ?>
        <a href="<?= e($baseUrl . ($currentPage + 1)) ?>" class="pagination-link">Next &raquo;</a>
    <?php else: ?>
        <span class="pagination-link pagination-disabled">Next &raquo;</span>
    <?php endif; ?>
</nav>
