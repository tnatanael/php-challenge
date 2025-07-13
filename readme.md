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

### Protected Endpoints (Basic Authentication)

- `GET /bye/{name}` - Returns a goodbye message

### Authentication

Protected endpoints require Basic Authentication. Use the following credentials for testing:

- Username: `root`
- Password: `secret`

These credentials are defined in the `.env` file as `ADMIN_USERNAME` and `ADMIN_PASSWORD`.

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
  - `swagger-ui.php` - Swagger UI configuration
- `src/` - Application source code
  - `Controllers/` - API endpoint controllers
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