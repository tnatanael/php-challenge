<?php

declare(strict_types=1);

use App\Controllers\HelloController;
use Slim\App;

return function (App $app) {
    // unprotected routes
    $app->get('/hello/{name}', HelloController::class . ':hello');

    // protected routes
    $app->get('/bye/{name}', HelloController::class . ':bye');
    
    // OpenAPI documentation
    $app->get('/api/documentation', function ($request, $response) {

        $openapi = (new \OpenApi\Generator())->generate([
            __DIR__ . '/../src/Controllers',
            __DIR__ . '/../src/OpenApi'
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
