<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Parser;

use ReflectionClass;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Analyze Laravel FormRequest classes to extract validation rules.
 *
 * Converts Laravel validation rules into OpenAPI request body schemas.
 */
class FormRequestAnalyzer
{
    public function __construct(
        private readonly TypeResolver $typeResolver,
    ) {}

    /**
     * Analyze a FormRequest class and extract schema.
     *
     * @param class-string<FormRequest> $formRequestClass
     * @return array<string, mixed>
     */
    public function analyze(string $formRequestClass): array
    {
        if (!class_exists($formRequestClass)) {
            return [];
        }

        if (!is_subclass_of($formRequestClass, FormRequest::class)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($formRequestClass);

            // Check if class is instantiable
            if (!$reflection->isInstantiable()) {
                return [];
            }

            /** @var FormRequest $instance */
            $instance = $reflection->newInstanceWithoutConstructor();
            $rules = $this->extractRules($instance);

            if (empty($rules)) {
                return [];
            }

            return $this->buildSchema($rules, $formRequestClass);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Analyze multiple FormRequest classes.
     *
     * @param array<class-string<FormRequest>> $formRequestClasses
     * @return array<string, mixed>
     */
    public function analyzeMany(array $formRequestClasses): array
    {
        $schemas = [];

        foreach ($formRequestClasses as $formRequestClass) {
            $schema = $this->analyze($formRequestClass);

            if (!empty($schema)) {
                $name = $this->getSchemaName($formRequestClass);
                $schemas[$name] = $schema;
            }
        }

        return $schemas;
    }

    /**
     * Extract validation rules from FormRequest instance.
     *
     * @return array<string, mixed>
     */
    private function extractRules(FormRequest $instance): array
    {
        try {
            // Try to call rules() method
            if (method_exists($instance, 'rules')) {
                $rules = $instance->rules();

                if (is_array($rules)) {
                    return $rules;
                }
            }
        } catch (\Exception $e) {
            // Silently fail if rules() cannot be called
        }

        return [];
    }

    /**
     * Build OpenAPI schema from validation rules.
     *
     * @param array<string, mixed> $rules
     * @param string $formRequestClass
     * @return array<string, mixed>
     */
    private function buildSchema(array $rules, string $formRequestClass): array
    {
        $properties = [];
        $required = [];

        foreach ($rules as $field => $rule) {
            $ruleString = $this->normalizeRule($rule);
            $property = $this->convertRuleToProperty($field, $ruleString);

            if (!empty($property)) {
                $properties[$field] = $property;

                // Check if required
                if ($this->isRequired($ruleString)) {
                    $required[] = $field;
                }
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if (!empty($required)) {
            $schema['required'] = $required;
        }

        // Add description from class name
        $schema['description'] = 'Request body for ' . class_basename($formRequestClass);

        return $schema;
    }

    /**
     * Normalize validation rule to string format.
     *
     * @param string|array<mixed> $rule
     */
    private function normalizeRule(string|array $rule): string
    {
        if (is_string($rule)) {
            return $rule;
        }

        if (is_array($rule)) {
            return implode('|', array_map(function ($r) {
                return is_string($r) ? $r : '';
            }, $rule));
        }

        return '';
    }

    /**
     * Convert Laravel validation rule to OpenAPI property.
     *
     * @return array<string, mixed>
     */
    private function convertRuleToProperty(string $field, string $ruleString): array
    {
        $property = [];
        $rules = explode('|', $ruleString);

        // Determine type
        $type = $this->determineType($rules);
        $property['type'] = $type;

        // Add format
        $format = $this->determineFormat($rules, $field);
        if ($format !== null) {
            $property['format'] = $format;
        }

        // Add constraints
        $constraints = $this->extractConstraints($rules);
        $property = array_merge($property, $constraints);

        // Add enum values
        $enum = $this->extractEnum($rules);
        if (!empty($enum)) {
            $property['enum'] = $enum;
        }

        // Add description based on field name
        $property['description'] = $this->generateDescription($field, $ruleString);

        return $property;
    }

    /**
     * Determine OpenAPI type from validation rules.
     *
     * @param array<string> $rules
     */
    private function determineType(array $rules): string
    {
        foreach ($rules as $rule) {
            $ruleName = strtok($rule, ':');

            switch ($ruleName) {
                case 'integer':
                case 'numeric':
                    return 'integer';
                case 'boolean':
                case 'bool':
                    return 'boolean';
                case 'array':
                    return 'array';
                case 'file':
                case 'image':
                    return 'string';
                case 'string':
                case 'email':
                case 'url':
                case 'date':
                case 'uuid':
                case 'ip':
                    return 'string';
            }
        }

        return 'string';
    }

    /**
     * Determine OpenAPI format from validation rules.
     *
     * @param array<string> $rules
     */
    private function determineFormat(array $rules, string $field): ?string
    {
        foreach ($rules as $rule) {
            $ruleName = strtok($rule, ':');

            switch ($ruleName) {
                case 'email':
                    return 'email';
                case 'url':
                    return 'uri';
                case 'date':
                    return 'date';
                case 'uuid':
                    return 'uuid';
                case 'ip':
                    return 'ipv4';
            }
        }

        // Infer from field name
        if (str_contains($field, 'email')) {
            return 'email';
        }

        if (str_contains($field, 'url') || str_contains($field, 'link')) {
            return 'uri';
        }

        if (str_contains($field, 'date') || str_contains($field, 'time')) {
            return 'date-time';
        }

        return null;
    }

    /**
     * Extract constraints from validation rules.
     *
     * @param array<string> $rules
     * @return array<string, mixed>
     */
    private function extractConstraints(array $rules): array
    {
        $constraints = [];

        foreach ($rules as $rule) {
            $parts = explode(':', $rule);
            $ruleName = $parts[0];
            $params = $parts[1] ?? null;

            switch ($ruleName) {
                case 'min':
                    if ($params !== null) {
                        $constraints['minLength'] = (int) $params;
                    }
                    break;
                case 'max':
                    if ($params !== null) {
                        $constraints['maxLength'] = (int) $params;
                    }
                    break;
                case 'between':
                    if ($params !== null) {
                        [$min, $max] = explode(',', $params);
                        $constraints['minimum'] = (int) $min;
                        $constraints['maximum'] = (int) $max;
                    }
                    break;
                case 'size':
                    if ($params !== null) {
                        $constraints['minLength'] = (int) $params;
                        $constraints['maxLength'] = (int) $params;
                    }
                    break;
            }
        }

        return $constraints;
    }

    /**
     * Extract enum values from validation rules.
     *
     * @param array<string> $rules
     * @return array<mixed>
     */
    private function extractEnum(array $rules): array
    {
        foreach ($rules as $rule) {
            if (str_starts_with($rule, 'in:')) {
                $values = substr($rule, 3);
                return explode(',', $values);
            }
        }

        return [];
    }

    /**
     * Check if field is required.
     */
    private function isRequired(string $ruleString): bool
    {
        $rules = explode('|', $ruleString);
        return in_array('required', $rules, true);
    }

    /**
     * Generate description from field name and rules.
     */
    private function generateDescription(string $field, string $ruleString): string
    {
        $fieldName = str_replace('_', ' ', $field);
        $fieldName = ucfirst($fieldName);

        // Add required info
        if ($this->isRequired($ruleString)) {
            return $fieldName . ' (required)';
        }

        return $fieldName;
    }

    /**
     * Get schema name from FormRequest class name.
     */
    private function getSchemaName(string $formRequestClass): string
    {
        $baseName = class_basename($formRequestClass);

        // Remove "Request" suffix if exists
        if (str_ends_with($baseName, 'Request')) {
            $baseName = substr($baseName, 0, -7);
        }

        return $baseName . 'Request';
    }
}
