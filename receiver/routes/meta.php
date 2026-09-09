<?php

use App\Controllers\MetaController;
use App\Middlewares\AuthJwt;

$router->get(
    '/desa',
    fn($p, $c) => ['user' => AuthJwt::authenticate()],
    fn($p, $c) => MetaController::listDesa()
);

$router->get(
    '/tables',
    fn($p, $c) => ['user' => AuthJwt::authenticate()],
    fn($p, $c) => MetaController::listTables()
);