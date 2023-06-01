<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection\Adapter;

use ReflectionException as CoreReflectionException;
use ReflectionProperty as CoreReflectionProperty;
use Roave\BetterReflection\Reflection\Exception\NoObjectProvided;
use Roave\BetterReflection\Reflection\Exception\NotAnObject;
use Roave\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use Throwable;
use TypeError;
use ValueError;

use function array_map;

final class ReflectionProperty extends CoreReflectionProperty
{
    private bool $accessible = false;

    public function __construct(private BetterReflectionProperty $betterReflectionProperty)
    {
    }

    public function __toString(): string
    {
        return $this->betterReflectionProperty->__toString();
    }

    public function getName(): string
    {
        return $this->betterReflectionProperty->getName();
    }

    /**
     * @psalm-suppress MethodSignatureMismatch
     */
    public function getValue(?object $object = null): mixed
    {
        if (! $this->isAccessible()) {
            throw new CoreReflectionException('Property not accessible');
        }

        try {
            return $this->betterReflectionProperty->getValue($object);
        } catch (NoObjectProvided | TypeError) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), previous: $e);
        }
    }

    /**
     * @psalm-suppress MethodSignatureMismatch
     */
    public function setValue(mixed $object, mixed $value = null): void
    {
        if (! $this->isAccessible()) {
            throw new CoreReflectionException('Property not accessible');
        }

        try {
            $this->betterReflectionProperty->setValue($object, $value);
        } catch (NoObjectProvided | NotAnObject) {
            return;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), previous: $e);
        }
    }

    public function hasType(): bool
    {
        return $this->betterReflectionProperty->hasType();
    }

    /**
     * @psalm-mutation-free
     */
    public function getType(): ReflectionUnionType|ReflectionNamedType|ReflectionIntersectionType|null
    {
        /** @psalm-suppress ImpureMethodCall */
        return ReflectionType::fromTypeOrNull($this->betterReflectionProperty->getType());
    }

    public function isPublic(): bool
    {
        return $this->betterReflectionProperty->isPublic();
    }

    public function isPrivate(): bool
    {
        return $this->betterReflectionProperty->isPrivate();
    }

    public function isProtected(): bool
    {
        return $this->betterReflectionProperty->isProtected();
    }

    public function isStatic(): bool
    {
        return $this->betterReflectionProperty->isStatic();
    }

    public function isDefault(): bool
    {
        return $this->betterReflectionProperty->isDefault();
    }

    public function getModifiers(): int
    {
        return $this->betterReflectionProperty->getModifiers();
    }

    public function getDeclaringClass(): ReflectionClass
    {
        return new ReflectionClass($this->betterReflectionProperty->getImplementingClass());
    }

    public function getDocComment(): string|false
    {
        return $this->betterReflectionProperty->getDocComment() ?: false;
    }

    public function setAccessible(bool $accessible): void
    {
        $this->accessible = true;
    }

    public function isAccessible(): bool
    {
        return $this->accessible || $this->isPublic();
    }

    public function hasDefaultValue(): bool
    {
        return $this->betterReflectionProperty->hasDefaultValue();
    }

    public function getDefaultValue(): mixed
    {
        return $this->betterReflectionProperty->getDefaultValue();
    }

    public function isInitialized(?object $object = null): bool
    {
        if (! $this->isAccessible()) {
            throw new CoreReflectionException('Property not accessible');
        }

        try {
            return $this->betterReflectionProperty->isInitialized($object);
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), previous: $e);
        }
    }

    public function isPromoted(): bool
    {
        return $this->betterReflectionProperty->isPromoted();
    }

    /**
     * @param class-string|null $name
     *
     * @return list<ReflectionAttribute>
     */
    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        if ($flags !== 0 && $flags !== ReflectionAttribute::IS_INSTANCEOF) {
            throw new ValueError('Argument #2 ($flags) must be a valid attribute filter flag');
        }

        if ($name !== null && $flags & ReflectionAttribute::IS_INSTANCEOF) {
            $attributes = $this->betterReflectionProperty->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionProperty->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionProperty->getAttributes();
        }

        return array_map(static fn (BetterReflectionAttribute $betterReflectionAttribute): ReflectionAttribute => new ReflectionAttribute($betterReflectionAttribute), $attributes);
    }

    public function isReadOnly(): bool
    {
        return $this->betterReflectionProperty->isReadOnly();
    }
}
