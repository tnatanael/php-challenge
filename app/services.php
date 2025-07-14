<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use App\Services\MessageQueue;
use App\Services\StockApiService;
use App\Services\StooqApiService;
use App\Services\HttpClient;
use App\Services\EmailNotificationService;
use App\Services\TemplateRenderer;
use App\Services\Interfaces\HttpClientInterface;
use App\Services\Interfaces\StockApiServiceInterface;
use App\Services\Interfaces\NotificationServiceInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        Mailer::class => function() {
            // Check if mailer is enabled
            $enabled = (bool)($_ENV['MAILER_ENABLED'] ?? false);
            
            if (!$enabled) {
                return null;
            }
            
            // Use DSN from environment or build from individual settings
            $dsn = $_ENV['MAILER_DSN'] ?? null;
            
            if (!$dsn) {
                $host = $_ENV['MAILER_HOST'] ?? 'smtp.mailtrap.io';
                $port = intval($_ENV['MAILER_PORT'] ?? 465);
                $username = $_ENV['MAILER_USERNAME'] ?? 'test';
                $password = $_ENV['MAILER_PASSWORD'] ?? 'test';
                
                $dsn = "smtp://{$username}:{$password}@{$host}:{$port}";
            }
            
            $transport = Transport::fromDsn($dsn);
            return new Mailer($transport);
        },
        
        MessageQueue::class => function() {
            return new MessageQueue();
        },
        
        // Register HttpClient implementation
        HttpClientInterface::class => function() {
            return new HttpClient();
        },
        
        // Bind StockApiServiceInterface to StooqApiService implementation
        StockApiServiceInterface::class => function($container) {
            return new StooqApiService(
                $container->get(HttpClientInterface::class)
            );
        },
        
        // Register TemplateRenderer
        TemplateRenderer::class => function() {
            return new TemplateRenderer(__DIR__ . '/../src/Views');
        },
        
        // Bind NotificationServiceInterface to EmailNotificationService implementation
        NotificationServiceInterface::class => function($container) {
            return new EmailNotificationService(
                $container->get(MessageQueue::class),
                $container->get(TemplateRenderer::class),
                [
                    'from_email' => $_ENV['MAILER_FROM'] ?? 'stock-api@example.com',
                    'from_name' => $_ENV['MAILER_FROM_NAME'] ?? 'Stock API'
                ]
            );
        },
        
        // Register User related services
        App\Repositories\Interfaces\UserRepositoryInterface::class => function() {
            return new App\Repositories\UserRepository();
        },
        
        App\Services\Interfaces\UserServiceInterface::class => function($container) {
            return new App\Services\UserService(
                $container->get(App\Repositories\Interfaces\UserRepositoryInterface::class)
            );
        },
        
        App\Validators\UserValidator::class => function() {
            return new App\Validators\UserValidator();
        }
    ]);
};
