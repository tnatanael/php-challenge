<?php

declare(strict_types=1);

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MessageQueue
{
    private $connection;
    private $channel;
    private $enabled;
    
    /**
     * MessageQueue constructor.
     */
    public function __construct()
    {        
        $this->enabled = (bool)($_ENV['RMQ_ENABLED'] ?? false);
        
        if (!$this->enabled) {
            return;
        }
        
        $host = $_ENV['RMQ_HOST'] ?? 'localhost';
        $port = (int)($_ENV['RMQ_PORT'] ?? 5672);
        $user = $_ENV['RMQ_USERNAME'] ?? 'guest';
        $password = $_ENV['RMQ_PASSWORD'] ?? 'guest';
        $vhost = $_ENV['RMQ_VHOST'] ?? '/';
        
        try {
            $this->connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
            $this->channel = $this->connection->channel();
            
            // Declare queues
            $this->channel->queue_declare('email_queue', false, true, false, false);
        } catch (\Exception $e) {
            // Log the error but don't crash the application
            error_log('RabbitMQ connection error: ' . $e->getMessage());
        }
    }
    
    /**
     * Publish a message to the queue
     *
     * @param string $queue
     * @param array $data
     * @return bool
     */
    public function publish(string $queue, array $data): bool
    {        
        if (!$this->enabled || !$this->channel) {
            return false;
        }
        
        try {
            $message = new AMQPMessage(json_encode($data), [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]);
            
            $this->channel->basic_publish($message, '', $queue);
            return true;
        } catch (\Exception $e) {
            error_log('Failed to publish message: ' . $e->getMessage());
            return false;
        }
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