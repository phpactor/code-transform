<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Transformer;

use Phpactor\CodeTransform\Domain\Transformer;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\WorseReflection\Core\Reflection\ReflectionInterface;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Core\SourceCode as WorseSourceCode;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\CodeBuilder\Domain\Code;

class CompleteConstructor implements Transformer
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

        foreach ($classes as $class) {
            if ($class instanceof ReflectionInterface) {
                continue;
            }

            $classBuilder = $sourceCodeBuilder->class($class->name()->short());

            if (!$class->methods()->has('__construct')) {
                continue;
            }

            $methodBuilder = $classBuilder->method('__construct');
            $constructMethod = $class->methods()->get('__construct');
            $methodBody = (string) $constructMethod->body();

            foreach ($constructMethod->parameters() as $parameter) {
                if (preg_match('{this\s*->' . $parameter->name() . '}', $methodBody)) {
                    continue;
                }
                $methodBuilder->body()->line('$this->' . $parameter->name() . ' = $' . $parameter->name() .';');
            }

            foreach ($constructMethod->parameters() as $parameter) {
                /** @var ReflectionClass|ReflectionTrait $class */
                if (true === $class->properties()->has($parameter->name())) {
                    continue;
                }

                $propertyBuilder = $classBuilder->property($parameter->name());
                $propertyBuilder->visibility('private');
                if ($parameter->type()->isDefined()) {
                    $propertyBuilder->type((string) $parameter->type()->short());
                }
            }
        }

        $source = SourceCode::fromString($this->updater->apply($sourceCodeBuilder->build(), Code::fromString((string) $source)));

        return $source;
    }
}
