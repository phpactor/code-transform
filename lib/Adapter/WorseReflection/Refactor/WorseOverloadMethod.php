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
use Phpactor\CodeBuilder\Domain\BuilderFactory;

class WorseOverloadMethod implements OverloadMethod
{
    /**
     * @var Updater
     */
    private $updater;

    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var BuilderFactory
     */
    private $factory;

    public function __construct(Reflector $reflector, BuilderFactory $factory, Updater $updater)
    {
        $this->factory = $factory;
        $this->updater = $updater;
        $this->reflector = $reflector;
    }

    public function overloadMethod(SourceCode $source, string $className, string $methodName)
    {
        $class = $this->getReflectionClass($className);
        $method = $this->getAncestorReflectionMethod($class, $methodName);

        $methodBuilder = $this->getMethodPrototype($class, $method, $methodName);
        $sourcePrototype = $this->getSourcePrototype($class, $method, $source, $methodBuilder);

        return $this->updater->apply($sourcePrototype, Code::fromString((string) $source));
    }

    private function getReflectionClass(string $className): ReflectionClass
    {
        $class = $this->reflector->reflectClass($className);

        return $class;
    }

    private function getMethodPrototype(ReflectionClass $class, ReflectionMethod $method)
    {
        /** @var ReflectionMethod $method */
        $builder = $this->factory->fromSource(
            (string) $method->class()->sourceCode()
        );

        $methodBuilder = $builder->class($method->declaringClass()->name()->short())->method($method->name());

        return $methodBuilder;
    }

    private function getAncestorReflectionMethod(ReflectionClass $class, string $methodName): ReflectionMethod
    {
        if (null === $class->parent()) {
            throw new TransformException(sprintf(
                'Class "%s" has no parent, cannot overload anything',
                $class->name()
            ));
        }

        return $class->parent()->methods()->get($methodName);
    }

    private function getSourcePrototype(ReflectionClass $class, ReflectionMethod $method, SourceCode $source, $methodBuilder)
    {
        $sourceBuilder = $this->factory->fromSource((string) $source);
        $sourceBuilder->class($class->name()->short())->add($methodBuilder);
        $this->importClasses($class, $method, $sourceBuilder);

        return $sourceBuilder->build();
    }

    private function importClasses(ReflectionClass $class, ReflectionMethod $method, SourceCodeBuilder $sourceBuilder)
    {
        $usedClasses = [];

        if ($method->returnType()->isDefined() && $method->returnType()->isClass()) {
            $usedClasses[] = $method->returnType();
        }

        /**
         * @var ReflectionParameter $parameter */
        foreach ($method->parameters() as $parameter) {
            if (false === $parameter->type()->isDefined() || false === $parameter->type()->isClass()) {
                continue;
            }

            $usedClasses[] = $parameter->type();
        }

        foreach ($usedClasses as $usedClass) {
            if ($class->name()->namespace() == $usedClass->className()->namespace()) {
                continue;
            }

            $sourceBuilder->use((string) $usedClass);
        }
    }
}
