<?php
declare(strict_types=1);

namespace TVTCommerce\AdminAuditLog\Model;

/**
 * Pure, Magento-independent sanitization logic — deliberately has no framework
 * dependency so it can be unit-tested directly (see tests/Unit/Model/ParamSanitizerTest.php).
 *
 * Recursively walks a request-params array and replaces the *value* of any key
 * that looks sensitive with the literal string "[REDACTED]". The key itself is
 * kept (not dropped) so an admin reviewing the log can still see that a
 * sensitive field was touched by the action, just not its value.
 */
class ParamSanitizer
{
    public const REDACTED_VALUE = '[REDACTED]';

    /**
     * Case-insensitive substring match against each array key. A field named
     * e.g. "new_password" or "webhook_url" is caught by "password"/"webhook".
     */
    private const SENSITIVE_KEY_NEEDLES = [
        'password',
        'api_key',
        'license_key',
        'token',
        'secret',
        'webhook',
        'card',
        'cvv',
        'auth',
    ];

    /**
     * @param array<array-key, mixed> $params
     * @return array<array-key, mixed>
     */
    public function sanitize(array $params): array
    {
        $sanitized = [];

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
                continue;
            }

            $sanitized[$key] = $this->isSensitiveKey((string) $key) ? self::REDACTED_VALUE : $value;
        }

        return $sanitized;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalizedKey = strtolower($key);

        foreach (self::SENSITIVE_KEY_NEEDLES as $needle) {
            if (str_contains($normalizedKey, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Flattens an already-sanitize()'d params array into a single-line, human-readable
     * "key: value; nested.key: value" summary for direct display in the audit grid's
     * "Changed Fields" column — this is what actually answers "what did this action
     * change", instead of a raw JSON blob nobody wants to read in a grid cell. Nested arrays
     * are flattened with a dot-joined path (e.g. "general.enabled: 1") rather than shown as
     * a bracketed sub-object, so every leaf value is scannable on one line.
     *
     * @param array<array-key, mixed> $sanitizedParams
     */
    public function formatForDisplay(array $sanitizedParams, string $keyPrefix = ''): string
    {
        $parts = [];

        foreach ($sanitizedParams as $key => $value) {
            $label = $keyPrefix === '' ? (string) $key : $keyPrefix . '.' . $key;

            if (is_array($value)) {
                $parts[] = $value === [] ? $label . ': []' : $this->formatForDisplay($value, $label);
                continue;
            }

            $parts[] = $label . ': ' . $this->stringifyScalar($value);
        }

        return implode('; ', $parts);
    }

    private function stringifyScalar(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }
}
