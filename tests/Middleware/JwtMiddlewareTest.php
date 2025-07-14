<?php

declare(strict_types=1);

namespace Tests\Middleware;

use App\Middleware\JwtMiddleware;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Uri;

class JwtMiddlewareTest extends TestCase
{
    private JwtMiddleware $middleware;
    private $requestHandler;
    private string $jwtSecret = 'test-secret-key';
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set environment variables
        $_ENV['JWT_SECRET'] = $this->jwtSecret;
        $_ENV['JWT_EXPIRATION'] = '3600';
        
        $this->middleware = new JwtMiddleware();
        
        // Create a mock request handler
        $this->requestHandler = $this->createMock(RequestHandlerInterface::class);
    }
    
    private function createRequest(array $headers = []): ServerRequestInterface
    {
        $uri = new Uri('http', 'example.com', 80, '/');
        $stream = (new StreamFactory())->createStream('');
        return new Request('GET', $uri, new Headers($headers), [], [], $stream);
    }
    
    public function testProcessWithValidToken(): void
    {
        // Create a valid JWT token
        $payload = [
            'iat' => time(),
            'exp' => time() + 3600,
            'user_id' => 1,
            'email' => 'test@example.com',
        ];
        $token = JWT::encode($payload, $this->jwtSecret, 'HS256');
        
        // Create a request with the token
        $request = $this->createRequest(['Authorization' => 'Bearer ' . $token]);
        
        // Mock the request handler to return a response
        $mockResponse = $this->createMock(ResponseInterface::class);
        $this->requestHandler->method('handle')->willReturn($mockResponse);
        
        // Process the request
        $response = $this->middleware->process($request, $this->requestHandler);
        
        // Verify the response is the one from the handler
        $this->assertSame($mockResponse, $response);
    }
    
    public function testProcessWithMissingToken(): void
    {
        // Create a request without a token
        $request = $this->createRequest();
        
        // Process the request
        $response = $this->middleware->process($request, $this->requestHandler);
        
        // Verify the response status code is 401
        $this->assertEquals(401, $response->getStatusCode());
    }
    
    public function testProcessWithExpiredToken(): void
    {
        // Create an expired JWT token
        $payload = [
            'iat' => time() - 7200,
            'exp' => time() - 3600,
            'user_id' => 1,
            'email' => 'test@example.com',
        ];
        $token = JWT::encode($payload, $this->jwtSecret, 'HS256');
        
        // Create a request with the expired token
        $request = $this->createRequest(['Authorization' => 'Bearer ' . $token]);
        
        // Process the request
        $response = $this->middleware->process($request, $this->requestHandler);
        
        // Verify the response status code is 401
        $this->assertEquals(401, $response->getStatusCode());
    }
    
    public function testProcessWithInvalidSignature(): void
    {
        // Create a token with a different secret
        $payload = [
            'iat' => time(),
            'exp' => time() + 3600,
            'user_id' => 1,
            'email' => 'test@example.com',
        ];
        $token = JWT::encode($payload, 'wrong-secret', 'HS256');
        
        // Create a request with the invalid token
        $request = $this->createRequest(['Authorization' => 'Bearer ' . $token]);
        
        // Process the request
        $response = $this->middleware->process($request, $this->requestHandler);
        
        // Verify the response status code is 401
        $this->assertEquals(401, $response->getStatusCode());
    }
    
    public function testProcessWithMalformedToken(): void
    {
        // Create a request with a malformed token
        $request = $this->createRequest(['Authorization' => 'Bearer malformed.token.here']);
        
        // Process the request
        $response = $this->middleware->process($request, $this->requestHandler);
        
        // Verify the response status code is 401
        $this->assertEquals(401, $response->getStatusCode());
    }
    
    public function testProcessWithInvalidAuthorizationFormat(): void
    {
        // Create a request with an invalid Authorization header format
        $request = $this->createRequest(['Authorization' => 'InvalidFormat token123']);
        
        // Process the request
        $response = $this->middleware->process($request, $this->requestHandler);
        
        // Verify the response status code is 401
        $this->assertEquals(401, $response->getStatusCode());
    }
}