# Private Captcha for PHP

[![CI](https://github.com/PrivateCaptcha/private-captcha-php/actions/workflows/ci.yml/badge.svg)](https://github.com/PrivateCaptcha/private-captcha-php/actions)
[![Packagist Version](https://img.shields.io/packagist/v/private-captcha/private-captcha-php)](https://packagist.org/packages/private-captcha/private-captcha-php)

PHP client for server-side verification of Private Captcha solutions.

<mark>Please check the [official documentation](https://docs.privatecaptcha.com/docs/integrations/php/) for the in-depth and up-to-date information.</mark>

## Quick Start

- Install private captcha package using composer
  ```bash
  composer require private-captcha/private-captcha-php
  ```
- Verify captcha solution using `Client` class and `verify()` method
    ```php
    <?php
    
    use PrivateCaptcha\Client;
    
    // Initialize the client with your API key
    $client = new Client(apiKey: "your-api-key-here");
    
    // Verify a captcha solution
    try {
        $result = $client->verify(solution: "user-solution-from-frontend");
        if ($result->isOK()) {
            echo "Captcha verified successfully!";
        } else {
            echo "Verification failed: {$result}";
        }
    } catch (Exception $e) {
        echo "Error: {$e->getMessage()}";
    }
    ```
- Use with Laravel or Symphony using `$client->verifyRequest()` helper

## Requirements

- PHP 8.1+
- cURL extension
- JSON extension

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For issues with this PHP client, please open an issue on GitHub.
For Private Captcha service questions, visit [privatecaptcha.com](https://privatecaptcha.com).
