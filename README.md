[![Codacy Badge](https://api.codacy.com/project/badge/Grade/58d7eedeb5694fda93ceb2240308b54e)](https://app.codacy.com/app/shem-speck/matchy?utm_source=github.com&utm_medium=referral&utm_content=sebastiaanspeck/matchy&utm_campaign=Badge_Grade_Dashboard)
[![BCH compliance](https://bettercodehub.com/edge/badge/sebastiaanspeck/sportmonks?branch=master)](https://bettercodehub.com/)
[![Build Status](https://travis-ci.com/sebastiaanspeck/matchy.svg?branch=master)](https://travis-ci.com/sebastiaanspeck/matchy)

# Laravel Sportmonks Website

Laravel website that uses [Sportmonks](https://www.sportmonks.com/sports/soccer) (live)score API calls. 
Documentation for the API can be found [here](https://www.sportmonks.com/sports/soccer)

## Screenshots
[Gallery with screenshots](https://imgur.com/gallery/BlqvOwU)

## Installation

**1-** Run Composer to install requirements.

```bash
$ composer install
```

**2-** Publish the configuration file

```bash
$ php artisan vendor:publish --provider="Sportmonks\SoccerAPI\SoccerAPIServiceProvider"
```

**3-** Make a copy of .env.example and rename to .env

**4-** Add your API token in the .env file to `SPORTMONKS_API_TOKEN`

***If you don't have a API-token, you can get a free one [here](https://www.sportmonks.com/register) This should be enough to experiment with the code.***

**5-** Review the configuration file and change the `'without_data' => 'false'` to `'without_data' => 'true'`:

```
config/soccerapi.php
```

**6-** Run `php artisan key:generate` to overcome the next [problem](https://stackoverflow.com/questions/44839648/no-application-encryption-key-has-been-specified-new-laravel-app)

**7-** Run `php artisan serve` to use the application

**8-** If the application doesn't work as expected, run the following commands. If the problem still exists, open a issue. 
```
php artisan config:clear
php artisan cache:clear
composer dump-autoload
php artisan view:clear
php artisan route:clear
```

## Special thanks to
[kirill-latish](https://github.com/kirill-latish/laravel-sportmonks-soccer)
