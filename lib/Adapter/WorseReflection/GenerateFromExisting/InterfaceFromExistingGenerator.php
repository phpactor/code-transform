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
use Phpactor\WorseReflection\Visibility;

final class InterfaceFromExistingGenerator implements GenerateFromExisting
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
        $sourceBuilder->namespace((string) $targetName->namespace());
        $interfaceBuilder = $sourceBuilder->interface((string) $targetName->short());
        $useClasses = [];

        /** @var $method ReflectionMethod */
        foreach ($existingClass->methods()->byVisibilities([ Visibility::public() ]) as $method) {
            if ($method->name() === '__construct') {
                continue;
            }

            $methodBuilder = $interfaceBuilder->method($method->name());
            $methodBuilder->visibility((string) $method->visibility());

            if ($method->docblock()->formatted()) {
                $methodBuilder->docblock($method->docblock()->formatted());
            }

            /** @var $parameter ReflectionParameter */
            foreach ($method->parameters() as $parameter) {
                $parameterBuilder = $methodBuilder->parameter((string) $parameter->name());
                if ($parameter->type()->isDefined()) {
                    if ($parameter->type()->isPrimitive()) {
                        $parameterBuilder->type($parameter->type()->primitive());
                    } else {
                        $parameterBuilder->type((string) $parameter->type()->className()->short());
                        $useClasses[$parameter->type()->className()->__toString()] = true;
                    }

                    if ($parameter->default()->isDefined()) {
                        $parameterBuilder->defaultValue($parameter->default()->value());
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
