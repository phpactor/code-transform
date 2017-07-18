<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Transformer;

use Phpactor\CodeTransform\Domain\Transformer;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\SourceCode as WorseSourceCode;
use Phpactor\WorseReflection\Reflection\ReflectionClass;
use Phpactor\CodeTransform\Domain\Editor;
use Microsoft\PhpParser\TextEdit;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\WorseReflection\Type;
use Phpactor\WorseReflection\Reflection\ReflectionMethod;

class ImplementContracts implements Transformer
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
        $this->updater = $updater;
        $this->reflector = $reflector;
    }

    public function transform(SourceCode $source): SourceCode
    {
        $classes = $this->reflector->reflectClassesIn(WorseSourceCode::fromString((string) $source));
        $edits = [];
        $sourceCodeBuilder = SourceCodeBuilder::create();

        foreach ($classes->concrete() as $class) {
            $classBuilder = $sourceCodeBuilder->class($class->name()->short());
            $missingMethods = $this->missingClassMethods($class);

            if (empty($missingMethods)) {
                continue;
            }

            /** @var $missingMethod ReflectionMethod */
            foreach ($missingMethods as $missingMethod) {
                $methodBuilder = $classBuilder->method($missingMethod->name());

                if ($missingMethod->returnType()->isDefined()) {
                    $methodBuilder->returnType($missingMethod->returnType()->short());
                }

                if ($missingMethod->isStatic()) {
                    $methodBuilder->static();
                }

                if ($missingMethod->docblock()->isDefined()) {
                    $methodBuilder->docblock('{@inheritDoc}');
                }

                foreach ($missingMethod->parameters() as $parameter) {
                    $parameterBuilder = $methodBuilder->parameter($parameter->name());

                    if ($parameter->type()->isDefined()) {
                        $parameterBuilder->type($parameter->type()->short());

                        if ($parameter->type()->isClass()) {
                            $sourceCodeBuilder->use((string) $parameter->type());
                        }
                    }

                    if ($parameter->default()->isDefined()) {
                        $parameterBuilder->defaultValue($parameter->default()->value());
                    }
                }
            }
        }

        $source = SourceCode::fromString($this->updater->apply($sourceCodeBuilder->build(), Code::fromString((string) $source)));

        return $source;
    }

    private function missingClassMethods(ReflectionClass $class): array
    {
        $methods = [];
        $reflectionMethods = $class->methods();
        foreach ($class->interfaces() as $interface) {
            foreach ($interface->methods() as $method) {
                if (false === $reflectionMethods->has($method->name())) {
                    $methods[] = $method;
                }
            }
        }

        foreach ($class->methods()->abstract() as $method) {
            if ($method->class()->name() == $class->name()) {
                continue;
            }

            $methods[] = $method;
        }

        return $methods;
    }
}

