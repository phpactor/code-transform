<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Refactor;

use InvalidArgumentException;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflection\ReflectionProperty;
use Phpactor\WorseReflection\Reflector;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\CodeTransform\Domain\Refactor\GenerateAccessor;

class WorseGenerateAccessor implements GenerateAccessor
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
     * @var string
     */
    private $prefix;

    /**
     * @var bool
     */
    private $upperCaseFirst;

    public function __construct(
        Reflector $reflector,
        Updater $updater,
        string $prefix = '',
        bool $upperCaseFirst = false
    ) {
        $this->reflector = $reflector;
        $this->updater = $updater;
        $this->prefix = $prefix;
        $this->upperCaseFirst = $upperCaseFirst;
    }

    public function generate(SourceCode $sourceCode, string $propertyName): SourceCode
    {
        $property = $this->class((string) $sourceCode)->properties()->offsetGet($propertyName);

        $prototype = $this->buildPrototype($property);
        $sourceCode = $this->sourceFromClassName($sourceCode, $property->class()->name());

        return $sourceCode->withSource((string) $this->updater->apply(
            $prototype,
            Code::fromString((string) $sourceCode)
        ));
    }

    private function formatName(string $name)
    {
        if ($this->upperCaseFirst) {
            $name = ucfirst($name);
        }

        return $this->prefix . $name;
    }

    private function buildPrototype(ReflectionProperty $property)
    {
        $builder = SourceCodeBuilder::create();
        $className = $property->class()->name();

        $builder->namespace($className->namespace());
        $method = $builder
            ->class($className->short())
            ->method($this->formatName($property->name()));
        $method->body()->line(sprintf('return $this->%s;', $property->name()));

        $type = $property->inferredTypes()->best();
        if ($type->isDefined()) {
            $method->returnType($type->isClass() ? $type->className()->short() : $type->primitive());
        }

        return $builder->build();
    }

    private function sourceFromClassName(SourceCode $sourceCode, ClassName $className): SourceCode
    {
        $containingClass = $this->reflector->reflectClassLike($className);
        $worseSourceCode = $containingClass->sourceCode();

        if ($worseSourceCode->path() != $sourceCode->path()) {
            return $sourceCode;
        }

        return SourceCode::fromStringAndPath(
            $worseSourceCode->__toString(),
            $worseSourceCode->path()
        );
    }

    private function class(string $source): ReflectionClass
    {
        $classes = $this->reflector->reflectClassesIn($source);

        if ($classes->count() === 0) {
            throw new InvalidArgumentException(
                'No classes in source file'
            );
        }

        if ($classes->count() > 1) {
            throw new InvalidArgumentException(
                'Currently will only generates accessor by name in files with one class'
            );
        }

        return $classes->first();
    }
}
