<?php

declare(strict_types=1);

use App\HelloController;
use Slim\App;

return function (App $app) {
    // unprotected routes
    $app->get('/hello/{name}', HelloController::class . ':hello');

    // protected routes
    $app->get('/bye/{name}', HelloController::class . ':bye');
};
