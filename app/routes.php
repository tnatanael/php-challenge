<?php

declare(strict_types=1);

use App\Controllers\HelloController;
use App\Controllers\UserController;
use App\Controllers\AuthController;
use App\Controllers\StockController;
use App\Middleware\JwtMiddleware;
use Slim\App;

return function (App $app) {
    // unprotected routes
    $app->get('/hello/{name}', HelloController::class . ':hello');
    $app->get('/bye/{name}', HelloController::class . ':bye')
        ->add(JwtMiddleware::class);
    
    // authentication routes
    $app->post('/auth/login', AuthController::class . ':login');

    // stock routes
    $app->get('/stock', StockController::class . ':getStock')
        ->add(JwtMiddleware::class);
    $app->get('/history', StockController::class . ':getHistory')
        ->add(JwtMiddleware::class);
    
    // You can also protect groups of routes
    $app->group('/users', function ($group) {
        // user routes
        $group->get('', UserController::class . ':getAll');
        $group->post('', UserController::class . ':create');
        $group->get('/{id}', UserController::class . ':getOne');
        $group->put('/{id}', UserController::class . ':update');
        $group->delete('/{id}', UserController::class . ':delete');
    })->add(JwtMiddleware::class);
    
    // OpenAPI documentation
    $app->get('/api/documentation', function ($request, $response) {

        $openapi = (new \OpenApi\Generator())->generate([
            __DIR__ . '/../src/Controllers',
            __DIR__ . '/../src/OpenApi',
            __DIR__ . '/../src/Models',
            __DIR__ . '/../src/DTOs'  // Add this line
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
