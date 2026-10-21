<?php

use App\Controllers\HealthController;

$router->get('/health', fn($p, $c) => HealthController::check());
