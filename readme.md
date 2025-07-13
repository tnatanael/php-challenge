# PHP Challenge - Stock API

A REST API for tracking stock market values built with Slim Framework 4, PHP 8.2, and MySQL.

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

## Available Endpoints

### Public Endpoints

- `GET /hello/{name}` - Returns a greeting message
- `POST /auth/login` - Authenticates a user and returns a JWT token

### Protected Endpoints (JWT Authentication)

- `GET /bye/{name}` - Returns a goodbye message

### Authentication

The API uses JWT (JSON Web Token) authentication for protected endpoints. To access protected routes:

1. First, obtain a JWT token by authenticating with the login endpoint:
   ```
   POST /auth/login
   {
     "email": "admin@example.com",
     "password": "secret"
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

- Email: `admin@example.com`
- Password: `secret`

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