<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

class HelloController
{
    /**
     * HelloController constructor.
     */
    public function __construct()
    {
    }

    #[OA\Get(
        path: "/hello/{name}",
        summary: "Get a hello greeting",
        tags: ["Greetings"],
    )]
    #[OA\Parameter(
        name: "name",
        in: "path",
        required: true,
        description: "Name to greet",
        schema: new OA\Schema(type: "string")
    )]
    #[OA\Response(
        response: 200,
        description: "Returns a greeting",
        content: new OA\JsonContent(type: "string")
    )]
    public function hello(Request $request, Response $response, array $args): Response
    {
        $name = $args['name'];
        $body = "Hello, $name";

        $response->getBody()->write($body);

        return $response;
    }

    #[OA\Get(
        path: "/bye/{name}",
        summary: "Get a goodbye message",
        tags: ["Greetings"],
        security: [['basicAuth' => []]]
    )]
    #[OA\Parameter(
        name: "name",
        in: "path",
        required: true,
        description: "Name to say goodbye to",
        schema: new OA\Schema(type: "string")
    )]
    #[OA\Response(
        response: 200,
        description: "Returns a goodbye message",
        content: new OA\JsonContent(type: "string")
    )]
    #[OA\Response(
        response: 401,
        description: "Unauthorized"
    )]
    public function bye(Request $request, Response $response, array $args): Response
    {
        $name = $args['name'];
        $body = "Bye, $name";

        $response->getBody()->write($body);

        return $response;
    }
}