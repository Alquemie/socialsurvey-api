# Social Survey API PHP Wrapper

This is a simple PHP Wrapper for the Social Survey API services.

## Requirements

depends on PHP 5.4+, Guzzle 6+.

##Installation

Add ``alquemie/socialsurvey-api`` as a require dependency in your ``composer.json`` file:

```sh
php composer.phar require alquemie/socialsurvey-api:0.1.0
```

## Usage

```php
use SocialSurveyApi\SocialSurveyApiClient;

$client = new SocialSurveyApiClient('socialsurvey-key');
```

Make requests with a specific API call method:

```php
// Run surveys
$response = $client->execute(
    'surveys', 
    [
        'user' => 'user@email.com'
    ]
);
```

Any Social Survey API call will work. Valid methods are:

- surveys
- surveycount


## License

GPL3 or later license.
