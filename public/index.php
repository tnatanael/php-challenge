<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

$ENV = $_ENV['ENV'] ?? 'dev';

$containerBuilder = new ContainerBuilder();

// Import services
$dependencies = require __DIR__ . '/../app/services.php';
$dependencies($containerBuilder);

// Initialize app with PHP-DI
$container = $containerBuilder->build();
AppFactory::setContainer($container);

$app = AppFactory::create();

// Register routes
$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

// Setup Basic Auth
$auth = require __DIR__ . '/../app/auth.php';
$auth($app);

$displayErrorDetails = $ENV == 'dev';
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, true, true);

// Error Handler
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType('application/json');

$app->run();
