<div class="cms-header">
    <h1><?= e($title) ?></h1>
    <?= component('cms:button/a', ['href' => '/cms/' . $table], '&larr; Back to list') ?>
</div>

<form method="POST" action="/cms/<?= e($table) ?><?= $isEdit ? '/' . e((string) $id) : '' ?>" style="max-width: 640px;">
    <?= csrf_field() ?>

    <?php foreach ($fields as $field): ?>
        <?= component('cms:form-field', [
            'field' => $field,
            'value' => $isEdit ? ($record[$field->name] ?? $field->default) : $field->default,
        ]) ?>
    <?php endforeach; ?>

    <div style="display: flex; gap: var(--space-sm); margin-top: var(--space-lg);">
        <?= component('cms:button', ['variant' => 'primary', 'type' => 'submit'], $isEdit ? 'Update' : 'Create') ?>

        <?php if ($isEdit): ?>
            <?= component('cms:button', ['variant' => 'danger', 'type' => 'button', 'onclick' => "if(confirm('Delete this record?')){document.getElementById('delete-form').submit()}"], 'Delete') ?>
        <?php endif; ?>
    </div>
</form>

<?php if ($isEdit): ?>
    <form id="delete-form" method="POST" action="/cms/<?= e($table) ?>/<?= e((string) $id) ?>/delete" style="display: none;">
        <?= csrf_field() ?>
    </form>
<?php endif; ?>

<?php if ($isEdit && !empty($relatedRecords)): ?>
    <?php foreach ($relatedRecords as $related): ?>
        <div class="cms-related">
            <h3>
                <?= e($related['displayName']) ?>
                <span style="font-weight: normal; color: var(--text-2); font-size: var(--text-sm);">
                    (<?= $related['type'] === 'mtm' ? 'many-to-many' : 'has many' ?>)
                </span>
            </h3>
            <?php if (!empty($related['rows'])): ?>
                <ul class="cms-related-list">
                    <?php foreach ($related['rows'] as $relRow): ?>
                        <li>
                            <a href="/cms/<?= e($related['table']) ?>/<?= e((string) $relRow[$related['pkName']]) ?>">
                                <?= e((string) ($relRow[$related['labelColumn']] ?? $relRow[$related['pkName']])) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="color: var(--text-2); font-size: var(--text-sm);">No related records.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
