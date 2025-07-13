<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTOs\SuccessResponse;
use App\DTOs\ErrorResponse;
use App\Models\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

class UserController
{
    /**
     * UserController constructor.
     */
    public function __construct()
    {
    }

    #[OA\Get(
        path: "/users",
        summary: "Get all users",
        tags: ["Users"],
    )]
    #[OA\Response(
        response: 200,
        description: "Returns a list of all users",
        content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/User"))
    )]
    public function getAll(Request $request, Response $response): Response
    {
        $users = User::all();
        $successResponse = new SuccessResponse($users, 'Users retrieved successfully');
        
        $response->getBody()->write(json_encode($successResponse));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
    
    #[OA\Post(
        path: "/users",
        summary: "Create a new user",
        tags: ["Users"],
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/User")
    )]
    #[OA\Response(
        response: 201,
        description: "User created successfully",
        content: new OA\JsonContent(ref: "#/components/schemas/User")
    )]
    #[OA\Response(
        response: 400,
        description: "Bad request"
    )]
    public function create(Request $request, Response $response): Response
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
        
        // Check if email already exists
        $existingUser = User::where('email', $data['email'])->first();
        if ($existingUser) {
            $errorResponse = new ErrorResponse('Email already in use', 400);
            $response->getBody()->write(json_encode($errorResponse));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Create user
        $user = User::create([
            'email' => $data['email'],
            'password' => $data['password']
        ]);
        
        $successResponse = new SuccessResponse($user, 'User created successfully');
        $response->getBody()->write(json_encode($successResponse));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }
    
    #[OA\Get(
        path: "/users/{id}",
        summary: "Get a user by ID",
        tags: ["Users"],
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
        content: new OA\JsonContent(ref: "#/components/schemas/User")
    )]
    #[OA\Response(
        response: 404,
        description: "User not found"
    )]
    public function getOne(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $user = User::find($id);
        
        if (!$user) {
            $errorResponse = new ErrorResponse('User not found', 404);
            $response->getBody()->write(json_encode($errorResponse));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }
        
        $successResponse = new SuccessResponse($user, 'User retrieved successfully');
        $response->getBody()->write(json_encode($successResponse));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
    
    #[OA\Put(
        path: "/users/{id}",
        summary: "Update a user",
        tags: ["Users"],
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
        content: new OA\JsonContent(ref: "#/components/schemas/User")
    )]
    #[OA\Response(
        response: 404,
        description: "User not found"
    )]
    public function update(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $user = User::find($id);
        
        if (!$user) {
            $errorResponse = new ErrorResponse('User not found', 404);
            $response->getBody()->write(json_encode($errorResponse));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }
        
        $data = $request->getParsedBody();
        
        // Update email if provided
        if (isset($data['email'])) {
            // Check if email already exists for another user
            $existingUser = User::where('email', $data['email'])->where('id', '!=', $id)->first();
            if ($existingUser) {
                $errorResponse = new ErrorResponse('Email already in use', 400);
                $response->getBody()->write(json_encode($errorResponse));
                
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            $user->email = $data['email'];
        }
        
        // Update password if provided
        if (isset($data['password'])) {
            $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $user->save();
        
        $successResponse = new SuccessResponse($user, 'User updated successfully');
        $response->getBody()->write(json_encode($successResponse));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
    
    #[OA\Delete(
        path: "/users/{id}",
        summary: "Delete a user",
        tags: ["Users"],
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
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $user = User::find($id);
        
        if (!$user) {
            $errorResponse = new ErrorResponse('User not found', 404);
            $response->getBody()->write(json_encode($errorResponse));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }
        
        $user->delete();
        
        $successResponse = new SuccessResponse(null, 'User deleted successfully');
        $response->getBody()->write(json_encode($successResponse));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}