# Contributing

Thanks for your interest in contributing to **Internship Ratings** (تقييم التدريب)!

## Getting Started

1. Fork the repository and clone it locally.
2. Copy the environment file and install dependencies:

   ```bash
   cp .env.example .env
   composer install
   npm install
   ```

3. Generate an application key and run migrations:

   ```bash
   php artisan key:generate
   php artisan migrate
   ```

4. Start the development server:

   ```bash
   composer dev
   ```

## Development Workflow

1. Create a branch from `main` for your changes.
2. Make your changes and ensure tests pass:

   ```bash
   composer test
   ```

3. Run the linter to fix code style:

   ```bash
   composer lint
   ```

4. Commit your changes with a clear message and open a pull request.

## Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) for PHP code formatting. Run `composer lint` before committing.

## Reporting Issues

Please use the [issue templates](https://github.com/saad5400/internship-ratings/issues/new/choose) when reporting bugs or requesting features.
