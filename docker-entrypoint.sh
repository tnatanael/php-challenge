#!/bin/bash
set -e

# Change to the correct directory
cd /var/www/html

# Parse .env file
if [ -f .env ]; then
  echo "Loading environment variables from .env file"
  export $(grep -v '^#' .env | xargs)
fi

# Create logs directory if it doesn't exist
mkdir -p ./logs
chmod 777 ./logs

# Run composer update
composer update

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
MAX_TRIES=30
COUNT=0
while [ $COUNT -lt $MAX_TRIES ]; do
    if mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1" >/dev/null 2>&1; then
        echo "MySQL is ready!"
        break
    fi
    echo "MySQL is not ready yet. Waiting..."
    sleep 2
    COUNT=$((COUNT+1))
done

if [ $COUNT -eq $MAX_TRIES ]; then
    echo "Error: MySQL did not become ready in time."
    exit 1
fi

# Run migrations
php ./migrate run

# Wait for RabbitMQ to be ready if enabled
# Skip the port check since Docker Compose already waited for RabbitMQ to be healthy
echo "RabbitMQ should be ready (Docker Compose healthcheck passed)"

# Additional connection verification
MAX_RETRIES=15
RETRY_COUNT=0
while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if (echo > /dev/tcp/rabbitmq/5672) &>/dev/null; then
        echo "RabbitMQ connection verified"
        break
    fi
    echo "RabbitMQ not ready - retrying... (Attempt $((RETRY_COUNT+1))/$MAX_RETRIES))"
    RETRY_COUNT=$((RETRY_COUNT+1))
    sleep 2
done

# Start the email consumer in the background
echo "Starting email consumer in background..."
touch ./logs/consumer.log  # Ensure the log file exists
chmod 666 ./logs/consumer.log  # Make it writable
nohup php ./email-consumer.php > ./logs/consumer.log 2>&1 &
CONSUMER_PID=$!
echo "Email consumer started with PID $CONSUMER_PID"

# Check if the consumer is actually running after a short delay
sleep 2
if ps -p $CONSUMER_PID > /dev/null; then
    echo "Email consumer is running successfully."
else
    echo "Warning: Email consumer may have failed to start. Check logs/consumer.log for details."
    cat ./logs/consumer.log
fi

# Start PHP-FPM
exec php-fpm