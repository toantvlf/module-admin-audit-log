<?php
declare(strict_types=1);

namespace TVTCommerce\AdminAuditLog\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use TVTCommerce\AdminAuditLog\Model\ParamSanitizer;

final class ParamSanitizerTest extends TestCase
{
    private ParamSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new ParamSanitizer();
    }

    public function testNonSensitiveKeysPassThroughUnchanged(): void
    {
        $result = $this->sanitizer->sanitize([
            'sku' => 'ABC-123',
            'qty' => 5,
            'name' => 'Test Product',
        ]);

        self::assertSame([
            'sku' => 'ABC-123',
            'qty' => 5,
            'name' => 'Test Product',
        ], $result);
    }

    public function testRedactsExactPasswordKey(): void
    {
        $result = $this->sanitizer->sanitize(['password' => 'hunter2']);

        self::assertSame(ParamSanitizer::REDACTED_VALUE, $result['password']);
    }

    /**
     * @dataProvider sensitiveKeyProvider
     */
    public function testRedactsKeysMatchingSensitiveNeedlesCaseInsensitively(string $key): void
    {
        $result = $this->sanitizer->sanitize([$key => 'some-secret-value']);

        self::assertSame(ParamSanitizer::REDACTED_VALUE, $result[$key]);
    }

    public static function sensitiveKeyProvider(): array
    {
        return [
            'password' => ['password'],
            'new_password mixed case' => ['New_Password'],
            'api_key' => ['api_key'],
            'license_key' => ['license_key'],
            'token' => ['access_token'],
            'secret' => ['client_secret'],
            'webhook' => ['slack_webhook_url'],
            'card' => ['credit_card_number'],
            'cvv' => ['cvv'],
            'auth' => ['authorization'],
        ];
    }

    public function testRedactsNestedArrayValuesRecursively(): void
    {
        $result = $this->sanitizer->sanitize([
            'general' => [
                'sku' => 'ABC-123',
                'password' => 'hunter2',
                'nested' => [
                    'api_key' => 'sk-abcdef',
                    'label' => 'keep-me',
                ],
            ],
        ]);

        self::assertSame('ABC-123', $result['general']['sku']);
        self::assertSame(ParamSanitizer::REDACTED_VALUE, $result['general']['password']);
        self::assertSame(ParamSanitizer::REDACTED_VALUE, $result['general']['nested']['api_key']);
        self::assertSame('keep-me', $result['general']['nested']['label']);
    }

    public function testKeyIsPreservedWhenValueIsRedacted(): void
    {
        $result = $this->sanitizer->sanitize(['password' => 'hunter2']);

        self::assertArrayHasKey('password', $result);
    }

    public function testEmptyArrayReturnsEmptyArray(): void
    {
        self::assertSame([], $this->sanitizer->sanitize([]));
    }

    public function testFormatForDisplayJoinsFlatKeyValuePairs(): void
    {
        $result = $this->sanitizer->formatForDisplay([
            'sku' => 'ABC-123',
            'qty' => 5,
        ]);

        self::assertSame('sku: ABC-123; qty: 5', $result);
    }

    public function testFormatForDisplayFlattensNestedArraysWithDotPath(): void
    {
        $result = $this->sanitizer->formatForDisplay([
            'general' => [
                'enabled' => 1,
                'nested' => ['label' => 'keep-me'],
            ],
        ]);

        self::assertSame('general.enabled: 1; general.nested.label: keep-me', $result);
    }

    public function testFormatForDisplayShowsRedactedValuesAsIs(): void
    {
        $sanitized = $this->sanitizer->sanitize(['password' => 'hunter2']);
        $result = $this->sanitizer->formatForDisplay($sanitized);

        self::assertSame('password: [REDACTED]', $result);
    }

    public function testFormatForDisplayRendersBooleansAndNullReadably(): void
    {
        $result = $this->sanitizer->formatForDisplay([
            'active' => true,
            'inactive' => false,
            'missing' => null,
        ]);

        self::assertSame('active: true; inactive: false; missing: null', $result);
    }

    public function testFormatForDisplayShowsEmptyArrayLiterally(): void
    {
        $result = $this->sanitizer->formatForDisplay(['tags' => []]);

        self::assertSame('tags: []', $result);
    }

    public function testFormatForDisplayOnEmptyParamsReturnsEmptyString(): void
    {
        self::assertSame('', $this->sanitizer->formatForDisplay([]));
    }
}
