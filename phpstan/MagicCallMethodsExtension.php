<?php

namespace Go2Flow\Ezport\PHPStan;

use Go2Flow\Ezport\Connectors\Ftp\Api as FtpApi;
use Go2Flow\Ezport\Connectors\ShopwareSix\Api as ShopSixApi;
use Go2Flow\Ezport\ContentTypes\Helpers\TypeGetter;
use Go2Flow\Ezport\Finders\Base as Finder;
use Go2Flow\Ezport\Instructions\Getters\GetProxy;
use Go2Flow\Ezport\Instructions\Setters\Types\Base as Setter;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;

/**
 * Describes the __call conventions PHPStan cannot see:
 * - the Shopware and FTP APIs take any unknown method as endpoint or folder and return themselves
 *   ($api->product()->search(), $api->orderArchive()->upload(...)); GetProxy records any call
 *   and returns itself;
 * - finders (Find::api(), Find::instruction(), ...) forward unknown calls to an object whose class
 *   is only known at runtime, and TypeGetter forwards them to the query builder and converts the
 *   result, so the result is mixed;
 * - setters answer get<Property>() with that property (Job::getClass() reads $class).
 */
class MagicCallMethodsExtension implements MethodsClassReflectionExtension
{
    private const SELF_RETURNING = [ShopSixApi::class, FtpApi::class, GetProxy::class];

    private const FORWARDING = [Finder::class, TypeGetter::class];

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $this->selfReturning($classReflection)
            || $this->isAny($classReflection, self::FORWARDING)
            || $this->setterGetter($classReflection, $methodName);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return new MagicCallMethodReflection(
            $classReflection,
            $methodName,
            $this->selfReturning($classReflection) ? new ObjectType($classReflection->getName()) : new MixedType,
        );
    }

    private function setterGetter(ClassReflection $classReflection, string $methodName): bool
    {
        return $classReflection->is(Setter::class)
            && str_starts_with($methodName, 'get')
            && $classReflection->hasNativeProperty(lcfirst(substr($methodName, 3)));
    }

    private function selfReturning(ClassReflection $classReflection): bool
    {
        return $this->isAny($classReflection, self::SELF_RETURNING);
    }

    private function isAny(ClassReflection $classReflection, array $classes): bool
    {
        foreach ($classes as $class) {
            if ($classReflection->is($class)) {
                return true;
            }
        }

        return false;
    }
}
