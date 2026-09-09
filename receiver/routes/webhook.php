<?php

use App\Controllers\WebhookController;
use App\Middlewares\AuthDesa;

$router->post(
    '/webhook',
    fn($p, $c) => ['desa' => AuthDesa::authenticate()],
    fn($p, $c) => WebhookController::receive(getJsonBody(), $c['desa'])
);