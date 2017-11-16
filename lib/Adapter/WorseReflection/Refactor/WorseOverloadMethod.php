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
        $class = $this->reflector->reflectClass($className);
        $methodBuilder = $this->getMethodPrototype($class, $methodName);

        $sourceBuilder = $this->factory->fromSource((string) $source);
        $sourceBuilder->class($class->name()->short())->add($methodBuilder);

        $prototype = $sourceBuilder->build();

        return $this->updater->apply($prototype, Code::fromString((string) $source));
    }

    private function getMethodPrototype(ReflectionClass $class, string $methodName)
    {
        if (null === $class->parent()) {
            throw new TransformException(sprintf(
                'Class "%s" has no parent, cannot overload anything',
                $class->name()
            ));
        }

        $method = $class->methods()->get($methodName);

        /** @var ReflectionMethod $method */
        $builder = $this->factory->fromSource((string) $method->class()->sourceCode());
        $methodBuilder = $builder->class($class->name()->short())->method($methodName);

        return $methodBuilder;
    }
}
