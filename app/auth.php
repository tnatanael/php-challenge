<?php

declare(strict_types=1);

use Slim\App;
use Slim\Exception\HttpUnauthorizedException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

return function (App $app) {
    // Only keep the middleware to throw 401 with correct slim exception
    $app->add(function (Request $request, RequestHandler $handler) {
        $response = $handler->handle($request);
                
        $statusCode = $response->getStatusCode();

        if ($statusCode == 401) {
            throw new HttpUnauthorizedException($request);
        }

        return $response;
    });
};
