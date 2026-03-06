<?php
/** @var \Sauerkraut\CMS\Field $field */
$value = $value ?? $field->default ?? '';
$error = $error ?? '';
$id = 'field-' . $field->name;
$hasError = !empty($error);
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
            ><?= e((string) $value) ?></textarea>
            <?php break; ?>

        <?php case 'select': ?>
            <select
                class="form-input"
                id="<?= e($id) ?>"
                name="<?= e($field->name) ?>"
                <?= $field->required ? 'required' : '' ?>
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
            >
            <?php break; ?>

        <?php default: ?>
            <input
                type="text"
                class="form-input"
                id="<?= e($id) ?>"
                name="<?= e($field->name) ?>"
                value="<?= e((string) $value) ?>"
            >
    <?php endswitch; ?>

    <?php if ($hasError): ?>
        <p class="form-error"><?= e($error) ?></p>
    <?php endif; ?>
</div>
