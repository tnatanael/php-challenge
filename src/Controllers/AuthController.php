<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTOs\SuccessResponse;
use App\DTOs\ErrorResponse;
use App\Models\User;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

class AuthController
{
    /**
     * @var string
     */
    private string $jwtSecret;
    
    /**
     * @var int
     */
    private int $jwtExpirationTime;
    
    /**
     * AuthController constructor.
     */
    public function __construct()
    {
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
        $this->jwtExpirationTime = (int)($_ENV['JWT_EXPIRATION'] ?? 3600); // Default: 1 hour
    }
    
    #[OA\Post(
        path: "/auth/login",
        summary: "Login with email and password",
        tags: ["Authentication"],
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "email", type: "string", format: "email"),
                new OA\Property(property: "password", type: "string", format: "password", example: "user123"),
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Login successful",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "boolean"),
                new OA\Property(property: "message", type: "string"),
                new OA\Property(property: "data", properties: [
                    new OA\Property(property: "token", type: "string"),
                    new OA\Property(property: "user", ref: "#/components/schemas/User"),
                ], type: "object"),
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Invalid credentials"
    )]
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate required fields
        if (!isset($data['email']) || !isset($data['password'])) {
            $errorResponse = new ErrorResponse('Email and password are required', 400);
            $response->getBody()->write(json_encode($errorResponse));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
        
        // Find user by email
        $user = User::where('email', $data['email'])->first();
        
        // Verify user exists and password is correct
        if (!$user || !password_verify($data['password'], $user->password)) {
            $errorResponse = new ErrorResponse('Invalid credentials', 401);
            $response->getBody()->write(json_encode($errorResponse));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
        
        // Generate JWT token
        $issuedAt = time();
        $expirationTime = $issuedAt + $this->jwtExpirationTime;
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => $user->id,
            'email' => $user->email,
        ];
        
        $token = JWT::encode($payload, $this->jwtSecret, 'HS256');
        
        $responseData = [
            'token' => $token,
            'user' => $user,
        ];
        
        $successResponse = new SuccessResponse($responseData, 'Login successful');
        $response->getBody()->write(json_encode($successResponse));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}