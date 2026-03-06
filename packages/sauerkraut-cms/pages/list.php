<div class="cms-header">
    <h1><?= e($tableConfig->displayName()) ?></h1>
    <?= component('cms:button/a', ['variant' => 'primary', 'href' => '/cms/' . $table . '/create'], 'New Record') ?>
</div>

<p style="color: var(--text-2); margin-bottom: var(--space-md); font-size: var(--text-sm);">
    <?= (int) $total ?> record<?= $total != 1 ? 's' : '' ?>
</p>

<?php open('cms:table') ?>
    <?php open('cms:table/head') ?>
        <?php foreach ($columns as $col): ?>
            <th><?= e(ucfirst(str_replace('_', ' ', $col))) ?></th>
        <?php endforeach; ?>
        <th>Actions</th>
    <?php close() ?>
    <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($columns as $col): ?>
                    <td>
                        <?php if (isset($mtoMap[$col]) && isset($row[$col])): ?>
                            <?php
                                $label = $mtoMap[$col]['labels'][$row[$col]] ?? $row[$col];
                                $refTable = $mtoMap[$col]['referencesTable'];
                            ?>
                            <a href="/cms/<?= e($refTable) ?>/<?= e((string) $row[$col]) ?>"><?= e((string) $label) ?></a>
                        <?php elseif ($col === $pkName): ?>
                            <a href="/cms/<?= e($table) ?>/<?= e((string) $row[$col]) ?>"><?= e((string) ($row[$col] ?? '')) ?></a>
                        <?php else: ?>
                            <?php
                                $val = (string) ($row[$col] ?? '');
                                echo e(mb_strlen($val) > 80 ? mb_substr($val, 0, 80) . '...' : $val);
                            ?>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
                <td>
                    <a href="/cms/<?= e($table) ?>/<?= e((string) $row[$pkName]) ?>">Edit</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
            <tr>
                <td colspan="<?= count($columns) + 1 ?>" style="text-align: center; color: var(--text-2); padding: var(--space-lg);">
                    No records found.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
<?php close() ?>

<?php if ($totalPages > 1): ?>
    <div class="cms-pagination">
        <?php if ($page > 1): ?>
            <a href="/cms/<?= e($table) ?>?page=<?= $page - 1 ?>">&laquo; Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $page): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="/cms/<?= e($table) ?>?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="/cms/<?= e($table) ?>?page=<?= $page + 1 ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
