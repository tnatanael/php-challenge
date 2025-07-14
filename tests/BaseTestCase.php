<?php

declare(strict_types=1);

namespace Tests;

use Tests\Config\TestDatabase;
use DI\ContainerBuilder;
use Exception;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase as PHPUnit_TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Uri;
use Symfony\Component\Dotenv\Dotenv;

class BaseTestCase extends PHPUnit_TestCase
{
    /**
     * @return App
     * @throws Exception
     */
    protected function getAppInstance(): App
    {
        parent::setUp();

        $containerBuilder = new ContainerBuilder();

        $dotenv = new Dotenv();
        $dotenv->load(__DIR__ . '/../.env');

        // Initialize in-memory test database
        TestDatabase::boot();

        $dependencies = require __DIR__ . '/../app/services.php';
        $dependencies($containerBuilder);

        $container = $containerBuilder->build();
        AppFactory::setContainer($container);

        $app = AppFactory::create();

        // Add body parsing middleware
        $app->addBodyParsingMiddleware();

        $routes = require __DIR__ . '/../app/routes.php';
        $routes($app);

        $auth = require __DIR__ . '/../app/auth.php';
        $auth($app);

        return $app;
    }

    /**
     * Generate a JWT token for testing protected endpoints
     * 
     * @return String
     */
    protected function getJwtToken(): String
    {
        $jwtSecret = $_ENV["JWT_SECRET"] ?? 'your-secret-key';
        $jwtExpirationTime = (int)($_ENV['JWT_EXPIRATION'] ?? 3600);
        
        $issuedAt = time();
        $expirationTime = $issuedAt + $jwtExpirationTime;
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => 1, // Assuming admin user has ID 1
            'email' => $_ENV["DEFAULT_USERNAME"],
        ];
        
        return JWT::encode($payload, $jwtSecret, 'HS256');
    }

    /**
     * @param string $method
     * @param string $path
     * @param array  $headers
     * @param array  $cookies
     * @param array  $serverParams
     * @return Request
     */
    protected function createRequest(
        string $method,
        string $path,
        array $headers = ['HTTP_ACCEPT' => 'application/json'],
        array $cookies = [],
        array $serverParams = []
    ): Request {
        // Parse path and query string
        $parsed = parse_url('http://example.com' . $path);
        $uriPath = $parsed['path'] ?? '/';
        $query = $parsed['query'] ?? '';
    
        $uri = new Uri('', 'localhost', 80, $uriPath);
        $uri = $uri->withQuery($query);
    
        $handle = fopen('php://temp', 'w+');
        $stream = (new StreamFactory())->createStreamFromResource($handle);
    
        $h = new Headers();
        foreach ($headers as $name => $value) {
            $h->addHeader($name, $value);
        }
    
        return new SlimRequest($method, $uri, $h, $cookies, $serverParams, $stream);
    }
}
