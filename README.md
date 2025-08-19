# private-captcha-php

PHP client for server-side verification of Private Captcha solutions.

## Installation

```bash
composer require private-captcha/private-captcha-php
```

## Quick Start

```php
<?php

use PrivateCaptcha\Client;

// Initialize the client with your API key
$client = new Client(apiKey: "your-api-key-here");

// Verify a captcha solution
try {
    $result = $client->verify(solution: "user-solution-from-frontend");
    if ($result->success) {
        echo "Captcha verified successfully!";
    } else {
        echo "Verification failed: {$result}";
    }
} catch (Exception $e) {
    echo "Error: {$e->getMessage()}";
}
```

## Usage

### Web Framework Integration

#### Laravel Example

```php
<?php

use Illuminate\Http\Request;
use PrivateCaptcha\Client;
use PrivateCaptcha\Exceptions\SolutionException;

class FormController extends Controller
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(apiKey: config('services.privatecaptcha.key'));
    }

    public function submit(Request $request)
    {
        try {
            // Verify captcha from form data
            $this->client->verifyRequest($request->all());

            // Process your form data here
            return response('Form submitted successfully!');

        } catch (SolutionException $e) {
            return response('Captcha verification failed', 400);
        }
    }
}
```

#### Symfony Example

```php
<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PrivateCaptcha\Client;
use PrivateCaptcha\Exceptions\SolutionException;

class FormController
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(apiKey: $_ENV['PRIVATECAPTCHA_API_KEY']);
    }

    public function submit(Request $request): Response
    {
        try {
            $this->client->verifyRequest($request->request->all());
            // Process form data
            return new Response('Success!');
        } catch (SolutionException $e) {
            return new Response('Captcha failed', 400);
        }
    }
}
```

## Configuration

### Client Options

```php
<?php

use PrivateCaptcha\Client;

$client = new Client(
    apiKey: "your-api-key",
    domain: Client::EU_DOMAIN,                      // replace domain for self-hosting or EU isolation
    formField: "private-captcha-solution",          // custom form field name
    timeout: 10.0,                                  // request timeout in seconds
);
```

### Non-standard backend domains

```php
<?php

use PrivateCaptcha\Client;

// Use EU domain
$euClient = new Client(
    apiKey: "your-api-key",
    domain: Client::EU_DOMAIN  // api.eu.privatecaptcha.com
);

// Or specify custom domain in case of self-hosting
$customClient = new Client(
    apiKey: "your-api-key", 
    domain: "your-custom-domain.com"
);
```

### Retry Configuration

```php
<?php

$result = $client->verify(
    solution: "user-solution",
    maxBackoffSeconds: 15,  // maximum wait between retries
    attempts: 3             // number of retry attempts
);
```

## Requirements

- PHP 8.1+
- cURL extension
- JSON extension

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For issues with this PHP client, please open an issue on GitHub.
For Private Captcha service questions, visit [privatecaptcha.com](https://privatecaptcha.com).
