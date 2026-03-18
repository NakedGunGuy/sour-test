<?php
$variant = $variant ?? 'text';
$classes = ['skeleton', "skeleton-{$variant}"];
$width = $width ?? '';
$height = $height ?? '';
$style = '';
if ($width) {
    $style .= "width: {$width};";
}
if ($height) {
    $style .= "height: {$height};";
}
?>
<div class="<?= e(implode(' ', $classes)) ?>"<?= $style ? ' style="' . e($style) . '"' : '' ?>></div>
