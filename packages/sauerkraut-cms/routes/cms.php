<?php

use Sauerkraut\CMS\CmsController;

/** @var \Sauerkraut\Router $router */

$router->name('cms.index')->get('/', [CmsController::class, 'index']);
$router->name('cms.list')->get('/{table}', [CmsController::class, 'list']);
$router->name('cms.create')->get('/{table}/create', [CmsController::class, 'create']);
$router->name('cms.store')->post('/{table}', [CmsController::class, 'store']);
$router->name('cms.edit')->get('/{table}/{id}', [CmsController::class, 'edit']);
$router->name('cms.update')->post('/{table}/{id}', [CmsController::class, 'update']);
$router->name('cms.delete')->post('/{table}/{id}/delete', [CmsController::class, 'delete']);
