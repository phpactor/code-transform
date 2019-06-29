<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Transformer;

use Phpactor\CodeTransform\Domain\Transformer;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Core\SourceCode as WorseSourceCode;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
use Phpactor\CodeBuilder\Domain\BuilderFactory;

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

    /**
     * @var BuilderFactory
     */
    private $factory;

    public function __construct(Reflector $reflector, Updater $updater, BuilderFactory $factory)
    {
        $this->updater = $updater;
        $this->reflector = $reflector;
        $this->factory = $factory;
    }

    public function transform(SourceCode $source): SourceCode
    {
        $classes = $this->reflector->reflectClassesIn(WorseSourceCode::fromString((string) $source));
        $edits = [];
        $sourceCodeBuilder = SourceCodeBuilder::create();

        /** @var ReflectionClass $class */
        foreach ($classes->concrete() as $class) {
            $classBuilder = $sourceCodeBuilder->class($class->name()->short());
            $missingMethods = $this->missingClassMethods($class);

            if (empty($missingMethods)) {
                continue;
            }

            /** @var ReflectionMethod $missingMethod */
            foreach ($missingMethods as $missingMethod) {
                $builder = $this->factory->fromSource($missingMethod->declaringClass()->sourceCode());

                $methodBuilder = $builder->classLike(
                    $missingMethod->declaringClass()->name()->short()
                )->method($missingMethod->name());

                if ($missingMethod->docblock()->isDefined()) {
                    $methodBuilder->docblock('{@inheritDoc}');
                }

                if ($missingMethod->returnType()->isDefined()) {
                    $returnTypeClassName = $missingMethod->returnType()->className();
                    if ($returnTypeClassName && $missingMethod->returnType()->isClass() && $returnTypeClassName->namespace() != $class->name()->namespace()) {
                        $sourceCodeBuilder->use((string) $missingMethod->returnType());
                    }
                }

                foreach ($missingMethod->parameters() as $parameter) {
                    if ($parameter->type()->isDefined()) {
                        if ($parameter->type()->isClass() && $parameter->type()->className()->namespace() != $class->name()->namespace()) {
                            $sourceCodeBuilder->use((string) $parameter->type());
                        }
                    }
                }

                $classBuilder->add($methodBuilder);
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
                if ($reflectionMethods->has($method->name())) {
                    continue;
                }

                $methods[] = $method;
            }
        }

        foreach ($class->methods()->abstract() as $method) {
            if ($method->declaringClass()->name() == $class->name()) {
                continue;
            }

            $methods[] = $method;
        }

        return $methods;
    }
}
