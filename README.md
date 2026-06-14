# Laravel Sportmonks Website

Laravel website that uses the [Sportmonks](https://www.sportmonks.com/sports/soccer) football API for (live)scores.
API documentation can be found in the [Sportmonks Football API documentation](https://docs.sportmonks.com/football).

## Screenshots

[Gallery with screenshots](https://imgur.com/gallery/BlqvOwU)

## Requirements

- PHP 8.3+
- Composer
- A [Sportmonks API token](https://www.sportmonks.com/register) (free tier available)

## Installation

**1.** Install Composer dependencies.

```bash
composer install
```

**2.** Copy the example environment file and rename it to `.env`.

```bash
cp .env.example .env
```

**3.** Add your Sportmonks API token to `.env`.

```env
SPORTMONKS_FOOTBALL_API_TOKEN=your_token_here
```

> Don't have a token yet? Register for free at [sportmonks.com](https://www.sportmonks.com/register).

**4.** Generate the application key.

```bash
php artisan key:generate
```

**5.** Run the database migrations.

```bash
php artisan migrate
```

**6.** Initialise the preferences storage.

```bash
php artisan filebase:setup
```

**7.** Start the development server.

```bash
php artisan serve
```

**8.** If the application doesn't work as expected, try clearing the caches. If the problem persists, please open an issue.

```bash
php artisan config:clear
php artisan cache:clear
composer dump-autoload
php artisan view:clear
php artisan route:clear
```

## Special thanks to

[kirill-latish](https://github.com/kirill-latish/laravel-sportmonks-soccer)
