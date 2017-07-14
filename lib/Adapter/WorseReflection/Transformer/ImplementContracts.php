<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection;

use Phpactor\CodeTransform\Domain\Transformer;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\SourceCode as WorseSourceCode;
use Phpactor\WorseReflection\Reflection\ReflectionClass;
use Phpactor\CodeTransform\Adapter\TolerantParser\TextEdit;
use Phpactor\CodeTransform\Domain\Editor;

class ImplementContracts implements Transformer
{
    /**
     * @var Editor
     */
    private $editor;

    /**
     * @var Reflector
     */
    private $reflector;

    public function __construct(Reflector $reflector, Editor $editor = null)
    {
        $this->editor = $editor ?: new Editor();
        $this->reflector = $reflector;
    }

    public function transform(SourceCode $source): SourceCode
    {
        $sourceBuilder = $this->sourceBuilder->create();
        $classes = $this->reflector->reflectClassesIn(WorseSourceCode::fromString((string) $source));

        foreach ($classes->concrete() as $class) {
            
            $classBuilder = $sourceBuilder->class((string) $class->name());
            $missingMethods = $this->missingClassMethods($class);

            foreach ($missingMethods as $missingMethod) {
                $method = $prototype->method($missingMethod->name())
                    ->position(\PHP_INT_MAX);

                foreach ($missingMethod->parameters() as $parameter) {
                    $method->parameter((string) $parameter->name())
                        ->type((string) $parameter->type())
                        ->defaultValue((string) $parameter->defaultValue());
                }

            }
        }

        return $this->editor->apply($sourceBuilder->build(), $source);
    }

    private function missingClassMethods(ReflectionClass $class): array
    {
        $methods = [];
        foreach ($class->interfaces() as $interface) {
            foreach ($interface->methods() as $method) {
                if (!$class->methods()->has($method->name())) {
                    $methods[] = $method;
                }
            }
        }

        foreach ($class->methods() as $method) {
            if ($method->class()->name() != $class->name()) {
                $methods[] = $method;
            }
        }

        return $methods;
    }
}


