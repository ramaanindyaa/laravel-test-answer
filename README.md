# Laravel Post Management System - Implementation Guide

## Overview

This project implements a RESTful API for a Post model using Laravel 12, supporting drafts, scheduled publishing, and authenticated operations. The system allows users to create, read, update, and delete blog posts with advanced publishing controls.

## Features Implemented

### Post Status Management

- **Draft Posts**: Posts can be saved as drafts that aren't publicly visible
- **Scheduled Posts**: Posts can be scheduled to publish at a future date/time
- **Published Posts**: Actively published and publicly visible posts
- **Auto Publishing**: Scheduled posts automatically become published when their publish date arrives

### Authentication & Authorization

- Session-based authentication using Laravel's built-in authentication system
- Authorization rules ensuring only post authors can edit/delete their own posts
- Public routes for viewing published posts
- Protected routes requiring authentication for creating/updating posts

## Setup Instructions

### Prerequisites

- PHP 8.3+
- Composer
- SQLite support
- Node.js v22.15.0+ (optional, for frontend assets)

### Installation

1. **Clone the repository**

    ```bash
    git clone https://github.com/your-username/laravel-skill-test.git
    cd laravel-skill-test
    ```

2. **Install PHP dependencies**

    ```bash
    composer install
    ```

3. **Set up the environment file**

    ```bash
    cp .env.example .env
    ```

    Update the `.env` file with your database and app configuration.

4. **Generate application key**

    ```bash
    php artisan key:generate
    ```

5. \*\*Run database migrations and seeders

    ```bash
    php artisan migrate --seed
    ```

6. **Install Node.js dependencies and build assets** (optional)
    ```bash
    npm install
    npm run dev
    ```

### Usage

- **Run the development server**

    ```bash
    php artisan serve
    ```

    Access the API at `http://localhost:8000`.

- **API Documentation**
  Refer to the Postman collection included in the repository for detailed API documentation.

## Testing

Feature tests are included to verify the functionality of the posts routes. To run the tests, use the following command:

```bash
php artisan test
```

## Acknowledgments

- Laravel 12 Documentation: https://laravel.com/docs/12.x
- Laravel GitHub Repository: https://github.com/laravel/laravel
- Postman for API testing and documentation
