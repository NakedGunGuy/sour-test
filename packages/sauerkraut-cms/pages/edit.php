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

    <?php if ($isEdit && !empty($relatedRecords)): ?>
        <?php foreach ($relatedRecords as $related): ?>
            <?php if ($related['type'] === 'mtm'): ?>
                <div class="form-field" style="margin-top: var(--space-lg);">
                    <label class="form-label"><?= e($related['displayName']) ?></label>
                    <div class="cms-checkbox-group">
                        <?php foreach ($related['allOptions'] as $option):
                            $optId = $option[$related['pkName']];
                            $optLabel = $option[$related['labelColumn']] ?? $optId;
                            $checked = in_array($optId, $related['linkedIds']);
                            $inputName = 'mtm_' . $related['junctionTable'] . '[]';
                        ?>
                            <label class="cms-checkbox-label">
                                <input
                                    type="checkbox"
                                    class="form-checkbox"
                                    name="<?= e($inputName) ?>"
                                    value="<?= e((string) $optId) ?>"
                                    <?= $checked ? 'checked' : '' ?>
                                >
                                <?= e((string) $optLabel) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <!-- Hidden field so empty selection still submits -->
                    <input type="hidden" name="mtm_<?= e($related['junctionTable']) ?>_present" value="1">
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

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
        <?php if ($related['type'] === 'otm'): ?>
            <div class="cms-related">
                <h3>
                    <?= e($related['displayName']) ?>
                    <span style="font-weight: normal; color: var(--text-2); font-size: var(--text-sm);">
                        (has many)
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
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
