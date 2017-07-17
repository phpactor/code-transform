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

            if (0 === count($missingMethods)) {
                continue;
            }

            foreach ($missingMethods as $missingMethod) {
                $methodBuilder = $classBuilder->method($missingMethod->name());
                if ($missingMethod->type() != Type::unknown()) {
                    $methodBuilder->returnType($missingMethod->type()->className() ? $missingMethod->type()->className()->short() : (string) $missingMethod->type());
                }

                if (trim($missingMethod->docblock()->raw())) {
                    $methodBuilder->docblock('{@inheritDoc}');
                }

                foreach ($missingMethod->parameters() as $parameter) {
                    $parameterBuilder = $methodBuilder->parameter($parameter->name());
                    if ($parameter->hasType()) {
                        $parameterBuilder->type($parameter->type()->className() ? $parameter->type()->className()->short() : (string) $parameter->type());

                        if (false === $parameter->type()->isPrimitive()) {
                            $sourceCodeBuilder->use((string) $parameter->type());
                        }
                    }

                    if ($parameter->hasDefault()) {
                        $parameterBuilder->defaultValue($parameter->default());
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
                // TODO: support static methods (requires changes to code builder)
                if ($method->isStatic()) {
                    continue;
                }
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

