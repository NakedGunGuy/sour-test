<?php
/** @var \Sauerkraut\CMS\Field $field */
$value = $value ?? $field->default ?? '';
$error = $error ?? '';
$id = 'field-' . $field->name;
$hasError = !empty($error);

// Check for custom field type
$customFieldType = \Sauerkraut\CMS\CmsController::resolveFieldType($field->type);
if ($customFieldType) {
    echo $customFieldType->render($field, $value, $error);
    return;
}
?>
<div class="form-field<?= $hasError ? ' has-error' : '' ?>">
    <label class="form-label" for="<?= e($id) ?>">
        <?= e($field->label) ?>
        <?php if ($field->required): ?>
            <span class="form-required">*</span>
        <?php endif; ?>
    </label>

    <?php switch ($field->type):
        case 'text':
        case 'slug': ?>
            <input
                type="text"
                class="form-input"
                id="<?= e($id) ?>"
                name="<?= e($field->name) ?>"
                value="<?= e((string) $value) ?>"
                <?= $field->required ? 'required' : '' ?>
                <?= $field->readonly ? 'readonly' : '' ?>
            >
            <?php break; ?>

        <?php case 'textarea':
        case 'richtext': ?>
            <textarea
                class="form-input form-textarea"
                id="<?= e($id) ?>"
                name="<?= e($field->name) ?>"
                rows="<?= $field->type === 'richtext' ? '12' : '4' ?>"
                <?= $field->required ? 'required' : '' ?>
                <?= $field->readonly ? 'readonly' : '' ?>
            ><?= e((string) $value) ?></textarea>
            <?php break; ?>

        <?php case 'select': ?>
            <select
                class="form-input"
                id="<?= e($id) ?>"
                name="<?= e($field->name) ?>"
                <?= $field->required ? 'required' : '' ?>
                <?= $field->readonly ? 'disabled' : '' ?>
            >
                <option value="">— Select —</option>
                <?php foreach ($field->options as $opt):
                    if (str_contains($opt, ':')) {
                        [$optValue, $optLabel] = explode(':', $opt, 2);
                    } else {
                        $optValue = $opt;
                        $optLabel = ucfirst($opt);
                    }
                ?>
                    <option value="<?= e($optValue) ?>"<?= (string) $value === $optValue ? ' selected' : '' ?>><?= e($optLabel) ?></option>
                <?php endforeach; ?>
            </select>
            <?php break; ?>

        <?php case 'boolean': ?>
            <input type="hidden" name="<?= e($field->name) ?>" value="0">
            <input
                type="checkbox"
                class="form-checkbox"
                id="<?= e($id) ?>"
                name="<?= e($field->name) ?>"
                value="1"
                <?= $value ? 'checked' : '' ?>
                <?= $field->readonly ? 'disabled' : '' ?>
            >
            <?php break; ?>

        <?php case 'date': ?>
            <input
                type="date"
                class="form-input"
                id="<?= e($id) ?>"
                name="<?= e($field->name) ?>"
                value="<?= e((string) $value) ?>"
                <?= $field->required ? 'required' : '' ?>
                <?= $field->readonly ? 'readonly' : '' ?>
            >
            <?php break; ?>

        <?php case 'image': ?>
            <?php if ($value): ?>
                <div class="form-image-preview">
                    <img src="/media/<?= e((string) $value) ?>" alt="">
                </div>
            <?php endif; ?>
            <input
                type="file"
                class="form-input"
                id="<?= e($id) ?>"
                name="<?= e($field->name) ?>"
                accept="image/*"
                <?= $field->readonly ? 'disabled' : '' ?>
            >
            <?php break; ?>

        <?php case 'number': ?>
            <input
                type="number"
                class="form-input"
                id="<?= e($id) ?>"
                name="<?= e($field->name) ?>"
                value="<?= e((string) $value) ?>"
                <?= $field->required ? 'required' : '' ?>
                <?= $field->readonly ? 'readonly' : '' ?>
            >
            <?php break; ?>

        <?php default: ?>
            <input
                type="text"
                class="form-input"
                id="<?= e($id) ?>"
                name="<?= e($field->name) ?>"
                value="<?= e((string) $value) ?>"
                <?= $field->readonly ? 'readonly' : '' ?>
            >
    <?php endswitch; ?>

    <?php if ($hasError): ?>
        <p class="form-error"><?= e($error) ?></p>
    <?php endif; ?>
</div>
