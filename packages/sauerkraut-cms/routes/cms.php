<?php

use Sauerkraut\CMS\CmsController;

/** @var \Sauerkraut\Router $router */

$router->name('cms.index')->get('/', [CmsController::class, 'index']);

$router->name('cms.list')->get('/{table}', function (\Sauerkraut\Request $request, string $table) {
    return CmsController::forTable($table)->list($request, $table);
});

$router->name('cms.create')->get('/{table}/create', function (\Sauerkraut\Request $request, string $table) {
    return CmsController::forTable($table)->create($request, $table);
});

$router->name('cms.store')->post('/{table}', function (\Sauerkraut\Request $request, string $table) {
    return CmsController::forTable($table)->store($request, $table);
});

$router->name('cms.edit')->get('/{table}/{id}', function (\Sauerkraut\Request $request, string $table, string $id) {
    return CmsController::forTable($table)->edit($request, $table, $id);
});

$router->name('cms.update')->post('/{table}/{id}', function (\Sauerkraut\Request $request, string $table, string $id) {
    return CmsController::forTable($table)->update($request, $table, $id);
});

$router->name('cms.delete')->post('/{table}/{id}/delete', function (\Sauerkraut\Request $request, string $table, string $id) {
    return CmsController::forTable($table)->delete($request, $table, $id);
});
