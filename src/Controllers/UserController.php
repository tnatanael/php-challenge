<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTOs\SuccessResponse;
use App\DTOs\ErrorResponse;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\UserServiceInterface;
use App\Validators\UserValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

class UserController
{
    private UserRepositoryInterface $userRepository;
    private UserServiceInterface $userService;
    private UserValidator $validator;

    /**
     * UserController constructor.
     * 
     * @param UserRepositoryInterface $userRepository
     * @param UserServiceInterface $userService
     * @param UserValidator $validator
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        UserServiceInterface $userService,
        UserValidator $validator
    ) {
        $this->userRepository = $userRepository;
        $this->userService = $userService;
        $this->validator = $validator;
    }

    #[OA\Get(
        path: "/users",
        summary: "Get all users",
        tags: ["Users"],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(
        response: 200,
        description: "Returns a list of all users",
        content: new OA\JsonContent(
            allOf: [
                new OA\Schema(ref: "#/components/schemas/ApiResponse"),
                new OA\Schema(properties: [
                    new OA\Property(
                        property: "data",
                        type: "array",
                        items: new OA\Items(ref: "#/components/schemas/User")
                    ),
                    new OA\Property(
                        property: "message",
                        example: "Users retrieved successfully"
                    )
                ])
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Unauthorized - JWT token missing or invalid"
    )]
    public function getAll(Request $request, Response $response): Response
    {
        $users = $this->userRepository->findAll();
        return $this->jsonResponse($response, $users, 'Users retrieved successfully', 200);
    }
    
    #[OA\Post(
        path: "/users",
        summary: "Create a new user",
        tags: ["Users"],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/User")
    )]
    #[OA\Response(
        response: 201,
        description: "User created successfully",
        content: new OA\JsonContent(
            allOf: [
                new OA\Schema(ref: "#/components/schemas/ApiResponse"),
                new OA\Schema(properties: [
                    new OA\Property(
                        property: "data",
                        ref: "#/components/schemas/User"
                    ),
                    new OA\Property(
                        property: "message",
                        example: "User created successfully"
                    )
                ])
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Bad request"
    )]
    #[OA\Response(
        response: 401,
        description: "Unauthorized - JWT token missing or invalid"
    )]
    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate input data
        $validationResult = $this->validator->validateForCreation($data);
        if (!$validationResult->isValid()) {
            return $this->errorResponse(
                $response, 
                $validationResult->getFirstError(), 
                400
            );
        }
        
        // Create user through service
        $result = $this->userService->createUser($data);
        if (!$result->isSuccess()) {
            return $this->errorResponse(
                $response, 
                $result->getMessage(), 
                400
            );
        }
        
        return $this->jsonResponse(
            $response, 
            $result->getData(), 
            'User created successfully', 
            201
        );
    }
    
    #[OA\Get(
        path: "/users/{id}",
        summary: "Get a user by ID",
        tags: ["Users"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Returns the user",
        content: new OA\JsonContent(
            allOf: [
                new OA\Schema(ref: "#/components/schemas/ApiResponse"),
                new OA\Schema(properties: [
                    new OA\Property(
                        property: "data",
                        ref: "#/components/schemas/User"
                    ),
                    new OA\Property(
                        property: "message",
                        example: "User retrieved successfully"
                    )
                ])
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: "User not found"
    )]
    #[OA\Response(
        response: 401,
        description: "Unauthorized - JWT token missing or invalid"
    )]
    public function getOne(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $user = $this->userRepository->findById($id);
        
        if (!$user) {
            return $this->errorResponse($response, 'User not found', 404);
        }
        
        return $this->jsonResponse($response, $user, 'User retrieved successfully', 200);
    }
    
    #[OA\Put(
        path: "/users/{id}",
        summary: "Update a user",
        tags: ["Users"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/User")
    )]
    #[OA\Response(
        response: 200,
        description: "User updated successfully",
        content: new OA\JsonContent(
            allOf: [
                new OA\Schema(ref: "#/components/schemas/ApiResponse"),
                new OA\Schema(properties: [
                    new OA\Property(
                        property: "data",
                        ref: "#/components/schemas/User"
                    ),
                    new OA\Property(
                        property: "message",
                        example: "User updated successfully"
                    )
                ])
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: "User not found"
    )]
    #[OA\Response(
        response: 401,
        description: "Unauthorized - JWT token missing or invalid"
    )]
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();
        
        // Validate input data
        $validationResult = $this->validator->validateForUpdate($data);
        if (!$validationResult->isValid()) {
            return $this->errorResponse(
                $response, 
                $validationResult->getFirstError(), 
                400
            );
        }
        
        // Update user through service
        $result = $this->userService->updateUser($id, $data);
        if (!$result->isSuccess()) {
            $statusCode = $result->getCode() ?: 400;
            return $this->errorResponse(
                $response, 
                $result->getMessage(), 
                $statusCode
            );
        }
        
        return $this->jsonResponse(
            $response, 
            $result->getData(), 
            'User updated successfully', 
            200
        );
    }
    
    #[OA\Delete(
        path: "/users/{id}",
        summary: "Delete a user",
        tags: ["Users"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "User deleted successfully"
    )]
    #[OA\Response(
        response: 404,
        description: "User not found"
    )]
    #[OA\Response(
        response: 401,
        description: "Unauthorized - JWT token missing or invalid"
    )]
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];
        
        // Delete user through service
        $result = $this->userService->deleteUser($id);
        if (!$result->isSuccess()) {
            $statusCode = $result->getCode() ?: 400;
            return $this->errorResponse(
                $response, 
                $result->getMessage(), 
                $statusCode
            );
        }
        
        return $this->jsonResponse($response, null, 'User deleted successfully', 200);
    }
    
    /**
     * Create a JSON response
     *
     * @param Response $response
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return Response
     */
    private function jsonResponse(Response $response, $data, string $message, int $statusCode): Response
    {
        $successResponse = new SuccessResponse($data, $message);
        $response->getBody()->write(json_encode($successResponse));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
    
    /**
     * Create an error response
     *
     * @param Response $response
     * @param string $message
     * @param int $statusCode
     * @return Response
     */
    private function errorResponse(Response $response, string $message, int $statusCode): Response
    {
        $errorResponse = new ErrorResponse($message, $statusCode);
        $response->getBody()->write(json_encode($errorResponse));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}