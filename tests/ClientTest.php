<?php

declare(strict_types=1);

namespace PrivateCaptcha\Tests;

use PHPUnit\Framework\TestCase;
use PrivateCaptcha\Client;
use PrivateCaptcha\Enums\VerifyCode;
use PrivateCaptcha\Exceptions\ApiKeyException;
use PrivateCaptcha\Exceptions\HttpException;
use PrivateCaptcha\Exceptions\SolutionException;
use PrivateCaptcha\Exceptions\VerificationFailedException;

class ClientTest extends TestCase
{
    private const SOLUTIONS_COUNT = 16;
    private const SOLUTION_LENGTH = 8;

    private static ?string $cachedPuzzle = null;
    private string $apiKey;

    protected function setUp(): void
    {
        $envKey = getenv('PC_API_KEY');
        $this->apiKey = $envKey !== false ? $envKey : '';
        if (empty($this->apiKey)) {
            $this->fail('PC_API_KEY environment variable not set');
        }
    }

    private function fetchTestPuzzle(): string
    {
        if (self::$cachedPuzzle !== null) {
            return self::$cachedPuzzle;
        }

        $puzzleUrl = 'https://api.privatecaptcha.com/puzzle?sitekey=aaaaaaaabbbbccccddddeeeeeeeeeeee';

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $puzzleUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Origin: not.empty'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false || $error !== '' || $httpCode !== 200) {
            $this->fail("Failed to fetch test puzzle: HTTP {$httpCode}, cURL error: {$error}");
        }

        // At this point we know $response is string, not false
        assert(is_string($response));

        self::$cachedPuzzle = $response;
        return $response;
    }

    public function testStubPuzzle(): void
    {
        $puzzle = $this->fetchTestPuzzle();
        $client = new Client($this->apiKey);

        // Create empty solutions
        $emptySolutionsBytes = str_repeat("\0", self::SOLUTIONS_COUNT * self::SOLUTION_LENGTH);
        $solutionsStr = base64_encode($emptySolutionsBytes);
        $payload = $solutionsStr . '.' . $puzzle;

        $output = $client->verify($payload);

        // Should succeed but indicate test property error
        $this->assertTrue($output->success);
        $this->assertEquals(VerifyCode::TEST_PROPERTY_ERROR, $output->code);
    }

    public function testVerifyError(): void
    {
        $puzzle = $this->fetchTestPuzzle();
        $client = new Client($this->apiKey);

        // Create malformed solutions (half the expected length)
        $malformedSolutionsBytes = str_repeat("\0", (self::SOLUTIONS_COUNT * self::SOLUTION_LENGTH) / 2);
        $solutionsStr = base64_encode($malformedSolutionsBytes);
        $payload = $solutionsStr . '.' . $puzzle;

        $this->expectException(HttpException::class);

        try {
            $client->verify($payload);
        } catch (HttpException $e) {
            $this->assertEquals(400, $e->statusCode);
            throw $e;
        }
    }

    public function testVerifyEmptySolution(): void
    {
        $client = new Client($this->apiKey);

        $this->expectException(SolutionException::class);
        $client->verify('');
    }

    public function testRetryBackoff(): void
    {
        // Use a non-existent domain to trigger retries
        $client = new Client(
            apiKey: $this->apiKey,
            domain: '192.0.2.1:9999', // Test IP that should be unreachable
            timeout: 1.0,
        );

        // This should fail after multiple attempts
        $this->expectException(VerificationFailedException::class);

        try {
            $client->verify(solution: 'asdf', maxBackoffSeconds: 1, attempts: 4);
        } catch (VerificationFailedException $e) {
            // Should have failed after 4 attempts
            $this->assertEquals(4, $e->attempts);
            throw $e;
        }
    }

    public function testApiKeyValidation(): void
    {
        $this->expectException(ApiKeyException::class);
        new Client('');
    }

    public function testVerifyRequestSuccess(): void
    {
        $this->expectNotToPerformAssertions();

        $puzzle = $this->fetchTestPuzzle();
        $client = new Client($this->apiKey);

        // Create form data with empty solutions (will trigger test property error)
        $emptySolutionsBytes = str_repeat("\0", self::SOLUTIONS_COUNT * self::SOLUTION_LENGTH);
        $solutionsStr = base64_encode($emptySolutionsBytes);
        $payload = $solutionsStr . '.' . $puzzle;

        $formData = [Client::DEFAULT_FORM_FIELD => $payload];

        // This should not raise an exception for test property (it's considered "success")
        $client->verifyRequest($formData);
    }

    public function testVerifyRequestFailure(): void
    {
        $client = new Client($this->apiKey);

        // Test with malformed data
        $formData = [Client::DEFAULT_FORM_FIELD => 'invalid-solution'];

        $this->expectException(HttpException::class);

        try {
            $client->verifyRequest($formData);
            // @phpstan-ignore-next-line
        } catch (HttpException $e) {
            $this->assertEquals(400, $e->statusCode);
            throw $e;
        }
    }

    public function testCustomFormField(): void
    {
        $customFormField = 'custom-field';
        $puzzle = $this->fetchTestPuzzle();
        $client = new Client(apiKey: $this->apiKey, formField: $customFormField);

        $emptySolutionsBytes = str_repeat("\0", self::SOLUTIONS_COUNT * self::SOLUTION_LENGTH);
        $solutionsStr = base64_encode($emptySolutionsBytes);
        $payload = $solutionsStr . '.' . $puzzle;

        // Should look for the custom field name
        $formData = [$customFormField => $payload];

        // Should work with custom form field
        $client->verifyRequest($formData);

        // Test that default client fails with custom field data
        $defaultClient = new Client($this->apiKey);
        $this->expectException(SolutionException::class);
        $defaultClient->verifyRequest($formData);
    }

    public function testEUDomain(): void
    {
        $client = new Client(apiKey: $this->apiKey, domain: Client::EU_DOMAIN);

        $reflection = new \ReflectionClass($client);
        $endpointProperty = $reflection->getProperty('endpoint');
        $endpointProperty->setAccessible(true);
        $endpoint = $endpointProperty->getValue($client);

        $this->assertStringContainsString('api.eu.privatecaptcha.com', $endpoint);
    }
}
