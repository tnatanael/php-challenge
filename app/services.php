<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use App\Services\MessageQueue;
use App\Services\StockApiService;

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
        
        StockApiService::class => function() {
            return new StockApiService();
        },
    ]);
};
