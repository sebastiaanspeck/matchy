[![Codacy Badge](https://api.codacy.com/project/badge/Grade/1e92b8e1d53f40cc9b92f9ebcf8aa8b9)](https://app.codacy.com/app/shem-speck/sportmonks?utm_source=github.com&utm_medium=referral&utm_content=sebastiaanspeck/sportmonks&utm_campaign=Badge_Grade_Settings)
[![BCH compliance](https://bettercodehub.com/edge/badge/sebastiaanspeck/sportmonks?branch=master)](https://bettercodehub.com/)

# Laravel Sportmonks Website

Laravel website that uses [Sportmonks](https://www.sportmonks.com/sports/soccer) (live)score API calls. 
Documentation for the API can be found [here](https://www.sportmonks.com/sports/soccer)

## Installation

**1-** Require the laravel-sportmonks-soccer-package via Composer.
```bash
$ composer require kirill-latish/laravel-sportmonks-soccer
```

**2-** Run Composer to install or update the new requirement.

```bash
$ composer install
```

or

```bash
$ composer update
```

**3-** Publish the configuration file

```bash
$ php artisan vendor:publish --provider="Sportmonks\SoccerAPI\SoccerAPIServiceProvider"
```

**4-** Review the configuration file and add your token (preferably through env: `'api_token' => env('API_TOKEN')` )

***If you don't have a API-token, you can get a free one [here](https://www.sportmonks.com/register) This should be enough to experiment with the code.***

```
config/soccerapi.php
```

**5-** Review the configuration file and change the `'without_data' => 'false' to `true`:

```
config/soccerapi.php
```

**6-** Review the configuration file and add your timezone (preferably through config file: `'timezone' => config('app.timezone')` )

```
config/soccerapi.php
```

**7--** Copy the file [SoccerAPIClient.php](https://github.com/sebastiaanspeck/sportmonks/blob/master/soccerapi/SoccerAPIClient.php) from `soccerapi-source` to `vendor/kirill-latish/laravel-sportmonks-soccer/src-Sportmonks/SoccerAPI` and overwrite the existing `SoccerAPIClient.php`

## Special thanks to
[kirill-latish](https://github.com/kirill-latish/laravel-sportmonks-soccer)
