<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Refactor;

use Phpactor\CodeTransform\Domain\Refactor\OverloadMethod;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Domain\ClassName;
use Phpactor\WorseReflection\Reflector;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\WorseReflection\Core\Reflection\ReflectionParameter;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\CodeTransform\Domain\Exception\TransformException;

class WorseOverloadMethod implements OverloadMethod
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var Updater
     */
    private $updater;

    public function __construct(Reflector $reflector, Updater $updater)
    {
        $this->reflector = $reflector;
        $this->updater = $updater;
    }

    public function overloadMethod(SourceCode $source, string $className, string $methodName)
    {
        $prototype = $this->getPrototype($className, $methodName);

        return $this->updater->apply($prototype, Code::fromString((string) $source));
    }

    private function getPrototype(string $className, string $methodName)
    {
        $class = $this->reflector->reflectClass($className);

        if (null === $class->parent()) {
            throw new TransformException(sprintf(
                'Class "%s" has no parent, cannot overload anything',
                $className
            ));
        }

        /** @var ReflectionMethod $method */
        $method = $class->parent()->methods()->get($methodName);

        $builder = SourceCodeBuilder::create();
        $classPrototype = $builder
            ->namespace((string) $class->name()->namespace())
            ->class($class->name()->short());

        $methodPrototype = $classPrototype->method($methodName);
        $methodPrototype->visibility((string) $method->visibility());

        /** @var ReflectionParameter $parameter */
        foreach ($method->parameters() as $parameter) {
            $parameterPrototype = $methodPrototype->parameter($parameter->name());

            if ($parameter->default()->isDefined()) {
                $parameterPrototype->defaultValue($parameter->default()->value());
            }

            if ($parameter->type()->isDefined()) {
                if ($parameter->type()->isClass() && $parameter->type()->className()->namespace() != $class->name()->namespace()) {
                    $builder->use((string) $parameter->type());
                }

                $parameterPrototype->type((string) $parameter->type()->short());
            }
        }

        if ($method->returnType()->isDefined()) {
            if ($method->returnType()->isClass() && $method->returnType()->className()->namespace() != $class->name()->namespace()) {
                $builder->use((string) $method->returnType());
            }
            $methodPrototype->returnType($method->returnType()->short());
        }

        return $builder->build();
    }
}
