<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Refactor;

use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\CodeBuilder\Domain\Prototype\Visibility;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeTransform\Domain\Refactor\GenerateMethod;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\AbstractReflectionMethodCall;
use Phpactor\WorseReflection\Core\Reflection\ReflectionArgument;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Inference\Variable;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethodCall;
use Phpactor\WorseReflection\Core\Type;

class WorseGenerateMethod implements GenerateMethod
{
    /**
     * @var Reflector
     */
    private $reflector;

    /**
     * @var Updater
     */
    private $updater;

    /** @var int
     */
    private $methodSuffixIndex = 0;

    public function __construct(Reflector $reflector, Updater $updater)
    {
        $this->reflector = $reflector;
        $this->updater = $updater;
    }

    public function generateMethod(string $sourceCode, int $offset, $methodName = null): SourceCode
    {
        $contextType = $this->contextType($sourceCode, $offset);
        $methodCall = $this->reflector->reflectMethodCall($sourceCode, $offset);
        $visibility = $this->determineVisibility($contextType, $methodCall->class());
        $prototype = $this->generatePrototype($methodCall, $visibility, $methodName);
        $sourceCode = $this->resolveSourceCode($sourceCode, $methodCall, $visibility);

        return SourceCode::fromStringAndPath(
            (string) $this->updater->apply($prototype, Code::fromString((string) $sourceCode)),
            $sourceCode->path()
        );
    }

    private function resolveSourceCode(string $sourceCode, ReflectionMethodCall $methodCall, $visibility)
    {
        if ($visibility === Visibility::public()) {
            $sourceCode = SourceCode::fromStringAndPath(
                (string) $methodCall->class()->sourceCode(),
                $methodCall->class()->sourceCode()->path()
            );
        }

        return SourceCode::fromString($sourceCode);
    }

    /**
     * @return ReflectionClassLike
     */
    private function contextType(string $sourceCode, int $offset): Type
    {
        $reflectionOffset = $this->reflector->reflectOffset($sourceCode, $offset);

        /**
         * @var Variable $variable
         */
        foreach ($reflectionOffset->frame()->locals()->byName('$this') as $variable) {
            return $variable->symbolInformation()->type();
        }
    }

    private function generatePrototype(ReflectionMethodCall $methodCall, Visibility $visibility, $methodName)
    {
        $methodName = $methodName ?: $methodCall->name();

        $reflectionClass = $methodCall->class();
        $builder = SourceCodeBuilder::create();
        $builder->namespace((string) $reflectionClass->name()->namespace());

        $classBuilder = $builder->class((string) $reflectionClass->name()->short());
        $methodBuilder = $classBuilder->method($methodName);
        $methodBuilder->visibility((string) $visibility);

        /** @var ReflectionArgument $argument */
        foreach ($methodCall->arguments() as $argument) {
            $type = $argument->type();

            $argumentBuilder = $methodBuilder->parameter($argument->guessName());

            if ($type->isDefined()) {
                if ($type->isPrimitive()) {
                    $argumentBuilder->type((string) $type);
                }

                if ($type->isClass()) {
                    $argumentBuilder->type($type->className()->short());
                }
            }
        }

        return $builder->build();
    }

    private function sourceFromSymbolInformation(SymbolInformation $info): SourceCode
    {
        $containingClass = $this->reflector->reflectClassLike($info->containerType()->className());
        $worseSourceCode = $containingClass->sourceCode();

        return SourceCode::fromStringAndPath(
            $worseSourceCode->__toString(),
            $worseSourceCode->path()
        );
    }

    private function determineVisibility(Type $contextType, ReflectionClassLike $targetClass): Visibility
    {
        if ($contextType->isClass() && $contextType->className() == $targetClass->name()) {
            return Visibility::private();
        }

        return Visibility::public();
    }
}

