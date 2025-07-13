<?php

declare(strict_types=1);

use App\Controllers\HelloController;
use App\Controllers\UserController;
use Slim\App;

return function (App $app) {
    // unprotected routes
    $app->get('/hello/{name}', HelloController::class . ':hello');
    
    // user routes
    $app->get('/users', UserController::class . ':getAll');
    $app->post('/users', UserController::class . ':create');
    $app->get('/users/{id}', UserController::class . ':getOne');
    $app->put('/users/{id}', UserController::class . ':update');
    $app->delete('/users/{id}', UserController::class . ':delete');

    // protected routes
    $app->get('/bye/{name}', HelloController::class . ':bye');
    
    // OpenAPI documentation
    $app->get('/api/documentation', function ($request, $response) {

        $openapi = (new \OpenApi\Generator())->generate([
            __DIR__ . '/../src/Controllers',
            __DIR__ . '/../src/OpenApi',
            __DIR__ . '/../src/Models'
        ]);
        
        // Add JSON_PRETTY_PRINT and ensure proper encoding
        $response->getBody()->write($openapi->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    });
    
    // Add this after your other routes
    $app->get('/swagger', function ($request, $response) {
        include __DIR__ . '/../public/swagger-ui.php';
        return $response;
    });
};
