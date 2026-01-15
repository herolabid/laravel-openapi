<?php

declare(strict_types=1);

namespace HerolabID\LaravelOpenApi\Parser;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionIntersectionType;

/**
 * Helper class for working with PHP Reflection API.
 */
class ReflectionHelper
{
    /**
     * Get all attributes of a specific type from a class.
     *
     * @template T
     * @param class-string<T> $attributeClass
     * @return array<T>
     */
    public function getClassAttributes(
        ReflectionClass $class,
        string $attributeClass,
    ): array {
        return $this->instantiateAttributes(
            $class->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF)
        );
    }

    /**
     * Get all attributes of a specific type from a method.
     *
     * @template T
     * @param class-string<T> $attributeClass
     * @return array<T>
     */
    public function getMethodAttributes(
        ReflectionMethod $method,
        string $attributeClass,
    ): array {
        return $this->instantiateAttributes(
            $method->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF)
        );
    }

    /**
     * Get all attributes of a specific type from a property.
     *
     * @template T
     * @param class-string<T> $attributeClass
     * @return array<T>
     */
    public function getPropertyAttributes(
        ReflectionProperty $property,
        string $attributeClass,
    ): array {
        return $this->instantiateAttributes(
            $property->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF)
        );
    }

    /**
     * Get all public methods from a class, excluding magic methods.
     *
     * @return array<ReflectionMethod>
     */
    public function getPublicMethods(ReflectionClass $class): array
    {
        return array_filter(
            $class->getMethods(ReflectionMethod::IS_PUBLIC),
            fn(ReflectionMethod $method) => !$this->isMagicMethod($method)
                && !$method->isConstructor()
                && !$method->isDestructor()
        );
    }

    /**
     * Get all properties from a class.
     *
     * @return array<ReflectionProperty>
     */
    public function getProperties(ReflectionClass $class): array
    {
        return $class->getProperties(ReflectionProperty::IS_PUBLIC);
    }

    /**
     * Check if a method is a magic method.
     */
    public function isMagicMethod(ReflectionMethod $method): bool
    {
        return str_starts_with($method->getName(), '__');
    }

    /**
     * Get the type name from a reflection type.
     */
    public function getTypeName(
        ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $type,
    ): ?string {
        if ($type === null) {
            return null;
        }

        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            $types = array_map(
                fn($t) => $t instanceof ReflectionNamedType ? $t->getName() : 'mixed',
                $type->getTypes()
            );
            return implode('|', $types);
        }

        return 'mixed';
    }

    /**
     * Check if a type is nullable.
     */
    public function isNullable(
        ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $type,
    ): bool {
        if ($type === null) {
            return true;
        }

        if ($type instanceof ReflectionNamedType) {
            return $type->allowsNull();
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $subType) {
                if ($subType instanceof ReflectionNamedType && $subType->getName() === 'null') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get method parameter names.
     *
     * @return array<string>
     */
    public function getParameterNames(ReflectionMethod $method): array
    {
        return array_map(
            fn($param) => $param->getName(),
            $method->getParameters()
        );
    }

    /**
     * Get return type of a method.
     */
    public function getReturnType(ReflectionMethod $method): ?string
    {
        $returnType = $method->getReturnType();
        return $this->getTypeName($returnType);
    }

    /**
     * Check if a class exists and is instantiable.
     */
    public function isInstantiableClass(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        $class = new ReflectionClass($className);
        return $class->isInstantiable();
    }

    /**
     * Instantiate attributes from reflection attributes.
     *
     * @param array<ReflectionAttribute> $attributes
     * @return array<object>
     */
    private function instantiateAttributes(array $attributes): array
    {
        return array_map(
            fn(ReflectionAttribute $attr) => $attr->newInstance(),
            $attributes
        );
    }
}
