# PHP Challenge - Stock API

A REST API for tracking stock market values built with Slim Framework 4, PHP 8.2, and MySQL.

## Table of Contents

- [Project Overview](#project-overview)
- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Setup Instructions](#setup-instructions)
- [API Documentation](#api-documentation)
- [Stock API Features](#stock-api-features)
- [Email System](#email-system)
- [Available Endpoints](#available-endpoints)
  - [Authentication](#authentication)
- [Database Migrations and Seeders](#database-migrations-and-seeders)
- [Running Tests](#running-tests)
  - [Running Tests with Code Coverage](#running-tests-with-code-coverage)
- [Project Structure](#project-structure)
- [Development](#development)
- [Contributing](#contributing)

## Project Overview

This project is a RESTful API that provides endpoints for tracking stock market values. It's built using:

- PHP 8.2
- Slim Framework 4
- MySQL 8.0
- Docker and Docker Compose
- OpenAPI/Swagger for API documentation

## Getting Started

### Prerequisites

- Docker and Docker Compose installed on your system
- Git (optional, for cloning the repository)

### Setup Instructions

1. Clone or download this repository to your local machine

2. Create a `.env` file in the project root (you can copy from `.env.sample`)

   ```bash
   cp .env.sample .env
   ```

3. Build and start the Docker containers

   ```bash
   docker-compose up -d
   ```

4. The API will be available at http://localhost:8080

## API Documentation

This project uses OpenAPI/Swagger for API documentation. You can access the documentation at:

- JSON format: http://localhost:8080/api/documentation
- Swagger UI: http://localhost:8080/swagger

## Stock API Features

The Stock API provides the following features:

1. **Stock Data Retrieval**: Query real-time stock information using the `/stock?q={symbol}` endpoint
   - Example: `/stock?q=AAPL.US` retrieves Apple Inc. stock data
   - Returns data including symbol, name, open, high, low, and close values
   - Data is sourced from the [Stooq API](https://stooq.com/), a free financial data provider

2. **Stock Query History**: View your past stock queries using the `/history` endpoint
   - Returns a list of all stock queries made by the authenticated user
   - Results are ordered by most recent first

3. **Data Persistence**: All stock queries are saved to the database for historical tracking
   - Each query is associated with the authenticated user
   - Includes timestamp information for when the query was made
   - Database schema includes:
     - `id`: Unique identifier for the query
     - `user_id`: Foreign key to the users table
     - `symbol`: Stock symbol (e.g., 'AAPL.US')
     - `name`: Company name (e.g., 'APPLE')
     - `open`: Opening price
     - `high`: Highest price
     - `low`: Lowest price
     - `close`: Closing price
     - `created_at`: When the query was made
     - `updated_at`: When the record was last updated

4. **Symbol Format**: Stock symbols should be provided in the format used by Stooq
   - US stocks: Add `.US` suffix (e.g., `AAPL.US`, `MSFT.US`)
   - For other markets, check the [Stooq website](https://stooq.com/) for the correct symbol format

5. **Response Format**:
   - All API responses follow a consistent JSON format
   - Success responses include `success: true`, `message`, and `data` fields
   - Error responses include `success: false`, `message`, and `error` fields
   - Stock data includes: symbol, name, open, high, low, and close values

6. **Error Handling**:
   - Missing symbol parameter: Returns 400 Bad Request
   - Invalid symbol or no data available: Returns 404 Not Found
   - Successful requests: Returns 200 OK with stock data

7. **Security Considerations**:
   - All stock API endpoints are protected with JWT authentication
   - Users can only access their own stock query history
   - API requests are validated to prevent injection attacks
   - Sensitive operations require a valid JWT token

## Email System

The application includes an email system that uses RabbitMQ for asynchronous email processing:

1. **Email Configuration**: Configure your email settings in the `.env` file:
   ```
   # Email Settings
   MAILER_DSN=smtp://username:password@smtp.example.com:587
   MAILER_FROM=your-email@example.com
   MAILER_FROM_NAME="Stock API"
   MAILER_ENABLED=1
   ```

2. **RabbitMQ Integration**: The system uses RabbitMQ to queue and process emails asynchronously:
   ```
   # RabbitMQ Settings
   RMQ_ENABLED=1  # Set to 1 to enable RabbitMQ
   RMQ_HOST=rabbitmq
   RMQ_VHOST=/
   RMQ_PORT=5672
   RMQ_USERNAME=guest
   RMQ_PASSWORD=guest
   ```

3. **Email Consumer**: The application includes an email consumer service that processes emails from the queue. This service runs automatically when the Docker container starts if RabbitMQ is enabled.

4. **Stock API Email Notifications**: When users query stock information, the system can send email notifications with the stock data. The emails are queued in RabbitMQ and processed asynchronously by the email consumer service.

## Available Endpoints

### Public Endpoints

- `GET /hello/{name}` - Returns a greeting message
- `POST /auth/login` - Authenticates a user and returns a JWT token

### Protected Endpoints (JWT Authentication)

- `GET /bye/{name}` - Returns a goodbye message
- `GET /stock?q={symbol}` - Returns stock information for the specified symbol
- `GET /history` - Returns the user's stock query history

### Authentication

The API uses JWT (JSON Web Token) authentication for protected endpoints. To access protected routes:

1. First, obtain a JWT token by authenticating with the login endpoint:
   ```
   POST /auth/login
   {
     "email": "user@example.com",
     "password": "user123"
   }
   ```

2. The response will contain a JWT token:
   ```json
   {
     "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
   }
   ```

3. Include this token in the Authorization header for protected endpoints:
   ```
   Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
   ```

#### Default User

A default admin user is automatically created during the first application startup if no users exist in the database:

- Email: `user@example.com`
- Password: `user123`

These credentials are defined in the `.env` file as `ADMIN_USERNAME` and `ADMIN_PASSWORD`.

## Database Migrations and Seeders

The project includes a robust database migration and seeding system that allows you to create, run, rollback, and refresh database migrations, as well as seed your database with initial data.

### Using the Migration CLI

The project provides a convenient CLI script for managing migrations:

```bash
# Make the migrate script executable (if needed)
chmod +x migrate

# Run all pending migrations
./migrate run

# Rollback all migrations
./migrate rollback

# Refresh all migrations (rollback then run)
./migrate refresh
```

You can also run these commands inside the Docker container:

```bash
docker-compose exec app ./migrate run
```

### Creating New Migrations

To create a new migration:

1. Create a new PHP class in the `src/Database/Schema/Migrations` directory
2. Implement the `App\Database\Schema\Migration` interface
3. Define the `up()` and `down()` methods to create and drop your database structures
4. Implement the `getName()` method to return a unique name for your migration

#### Handling Migration Dependencies

If your migrations have dependencies (e.g., foreign key constraints), you should explicitly register them in the correct order in `MigrateCommand.php`. For example:

```php
// In MigrateCommand.php
$migrationRunner = new MigrationRunner();

// Register migrations in the correct order
$migrationRunner->register(CreateMigrationsTable::class);
$migrationRunner->register(CreateUsersTable::class);       // Parent table
$migrationRunner->register(CreateStockQueriesTable::class); // Child table with foreign key

// Then scan for any other migrations
$migrationRunner->scanMigrations(__DIR__ . '/../../Schema/Migrations');
```

Example migration class:

```php
<?php

declare(strict_types=1);

namespace App\Database\Schema\Migrations;

use App\Database\Schema\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateExampleTable implements Migration
{
    public static function getName(): string
    {
        return 'create_example_table';
    }

    public static function up(): void
    {
        Capsule::schema()->create('examples', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public static function down(): void
    {
        Capsule::schema()->dropIfExists('examples');
    }
}
```

### How Migrations Work

The migration system:

1. Creates a `migrations` table to track executed migrations
2. Only runs migrations that haven't been executed yet
3. Automatically scans the `Migrations` directory for new migration classes
4. Provides detailed output during the migration process

> **Important Note**: Migrations do not follow a specific order when running automatically. If you have tables with foreign key dependencies, make sure to explicitly register dependent tables in the correct order in `MigrateCommand.php` before running migrations.

### Automatic Migrations

Migrations run automatically when the Docker container starts. This ensures your database schema is always up-to-date when you deploy or restart the application.

### Database Seeders

Seeders allow you to populate your database with initial or test data. The project includes a seeding system that works alongside migrations.

#### Default User Seeder

The application includes a `UserSeeder` that automatically creates a default admin user if no users exist in the database. This ensures you always have access to the system, even on a fresh installation.

#### Creating New Seeders

To create a new seeder:

1. Create a new PHP class in the `src/Database/Schema/Seeders` directory
2. Implement the `App\Database\Schema\Seeder` interface
3. Define the `run()` method to insert your data
4. Implement the `getName()` method to return a unique name for your seeder

Example seeder class:

```php
<?php

declare(strict_types=1);

namespace App\Database\Schema\Seeders;

use App\Database\Schema\Seeder;
use App\Models\ExampleModel;

class ExampleSeeder implements Seeder
{
    public static function getName(): string
    {
        return 'example_seeder';
    }

    public static function run(): void
    {
        // Check if data already exists
        if (ExampleModel::count() > 0) {
            return;
        }

        // Create example data
        ExampleModel::create([
            'name' => 'Example Data',
            // other fields...
        ]);
    }
}
```

#### Running Seeders

Seeders run automatically after migrations when the Docker container starts. You can also run them manually using the migration CLI:

```bash
# Run all seeders
./migrate seed
```

## Running Tests

The project includes PHPUnit tests that can be run within the Docker environment.

### Running All Tests

```bash
docker-compose exec app vendor/bin/phpunit
```

### Running a Specific Test File

```bash
docker-compose exec app vendor/bin/phpunit tests/HelloTest.php
```

### Running Tests with Code Coverage

To generate a code coverage report, you'll need to install Xdebug in your Docker environment first. Add the following to your Dockerfile:

```dockerfile
# Install Xdebug for code coverage
RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
```

Then rebuild your Docker container:

```bash
docker-compose down
docker-compose build app
docker-compose up -d
```

Update your phpunit.xml file to include coverage configuration:

```xml
<coverage>
    <include>
        <directory suffix=".php">src</directory>
    </include>
    <report>
        <html outputDirectory="coverage/html"/>
        <clover outputFile="coverage/clover.xml"/>
        <text outputFile="php://stdout" showUncoveredFiles="false"/>
    </report>
</coverage>
```

Now you can run tests with code coverage:

```bash
docker-compose exec app vendor/bin/phpunit --coverage-html=coverage
```

The coverage report will be generated in the `coverage` directory. You can open `coverage/html/index.html` in your browser to view a detailed coverage report.

### Running a Specific Test Method

```bash
docker-compose exec app vendor/bin/phpunit --filter testHelloEndpoint tests/HelloTest.php
```

Available test methods:
- `testHelloEndpoint` - Tests the public hello endpoint
- `testByeEndpointThrowsUnauthorized` - Tests that the bye endpoint requires authentication
- `testByeEndpointWithBasicAuth` - Tests the bye endpoint with valid authentication

## Project Structure

- `app/` - Application configuration and setup
  - `routes.php` - API route definitions
  - `services.php` - Dependency injection container configuration
  - `auth.php` - Authentication middleware setup
- `public/` - Web server entry point
  - `index.php` - Application entry point
- `src/Database/Schema/` - Database migration and seeding system
  - `Migration.php` - Migration interface
  - `MigrationRunner.php` - Migration execution engine
  - `Migrations/` - Directory containing migration files
  - `Seeder.php` - Seeder interface
  - `SeederRunner.php` - Seeder execution engine
  - `Seeders/` - Directory containing seeder files
  - `Commands/MigrateCommand.php` - Migration and seeder command implementation
  - `swagger-ui.php` - Swagger UI configuration
- `src/` - Application source code
  - `Controllers/` - API endpoint controllers
    - `AuthController.php` - Authentication controller for JWT login
    - `HelloController.php` - Example controller with protected and public routes
    - `UserController.php` - User management controller
  - `Middleware/` - Application middleware
    - `JwtMiddleware.php` - JWT authentication middleware
  - `Models/` - Database models
  - `OpenApi/` - OpenAPI documentation definitions
- `tests/` - PHPUnit tests

## Development

### Rebuilding Containers

If you make changes to the Dockerfile or docker-compose.yml, rebuild the containers:

```bash
docker-compose down
docker-compose up -d --build
```

### Accessing Logs

View logs from the containers:

```bash
docker-compose logs -f
```

PHP error logs are available in the `php-errors.log` file in the project root.

## Contributing

Contributions to this project are welcome. Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests to ensure they pass
5. Commit your changes (`git commit -m 'Add some amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

Please ensure your code follows the existing coding style and includes appropriate tests.