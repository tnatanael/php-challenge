<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    openapi: "3.0.0"
)]
#[OA\Info(
    title: "Stock API",
    version: "1.0.0",
    description: "A REST API for tracking stock market values",
    contact: new OA\Contact(
        email: "thiagonatanael@gmail.com",
        name: "Thiago Natanael"
    )
)]
#[OA\Server(
    url: "/",
    description: "API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "JWT Authentication"
)]
class OpenApiDefinition
{
    // This class doesn't need any content - it's just for the annotations
}