<?php

namespace App\Support\Validation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

final class SharedLaravelValidationRules
{
    /**
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

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     */
    private static function buildUniqueRule(array $config, array $context): object
    {
        $rule = Rule::unique(
            (string) $config['table'],
            (string) $config['column'],
        );

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
