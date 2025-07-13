<?php

declare(strict_types=1);

namespace App\Middleware;

use App\DTOs\ErrorResponse;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class JwtMiddleware implements MiddlewareInterface
{
    /**
     * @var string
     */
    private string $jwtSecret;
    
    /**
     * JwtMiddleware constructor.
     */
    public function __construct()
    {
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    }
    
    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        
        // Check if Authorization header exists and starts with 'Bearer '
        if (empty($authHeader) || !preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            $response = new \Slim\Psr7\Response();
            $errorResponse = new ErrorResponse('JWT token not found', 401);
            $response->getBody()->write(json_encode($errorResponse));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
        
        $token = $matches[1];
        
        try {
            // Decode the token
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            // Add the decoded token to the request attributes
            $request = $request->withAttribute('jwt', $decoded);
            $request = $request->withAttribute('user_id', $decoded->user_id);
            $request = $request->withAttribute('email', $decoded->email);
            
            // Continue with the request
            return $handler->handle($request);
            
        } catch (ExpiredException $e) {
            $response = new \Slim\Psr7\Response();
            $errorResponse = new ErrorResponse('Token has expired', 401);
            $response->getBody()->write(json_encode($errorResponse));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
                
        } catch (SignatureInvalidException $e) {
            $response = new \Slim\Psr7\Response();
            $errorResponse = new ErrorResponse('Invalid token signature', 401);
            $response->getBody()->write(json_encode($errorResponse));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
                
        } catch (\Exception $e) {
            $response = new \Slim\Psr7\Response();
            $errorResponse = new ErrorResponse('Invalid token: ' . $e->getMessage(), 401);
            $response->getBody()->write(json_encode($errorResponse));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }
}