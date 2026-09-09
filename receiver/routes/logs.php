<?php

use App\Controllers\LogController;
use App\Middlewares\AuthJwt;

$router->get(
    '/logs',
    fn($p, $c) => ['user' => AuthJwt::authenticate()],
    fn($p, $c) => LogController::list()
);