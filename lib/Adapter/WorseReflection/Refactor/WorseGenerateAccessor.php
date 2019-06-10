<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Refactor;

use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Reflector;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\WorseReflection\Core\Inference\Symbol;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\CodeTransform\Domain\Refactor\GenerateAccessor;
use Phpactor\WorseReflection\Core\Inference\SymbolContext;
use Phpactor\CodeTransform\Domain\Exception\TransformException;

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

    public function generateFromOffset(SourceCode $sourceCode, int $offset): SourceCode
    {
        $info = $this->reflector
            ->reflectOffset($sourceCode->__toString(), $offset)
            ->symbolContext();

        if ($info->symbol()->symbolType() !== Symbol::PROPERTY) {
            throw new TransformException(sprintf(
                'Symbol at offset "%s" is not a property, it is a symbol of type "%s"',
                $offset,
                $info->symbol()->symbolType()
            ));
        }

        return $this->generate($info, $sourceCode);
    }

    public function generateFromPropertyName(SourceCode $sourceCode, string $propertyName): SourceCode
    {
        $class = $this->class((string) $sourceCode);
        $property = $class->properties()->get($propertyName);

        $info = SymbolContext::for(Symbol::fromTypeNameAndPosition(
            Symbol::PROPERTY,
            $property->name(),
            $property->position()
        ))->withContainerType(Type::class($class->name()))
        ->withTypes($property->docblock()->vars()->types())
        ;

        return $this->generate($info, $sourceCode);
    }

    private function formatName(string $name)
    {
        if ($this->upperCaseFirst) {
            $name = ucfirst($name);
        }

        return $this->prefix . $name;
    }

    private function buildPrototype(SymbolContext $info)
    {
        $builder = SourceCodeBuilder::create();
        $builder->namespace($info->containerType()->className()->namespace());
        $method = $builder
            ->class($info->containerType()->className()->short())
            ->method($this->formatName($info->symbol()->name()));
        $method->body()->line(sprintf('return $this->%s;', $info->symbol()->name()));

        if ($info->type()->isDefined()) {
            $method->returnType($info->type()->isClass() ? $info->type()->className()->short() : $info->type()->primitive());
        }

        return $builder->build();
    }

    private function sourceFromSymbolInformation(SourceCode $sourceCode, SymbolContext $info): SourceCode
    {
        $containingClass = $this->reflector->reflectClassLike($info->containerType()->className());
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

    private function generate(SymbolContext $info, SourceCode $sourceCode)
    {
        $prototype = $this->buildPrototype($info);
        $sourceCode = $this->sourceFromSymbolInformation($sourceCode, $info);
        
        return $sourceCode->withSource(
            (string) $this->updater->apply($prototype, Code::fromString((string) $sourceCode))
        );
    }
}
