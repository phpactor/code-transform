<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Transformer;

use Phpactor\CodeTransform\Domain\Diagnostic;
use Phpactor\CodeTransform\Domain\Diagnostics;
use Phpactor\CodeTransform\Domain\Transformer;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\TextDocument\ByteOffsetRange;
use Phpactor\TextDocument\TextEdits;
use Phpactor\WorseReflection\Core\Inference\Variable;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMember;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Core\SourceCode as WorseSourceCode;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeBuilder\Domain\Builder\ClassLikeBuilder;
use Phpactor\CodeBuilder\Domain\Builder\ClassBuilder;
use Phpactor\CodeBuilder\Domain\Builder\TraitBuilder;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;

class AddMissingProperties implements Transformer
{
    private const LENGTH_OF_THIS_PREFIX = 7;

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

    public function transform(SourceCode $code): TextEdits
    {
        $classes = $this->reflector->reflectClassesIn(
            WorseSourceCode::fromString((string) $code)
        );

        if ($classes->count() === 0) {
            return TextEdits::none();
        }

        $sourceBuilder = SourceCodeBuilder::create();

        /** @var ReflectionClass $class */
        foreach ($classes as $class) {
            $classBuilder = $this->resolveClassBuilder($sourceBuilder, $class);

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

        return $this->updater->textEditsFor(
            $sourceBuilder->build(),
            Code::fromString((string) $code)
        );
    }

    public function diagnostics(SourceCode $code): Diagnostics
    {
        $diagnostics = [];
        $classes = $this->reflector->reflectClassesIn(
            WorseSourceCode::fromString((string) $code)
        );

        /** @var ReflectionClass $class */
        foreach ($classes as $class) {
            foreach ($class->methods()->belongingTo($class->name()) as $method) {
                assert($method instanceof ReflectionMember);
                $frame = $method->frame();

                foreach ($frame->properties() as $assignedProperty) {
                    assert($assignedProperty instanceof Variable);

                    $assignedPropertyClass = $assignedProperty->symbolContext()->containerType();

                    if (
                        $assignedPropertyClass
                        && $class->name() != $assignedPropertyClass->className()
                    ) {
                        continue;
                    }

                    if ($class->properties()->has($assignedProperty->name())) {
                        continue;
                    }

                    $diagnostics[] = new Diagnostic(
                        ByteOffsetRange::fromInts(
                            $assignedProperty->offset()->toInt(),
                            $assignedProperty->offset()->toInt() + self::LENGTH_OF_THIS_PREFIX + strlen($assignedProperty->name())
                        ),
                        sprintf('Assigning to non existant property "%s"', $assignedProperty->name()),
                        Diagnostic::WARNING
                    );
                }
            }
        }

        return new Diagnostics($diagnostics);
    }

    /**
     * @return TraitBuilder|ClassBuilder
     */
    private function resolveClassBuilder(SourceCodeBuilder $sourceBuilder, ReflectionClassLike $class): ClassLikeBuilder
    {
        $name = $class->name()->short();

        if ($class->isTrait()) {
            return $sourceBuilder->trait($name);
        }

        return $sourceBuilder->class($name);
    }
}
