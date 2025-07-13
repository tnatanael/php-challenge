<?php

declare(strict_types=1);

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class EmailConsumer
{
    private $connection;
    private $channel;
    private $mailer;
    
    /**
     * EmailConsumer constructor.
     */
    public function __construct()
    {        
        // Setup RabbitMQ connection
        $host = $_ENV['RMQ_HOST'] ?? 'localhost';
        $port = (int)($_ENV['RMQ_PORT'] ?? 5672);
        $user = $_ENV['RMQ_USERNAME'] ?? 'guest';
        $password = $_ENV['RMQ_PASSWORD'] ?? 'guest';
        $vhost = $_ENV['RMQ_VHOST'] ?? '/';
        
        // Setup Mailer
        $dsn = $_ENV['MAILER_DSN'] ?? null;
        
        if (!$dsn) {
            $mailHost = $_ENV['MAILER_HOST'] ?? 'smtp.mailtrap.io';
            $mailPort = intval($_ENV['MAILER_PORT'] ?? 465);
            $mailUsername = $_ENV['MAILER_USERNAME'] ?? 'test';
            $mailPassword = $_ENV['MAILER_PASSWORD'] ?? 'test';
            
            $dsn = "smtp://{$mailUsername}:{$mailPassword}@{$mailHost}:{$mailPort}";
        }
        
        $transport = Transport::fromDsn($dsn);
        $this->mailer = new Mailer($transport);
        
        try {
            $this->connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
            $this->channel = $this->connection->channel();
            
            // Declare the queue
            $this->channel->queue_declare('email_queue', false, true, false, false);
            $this->log("Connected to RabbitMQ at {$host}:{$port}");
        } catch (\Exception $e) {
            $this->log("RabbitMQ connection error: {$e->getMessage()}", true);
            die('RabbitMQ connection error: ' . $e->getMessage());
        }
    }
    
    /**
     * Start consuming messages
     */
    public function consume(): void
    {        
        $this->log("Waiting for email messages. To exit press CTRL+C");
        
        $callback = function ($msg) {
            $data = json_decode($msg->body, true);
            $this->log("Received email request for {$data['to']}");
            
            try {
                $this->sendEmail($data);
                $this->log("Email sent successfully to {$data['to']}");
            } catch (\Exception $e) {
                $this->log("Failed to send email: {$e->getMessage()}", true);
            }
            
            $msg->ack();
        };
        
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume('email_queue', '', false, false, false, false, $callback);
        
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }
    
    /**
     * Send an email using the data from the queue
     *
     * @param array $data
     * @return void
     */
    private function sendEmail(array $data): void
    {        
        $fromEmail = $data['from_email'] ?? 'stock-api@example.com';
        $fromName = $data['from_name'] ?? 'Stock API';
        
        $email = (new Email())
            ->from(new Address($fromEmail, $fromName))
            ->to($data['to'])
            ->subject($data['subject'])
            ->html($data['body']);
        
        $this->mailer->send($email);
    }
    
    /**
     * Log a message with timestamp
     *
     * @param string $message
     * @param bool $isError
     * @return void
     */
    private function log(string $message, bool $isError = false): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $prefix = $isError ? "[ERROR]" : "[INFO]";
        echo "{$timestamp} {$prefix} {$message}\n";
    }
    
    /**
     * Close the connection when the object is destroyed
     */
    public function __destruct()
    {        
        if ($this->channel) {
            $this->channel->close();
        }
        
        if ($this->connection) {
            $this->connection->close();
        }
    }
}