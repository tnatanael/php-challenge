<?php

declare(strict_types=1);

namespace Tests;

use Slim\Exception\HttpUnauthorizedException;

/**
 * Class HelloTest
 * @package Tests
 */
class HelloTest extends BaseTestCase
{
    /**
     * @var \Slim\App
     */
    protected $app;

    /**
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->app = $this->getAppInstance();
    }

    public function testHelloEndpoint()
    {
        // Arrange
        $request = $this->createRequest('GET', '/hello/Test');

        // Act
        $response = $this->app->handle($request);
        $body = (string) $response->getBody();

        // Assert
        $this->assertEquals("Hello, Test", $body);
    }

    public function testByeEndpointThrowsUnauthorized()
    {
        // Arrange
        $request = $this->createRequest('GET', '/bye/MyName');

        // Assert
        $this->expectException(HttpUnauthorizedException::class);

        // Act
        $response = $this->app->handle($request);
    }

    public function testByeEndpointWithBasicAuth()
    {
        // Arrange
        $headers = ['HTTP_ACCEPT' => 'application/json', 'Authorization' => $this->getAuthorizationHeader()];
        $request = $this->createRequest('GET', '/bye/My Name', $headers);

        // Act
        $response = $this->app->handle($request);
        $body = (string) $response->getBody();

        // Assert
        $this->assertEquals("Bye, My Name", $body);
    }
}
