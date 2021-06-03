<?php

declare(strict_types=1);

use Slim\App;
use Slim\Exception\HttpUnauthorizedException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Tuupola\Middleware\HttpBasicAuthentication;

return function (App $app) {
    $username = $_ENV["ADMIN_USERNAME"] ?? 'root';
    $password = $_ENV["ADMIN_PASSWORD"] ?? 'secret';

    // 1st middleware to configure basic authentication
    $app->add(new HttpBasicAuthentication([
        "path" => ["/bye"], // protected routes
        "users" => [
            $username => $password,
        ],
        "error" => function ($response) {
            return $response->withStatus(401);
        }
    ]));

    // 2nd middleware to throw 401 with correct slim exception
    // Reformat when lin updates to v4, see: https://github.com/tuupola/slim-basic-auth/issues/95
    $app->add(function (Request $request, RequestHandler $handler) {
        $response = $handler->handle($request);
        $statusCode = $response->getStatusCode();

        if ($statusCode == 401) {
            throw new HttpUnauthorizedException($request);
        }

        return $response;
    });
};
