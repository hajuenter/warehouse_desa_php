<?php

use App\Controllers\AuthController;
use App\Middlewares\AuthJwt;

$router->post('/auth/login', fn($p, $c) => AuthController::login(getJsonBody()));

$router->post('/auth/forgot-password', fn($p, $c) => AuthController::forgotPassword());

$router->post('/auth/reset-password', fn($p, $c) => AuthController::resetPassword(getJsonBody()));

$router->post(
    '/auth/change-password',
    fn($p, $c) => ['user' => AuthJwt::authenticate()],
    fn($p, $c) => AuthController::changePassword($c['user'], getJsonBody())
);
