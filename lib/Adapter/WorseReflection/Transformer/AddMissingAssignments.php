<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Transformer;

use Phpactor\CodeTransform\Domain\Transformer;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Core\SourceCode as WorseSourceCode;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflection\Inference\Variable;

class AddMissingAssignments  implements Transformer
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

    public function transform(SourceCode $code): SourceCode
    {
        $classes = $this->reflector->reflectClassesIn(
            WorseSourceCode::fromString((string) $code)
        );

        if ($classes->count() === 0) {
            return $code;
        }

        $sourceBuilder = SourceCodeBuilder::create();

        /** @var $class ReflectionClass */
        foreach ($classes as $class) {
            $classBuilder = $sourceBuilder->class($class->name()->short());

            foreach ($class->methods()->belongingTo($class->name()) as $method) {
                $frame = $method->frame();

                /** @var $variable Variable */
                foreach ($frame->properties() as $variable) {
                    $propertyBuilder = $classBuilder
                        ->property($variable->name())
                        ->visibility('private');
                    if ($variable->value()->type()->isDefined()) {
                        $propertyBuilder->type((string) $variable->value()->type()->short());
                    }
                }
            }
        }

        $sourceBuilder->namespace((string) $class->name()->namespace());

        $code = $this->updater->apply(
            $sourceBuilder->build(),
            Code::fromString((string) $code)
        );

        return SourceCode::fromString($code);
    }
}



