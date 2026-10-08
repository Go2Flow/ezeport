<?php

namespace Go2Flow\Ezport\PHPStan;

use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

/** A public method resolved through __call: any arguments, the given return type. */
class MagicCallMethodReflection implements MethodReflection
{
    public function __construct(
        private ClassReflection $classReflection,
        private string $name,
        private Type $returnType,
    ) {}

    public function getDeclaringClass(): ClassReflection
    {
        return $this->classReflection;
    }

    public function isStatic(): bool
    {
        return false;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getDocComment(): ?string
    {
        return null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrototype(): ClassMemberReflection
    {
        return $this;
    }

    public function getVariants(): array
    {
        return [
            new FunctionVariant(
                TemplateTypeMap::createEmpty(),
                TemplateTypeMap::createEmpty(),
                [new class implements ParameterReflection
                {
                    public function getName(): string
                    {
                        return 'arguments';
                    }

                    public function isOptional(): bool
                    {
                        return true;
                    }

                    public function getType(): Type
                    {
                        return new MixedType;
                    }

                    public function passedByReference(): PassedByReference
                    {
                        return PassedByReference::createNo();
                    }

                    public function isVariadic(): bool
                    {
                        return true;
                    }

                    public function getDefaultValue(): ?Type
                    {
                        return null;
                    }
                }],
                true,
                $this->returnType,
            ),
        ];
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getThrowType(): ?Type
    {
        return null;
    }

    public function hasSideEffects(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
}
