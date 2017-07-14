<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\GenerateFromExisting;

use Phpactor\CodeTransform\Domain\GenerateFromExisting;
use Phpactor\CodeTransform\Domain\ClassName;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\ClassName as ReflectionClassName;
use Phpactor\CodeBuilder\Domain\Renderer;
use Phpactor\WorseReflection\Reflection\ReflectionMethod;
use Phpactor\WorseReflection\Reflection\ReflectionParameter;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;

final class InterfaceGenerator implements GenerateFromExisting
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var Renderer
     */
    private $renderer;

    public function __construct(Reflector $reflector, Renderer $renderer)
    {
        $this->reflector = $reflector;
        $this->renderer = $renderer;
    }

    /**
     * {@inheritDoc}
     */
    public function generateFromExisting(ClassName $existingClass, ClassName $targetName): SourceCode
    {
        $existingClass = $this->reflector->reflectClass(ReflectionClassName::fromString((string) $existingClass));

        /** @var $sourceBuilder SourceCodeBuilder */
        $sourceBuilder = SourceCodeBuilder::create();
        $interfaceBuilder = $sourceBuilder->class((string) $targetName);
        $useClasses = [];

        /** @var $method ReflectionMethod */
        foreach ($existingClass->methods() as $method) {
            $methodBuilder = $interfaceBuilder->method($method->getName());
            $methodBuilder->visibility((string) $method->visibility());

            /** @var $parameter ReflectionParameter */
            foreach ($method->parameters() as $parameter) {
                $parameterBuilder = $methodBuilder->parameter((string) $parameter->name());
                if (false === $parameter->type()->isUnknown()) {
                    $parameterBuilder->type((string) $parameter->type()->className()->short());
                    $useClasses[$parameter->type()->className()->__toString()] = true;
                    if ($parameter->hasDefault()) {
                        $parameterBuilder->defaultValue($parameter->default());
                    }
                }
            }
        }

        foreach (array_keys($useClasses) as $useClass) {
            $sourceBuilder->use((string) $useClass);
        }

        return SourceCode::fromString($this->renderer->render($sourceBuilder->build()));
    }
}
