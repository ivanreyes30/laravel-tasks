# Task manager

A simple Laravel 13 application with an Inertia 3 / React interface and MySQL persistence. Create, rename, and delete tasks; drag their handles to reorder them. Arrow buttons provide the same ordering controls for keyboard and touch users. Create projects and choose one from the dropdown to view its tasks.

Each task stores a name, project, numeric priority, and creation/update timestamps. New tasks go at the bottom. Reordering assigns priorities starting at 1 within the selected project; deleting a task closes the gap. Project row locks and transactions serialize ordering changes. Reorder requests must contain every current task exactly once, so stale or invalid lists cannot partially update priorities.

The task board is public and shared; no login is required. The original starter kit's account pages remain separate from the task board.

## Requirements

- PHP 8.3 or newer for Laravel; **PHP 8.5 is recommended for this repository's locked dependencies and Pest 5 development tools**. Run `composer check-platform-reqs` to verify your runtime.
- Composer 2.
- Node.js 22.12+ (or a supported newer Node.js version) and npm.
- MySQL 8+ with the PHP PDO MySQL extension. Standard Laravel PHP extensions must also be enabled.

## Local setup

1. Clone the repository and open a terminal in its directory.
2. Install dependencies and create the environment file:

    ```sh
    composer install
    npm ci
    cp .env.example .env
    php artisan key:generate --no-interaction
    ```

3. Create a MySQL database named `laravel_tasks` with your MySQL client. Set the connection credentials in `.env`:

    ```dotenv
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=laravel_tasks
    DB_USERNAME=your_database_user
    DB_PASSWORD=your_database_password
    ```

    If using an existing `.env` from the starter kit, replace its SQLite configuration with the settings above. Set `APP_NAME="Task manager"` and `APP_URL` to your local application's address.

4. Create the tables and optional Inbox project:

    ```sh
    php artisan migrate --seed --no-interaction
    npm run build
    ```

    The project seeder is repeatable and does not create demo users or tasks. You can also create projects directly in the browser.

5. Start Laravel and Vite using `composer run dev`, or run `php artisan serve` and `npm run dev` in separate terminals. Open the address printed by Laravel. The task board is at the application root.

## Checks

```sh
php artisan test --compact tests/Feature/TaskManagementTest.php
php artisan test --compact
npm run check
npm run types:check
vendor/bin/phpstan analyse
npm run build
```

Tests use an isolated, in-memory SQLite database, requiring the PDO SQLite extension. They cover task creation, editing, deletion, project filtering, priority normalization, project scoping, and rejection of invalid task orders. MySQL is used for the deployed application; SQLite tests do not verify MySQL concurrency semantics.

For manual browser verification, create two projects and multiple tasks, drag a task from the bottom to the top, refresh to check persistence, try the arrow controls, rename a task, and delete one. Verify contiguous priorities and that switching projects shows only that project's tasks.

## Deployment

Use a PHP-capable server with MySQL and the requirements above. Point the web server's document root to this repository's `public/` directory, route requests through `public/index.php`, and enable HTTPS. Give the PHP process write access to `storage/` and `bootstrap/cache/`.

1. Provision a MySQL database and a database user for the application.
2. Deploy the code, set up `.env`, and configure `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` to your HTTPS domain, `SESSION_SECURE_COOKIE=true`, and the MySQL credentials. Generate `APP_KEY` once for a new installation; preserve it on later deployments. Never commit `.env`.
3. Build the release:

    ```sh
    composer install --no-dev --optimize-autoloader
    npm ci
    npm run build
    php artisan migrate --force --no-interaction
    php artisan db:seed --class=ProjectSeeder --force --no-interaction
    php artisan optimize
    ```

    Alternatively, build frontend assets in CI and ship `public/build/` with the release. Node.js is then unnecessary on the runtime server. Do not run Vite's development server in production.

4. Reload your PHP process after deployment and open the application root. Check creation, editing, deletion, and persisted ordering against MySQL.

Back up the database before applying production migrations. For future releases preserve the environment file and application key, migrate, rebuild assets, and refresh the Laravel caches. No queue worker or scheduler is needed for task management.
