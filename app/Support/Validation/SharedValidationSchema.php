<?php

namespace App\Support\Validation;

use InvalidArgumentException;
use JsonException;

final class SharedValidationSchema
{
    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    /**
     * @return array<string, mixed>
     */
    public static function load(string $schema): array
    {
        if (\array_key_exists($schema, self::$cache)) {
            return self::$cache[$schema];
        }

        $path = resource_path("js/Validation/schemas/{$schema}.json");

        if (! is_file($path)) {
            throw new InvalidArgumentException("Validation schema [{$schema}] was not found.");
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException(
                "Validation schema [{$schema}] contains invalid JSON.",
                previous: $exception,
            );
        }

        return self::$cache[$schema] = $decoded;
    }
}
