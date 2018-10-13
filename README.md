# Laravel Sportmonks Soccer API

Laravel website that uses [Sportmonks](https://www.sportmonks.com/sports/soccer) (live)score API calls. 
Documentation for the API can be found [here](https://www.sportmonks.com/sports/soccer)

## Installation

**1-** Require the laravel-sportmonks-soccer-package via Composer in your `composer.json`.
```json
{
  "require": {
    "kirill-latish/laravel-sportmonks-soccer": "^2.0"
  }
}
```

**2-** Run Composer to install or update the new requirement.

```bash
$ composer install
```

or

```bash
$ composer update
```

**3-** Add the service provider to your `app/config/app.php` file
```php
Sportmonks\SoccerAPI\SoccerAPIServiceProvider::class,
```

**4-** Add the facade to your `app/config/app.php` file
```php
'SoccerAPI' => Sportmonks\SoccerAPI\Facades\SoccerAPI::class,
```

**5-** Publish the configuration file

```bash
$ php artisan vendor:publish --provider="Sportmonks\SoccerAPI\SoccerAPIServiceProvider"
```

**6-** Review the configuration file and add your token (preferably through env: `'api_token' => env('API_TOKEN')` )

***If you don't have a API-token, you can get a free one [here](https://www.sportmonks.com/register) This should be enough to experiment with the code.***

```
config/soccerapi.php
```

**7-** Review the configuration file and add your timezone (preferably through config file: `'timezone' => config('app.timezone')` )

```
config/soccerapi.php
```

