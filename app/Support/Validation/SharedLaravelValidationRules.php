<?php

namespace App\Support\Validation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * A közös JSON sémákból Laravel-kompatibilis validációs szabályokat épít.
 *
 * Ez a réteg biztosítja, hogy a backend validáció ugyanabból a forrásból készüljön,
 * mint a frontend gyors visszajelzéseihez használt séma.
 */
final class SharedLaravelValidationRules
{
    /**
     * A séma meződefinícióit Laravel rule tömbökké alakítja.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, array<int, mixed>>
     */
    public static function for(string $schema, array $context = []): array
    {
        $definition = SharedValidationSchema::load($schema);
        $fields = $definition['fields'] ?? [];

        $rules = [];

        foreach ($fields as $field => $config) {
            $rules[$field] = self::mapFieldRules((array) $config, $context);
        }

        return $rules;
    }

    /**
     * Egy mező sémáját alakítja át a Laravel validator által értelmezhető szabályokká.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array<int, mixed>
     */
    private static function mapFieldRules(array $config, array $context): array
    {
        $rules = [];

        if (($config['nullable'] ?? false) === true) {
            $rules[] = 'nullable';
        }

        if (($config['required'] ?? false) === true) {
            $rules[] = 'required';
        }

        foreach ($config['types'] ?? [] as $type) {
            $rules[] = $type;
        }

        if (isset($config['format']) && is_string($config['format'])) {
            $rules[] = $config['format'];
        }

        if (isset($config['min'])) {
            $rules[] = 'min:'.$config['min'];
        }

        if (isset($config['max'])) {
            $rules[] = 'max:'.$config['max'];
        }

        if (isset($config['unique']) && is_array($config['unique'])) {
            $rules[] = self::buildUniqueRule($config['unique'], $context);
        }

        if (isset($config['exists']) && is_array($config['exists'])) {
            $rules[] = 'exists:'.$config['exists']['table'].','.$config['exists']['column'];
        }

        return $rules;
    }

    /**
     * A context alapján feloldható `unique` szabályt építi fel, beleértve az ignore eseteket is.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     */
    private static function buildUniqueRule(array $config, array $context): object
    {
        $rule = Rule::unique(
            (string) $config['table'],
            (string) $config['column'],
        );

        if (isset($config['where']) && is_array($config['where'])) {
            foreach ($config['where'] as $column => $contextKey) {
                if (! is_string($column) || ! is_string($contextKey) || ! array_key_exists($contextKey, $context)) {
                    continue;
                }

                $rule->where($column, $context[$contextKey]);
            }
        }

        $ignoreContextKey = $config['ignoreContextKey'] ?? null;

        if (! \is_string($ignoreContextKey) || ! array_key_exists($ignoreContextKey, $context)) {
            return $rule;
        }

        $ignoredValue = $context[$ignoreContextKey];

        if ($ignoredValue instanceof Model) {
            return $rule->ignore($ignoredValue->getKey());
        }

        if ($ignoredValue !== null) {
            return $rule->ignore($ignoredValue);
        }

        return $rule;
    }
}
