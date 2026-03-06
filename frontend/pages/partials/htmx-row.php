<?php
$statuses = ['Active', 'Idle', 'Away', 'Busy'];
$status = $statuses[array_rand($statuses)];
$updated = date('H:i:s');
?>
<?php open('table/row', array_filter(['id' => "user-{$id}", 'target' => $target ?? null])) ?>
    <td><?= e($name) ?></td>
    <td><?= e($role) ?></td>
    <td><?= e($status) ?></td>
    <td><?= e($updated) ?></td>
    <td>
        <button class="btn btn-ghost" hx-get="/htmx/row?id=<?= e($id) ?>&name=<?= urlencode($name) ?>&role=<?= urlencode($role) ?>" hx-swap="none">&#8635; Refresh</button>
    </td>
<?php close() ?>
