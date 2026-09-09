<?php

use App\Controllers\QueueController;
use App\Middlewares\AuthJwt;

$router->get(
    '/queue',
    fn($p, $c) => ['user' => AuthJwt::authenticate()],
    fn($p, $c) => QueueController::list()
);

$router->get(
    '/queue/summary',
    fn($p, $c) => ['user' => AuthJwt::authenticate()],
    fn($p, $c) => QueueController::summary()
);