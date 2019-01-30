<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Transformer;

use Phpactor\CodeTransform\Domain\Transformer;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\WorseReflection\Core\Inference\Variable;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Core\SourceCode as WorseSourceCode;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;

class AddMissingProperties implements Transformer
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

        /** @var ReflectionClass $class */
        foreach ($classes as $class) {
            $classOrTrait = $class->isTrait() ? 'trait' : 'class';
            $classBuilder = $sourceBuilder->$classOrTrait($class->name()->short());

            foreach ($class->methods()->belongingTo($class->name()) as $method) {
                $frame = $method->frame();

                /** @var Variable $variable */
                foreach ($frame->properties() as $variable) {
                    $propertyBuilder = $classBuilder
                        ->property($variable->name())
                        ->visibility('private');
                    if ($variable->symbolContext()->type()->isDefined()) {
                        $propertyBuilder->type($variable->symbolContext()->type()->short());
                    }
                }
            }
        }

        if (isset($class)) {
            $sourceBuilder->namespace($class->name()->namespace());
        }

        $code = $this->updater->apply(
            $sourceBuilder->build(),
            Code::fromString((string) $code)
        );

        return SourceCode::fromString($code);
    }
}
