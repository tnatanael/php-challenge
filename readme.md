# PHP Challenge - Stock API

A REST API for tracking stock market values built with Slim Framework 4, PHP 8.2, and MySQL.

## Key Features Implemented

- **Stock Data Retrieval**: Real-time stock information from Stooq API
- **User Authentication**: JWT-based authentication system
- **Stock Query History**: Track and retrieve user's past stock queries
- **Email Notifications**: Asynchronous email notifications using RabbitMQ
- **API Documentation**: OpenAPI/Swagger documentation
- **Containerized**: Docker and Docker Compose for easy setup
- **Database Migrations**: Automated database setup and seeding
- **Unit Tests**: Comprehensive test coverage

## Table of Contents

- [Project Overview](#project-overview)
  - [Mandatory Features Implemented](#mandatory-features-implemented)
  - [Bonus Features Implemented](#bonus-features-implemented)
  - [Technology Stack](#technology-stack)
- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Setup Instructions](#setup-instructions)
- [Available Endpoints](#available-endpoints)
  - [Core API Endpoints (Challenge Requirements)](#core-api-endpoints-challenge-requirements)
  - [Additional Endpoints](#additional-endpoints)
  - [Authentication](#authentication)
- [Email System with RabbitMQ Integration](#email-system-with-rabbitmq-integration)
- [Stock API Features](#stock-api-features)
- [Database Migrations and Seeders](#database-migrations-and-seeders)
- [Running Tests](#running-tests)
- [Project Structure](#project-structure)
- [Development Guidelines](#development-guidelines)
- [Operational Information](#operational-information)

## Project Overview

This project implements a RESTful API for tracking stock market values, meeting all mandatory and bonus requirements of the PHP Challenge:

### Mandatory Features Implemented
- ✅ **SQL Database**: MySQL for users and stock query logs
- ✅ **Authentication System**: Secure user authentication
- ✅ **User Creation Endpoint**: API for creating new users
- ✅ **Stock Quote Endpoint**: Real-time stock data with email notifications
- ✅ **Query History Endpoint**: Track and retrieve user's past stock queries

### Bonus Features Implemented
- ✅ **Unit Tests**: Comprehensive test coverage
- ✅ **RabbitMQ Integration**: Asynchronous email processing
- ✅ **JWT Authentication**: Secure token-based API access
- ✅ **Docker Containerization**: Easy setup and deployment

### Technology Stack
- PHP 8.2 with strict typing and attribute-based annotations
- Slim Framework 4 for routing and middleware
- MySQL 8.0 with Eloquent ORM for data persistence
- RabbitMQ for message queuing
- Symfony Mailer for email delivery
- OpenAPI/Swagger for API documentation

## Getting Started

### Prerequisites

- Docker and Docker Compose installed on your system

### Quick Setup (2 minutes)

```bash
# 1. Clone the repository (or download it)
git clone https://github.com/yourusername/php-challenge.git
cd php-challenge

# 2. Create environment file
cp .env.sample .env

# 3. Start the application
docker-compose up -d
```

That's it! The application will:
- Start all necessary containers (PHP, MySQL, RabbitMQ)
- Run database migrations automatically
- Create a default user (email: user@example.com, password: user123)
- Start the email consumer service

### Accessing the Application

- **API Endpoints**: http://localhost:8080
- **API Documentation**: http://localhost:8080/swagger
- **RabbitMQ Management**: http://localhost:15672 (guest/guest)

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

## Email System with RabbitMQ Integration

The application implements an asynchronous email notification system using RabbitMQ, satisfying the bonus feature requirement:

### How It Works

1. **Message Queue Architecture**:
   - When a user requests stock information, the API sends the stock data to a RabbitMQ queue
   - A separate consumer process reads from the queue and sends emails asynchronously
   - This decouples the API request handling from email delivery, improving performance

2. **Implementation Components**:
   - `MessageQueue` service: Handles connection to RabbitMQ and message publishing
   - `EmailNotificationService`: Prepares email content and publishes to the queue
   - `EmailConsumer`: Background process that consumes messages and sends emails
   - `TemplateRenderer`: Renders HTML email templates with stock data

3. **Configuration**: Configure your email and RabbitMQ settings in the `.env` file:
   ```
   # Email Settings
   MAILER_DSN=smtp://username:password@smtp.example.com:587
   MAILER_FROM=your-email@example.com
   MAILER_FROM_NAME="Stock API"
   MAILER_ENABLED=1
   
   # RabbitMQ Settings
   RMQ_ENABLED=1  # Set to 1 to enable RabbitMQ
   RMQ_HOST=rabbitmq
   RMQ_VHOST=/
   RMQ_PORT=5672
   RMQ_USERNAME=guest
   RMQ_PASSWORD=guest
   ```

4. **Automatic Consumer**: The email consumer service runs automatically when the Docker container starts if RabbitMQ is enabled, processing any messages in the queue.

5. **Fallback Mechanism**: If RabbitMQ is disabled or unavailable, the system gracefully degrades without affecting the API functionality.

## Available Endpoints

### Core API Endpoints (Challenge Requirements)

- **User Creation**
  - `POST /users` - Creates a new user account
  - Protected by JWT authentication
  - Accepts email, password, and name

- **Stock Quote Retrieval**
  - `GET /stock?q={symbol}` - Returns real-time stock information
  - Protected by JWT authentication
  - Example: `/stock?q=AAPL.US` retrieves Apple Inc. stock data
  - Sends an email notification with stock data to the requesting user
  - Response format:
    ```json
    {
      "success": true,
      "message": "Stock quote retrieved successfully",
      "data": {
        "symbol": "AAPL.US",
        "name": "APPLE",
        "open": 123.66,
        "high": 123.66,
        "low": 122.49,
        "close": 123
      }
    }
    ```

- **Stock Query History**
  - `GET /history` - Returns the user's stock query history
  - Protected by JWT authentication
  - Returns queries in reverse chronological order (newest first)
  - Response format:
    ```json
    {
      "success": true,
      "message": "Stock query history retrieved successfully",
      "data": [
        {
          "date": "2023-04-01T19:20:30Z",
          "symbol": "AAPL.US",
          "name": "APPLE",
          "open": 123.66,
          "high": 123.66,
          "low": 122.49,
          "close": 123
        },
        // More history entries...
      ]
    }
    ```

### Additional Endpoints

- `GET /hello/{name}` - Public endpoint, returns a greeting message
- `GET /bye/{name}` - Protected endpoint, returns a goodbye message
- `GET /users` - Protected endpoint, returns all users
- `GET /users/{id}` - Protected endpoint, returns a specific user
- `PUT /users/{id}` - Protected endpoint, updates a user
- `DELETE /users/{id}` - Protected endpoint, deletes a user

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
     "success": true,
     "message": "Login successful",
     "data": {
       "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
       "user": {
         "id": 1,
         "name": "Test User",
         "email": "user@example.com"
       }
     }
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

These credentials are defined in the `.env` file as `DEFAULT_USERNAME` and `DEFAULT_PASSWORD`.

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

The project includes comprehensive PHPUnit tests for all endpoints and services, satisfying the bonus feature requirement for unit tests.

### Test Coverage

The test suite covers:

- **Authentication**: Login, JWT validation, and protected routes
- **Stock API**: Stock data retrieval and error handling
- **User Management**: User creation, retrieval, update, and deletion
- **History**: Stock query history storage and retrieval
- **Email System**: Email notification generation and queue integration

### Running Tests

All tests can be run within the Docker environment:

```bash
# Run all tests
docker-compose exec app vendor/bin/phpunit

# Run a specific test file
docker-compose exec app vendor/bin/phpunit tests/HelloTest.php

# Run a specific test method
docker-compose exec app vendor/bin/phpunit --filter testHelloEndpoint tests/HelloTest.php
```

### Code Coverage

The project is configured for code coverage reporting with Xdebug. To generate a coverage report:

```bash
docker-compose exec app vendor/bin/phpunit --coverage-html=coverage
```

The coverage report will be generated in the `coverage` directory. You can open `coverage/html/index.html` in your browser to view a detailed coverage report.

## Project Structure

The project follows a clean, modular architecture with clear separation of concerns:

### Core Components

- **Controllers** (`src/Controllers/`)
  - `AuthController.php` - Handles user authentication and JWT token generation
  - `StockController.php` - Manages stock data retrieval and history endpoints
  - `UserController.php` - Provides user management functionality

- **Services** (`src/Services/`)
  - `StockService.php` - Business logic for stock data operations
  - `StooqApiService.php` - Integration with the Stooq API
  - `EmailNotificationService.php` - Email notification handling
  - `MessageQueue.php` - RabbitMQ integration for async processing
  - `UserService.php` - User management operations

- **Models** (`src/Models/`)
  - Database entity models using Eloquent ORM

- **Middleware** (`src/Middleware/`)
  - `JwtMiddleware.php` - JWT authentication and validation

### Configuration and Setup

- **Application Config** (`app/`)
  - `routes.php` - API route definitions
  - `services.php` - Dependency injection container configuration

- **Database** (`src/Database/`)
  - Migration and seeding system for database setup
  - Automated schema creation and initial data seeding

- **API Documentation** (`src/OpenApi/`)
  - OpenAPI/Swagger definitions for API documentation

- **Tests** (`tests/`)
  - Comprehensive test suite for all components

## Development Guidelines

### Key Implementation Aspects

- **Docker Containerization**: The application is fully containerized with Docker, making it easy to set up and run in any environment.

- **Dependency Injection**: Services are registered in `app/services.php` and automatically injected where needed.

- **Database Migrations**: Automated database schema creation and seeding for quick setup.

- **API Documentation**: All endpoints are documented with OpenAPI annotations, accessible via Swagger UI.

- **Asynchronous Processing**: Email notifications are handled asynchronously via RabbitMQ.

- **JWT Authentication**: Secure API access with JWT tokens and middleware protection.

### Testing Strategy

The project follows a comprehensive testing approach with:

- **Unit Tests**: Testing individual components in isolation
- **Integration Tests**: Testing interactions between components
- **API Tests**: End-to-end testing of API endpoints

### Code Quality Standards

- **PSR Standards**: The codebase follows PSR-1, PSR-4, and PSR-12 coding standards
- **Type Hinting**: Strict type declarations are used throughout the codebase
- **Dependency Management**: Composer is used for managing dependencies

## Operational Information

### Container Management

```bash
# Rebuild containers after Dockerfile changes
docker-compose down
docker-compose up -d --build

# View logs from all containers
docker-compose logs -f
```

### Extending the Application

The application follows a modular architecture that makes it easy to extend:

- **New Endpoints**: Add routes in `app/routes.php` and implement controllers in `src/Controllers/`
- **New Features**: Create services in `src/Services/` and register them in `app/services.php`
- **Database Changes**: Add migrations in `src/Database/Schema/Migrations/` and run with `php console migrate`
- **New Tests**: Add test cases in `tests/` directory following the existing patterns

### Troubleshooting

- PHP error logs are available in the `php-errors.log` file in the project root
- Database connection issues can be resolved by checking the `.env` configuration
- RabbitMQ connection issues can be verified through the management interface at port 15672