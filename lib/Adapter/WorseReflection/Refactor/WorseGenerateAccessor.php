<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Refactor;

use Phpactor\WorseReflection\Reflector;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\WorseReflection\Core\Inference\Symbol;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\CodeTransform\Domain\Refactor\GenerateAccessor;
use Phpactor\WorseReflection\Core\Inference\SymbolInformation;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\ClassName;

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

    public function generateAccessor(string $sourceCode, int $offset): SourceCode
    {
        $info = $this->getInfo($sourceCode, $offset);
        $prototype = $this->buildPrototype($info);

        return SourceCode::fromStringAndPath(
            (string) $this->updater->apply($prototype, Code::fromString($sourceCode)),
            $this->filePathFromType($info->containerType()->className())
        );
    }

    private function getInfo(string $sourceCode, int $offset)
    {
        $reflectionOffset = $this->reflector->reflectOffset($sourceCode, $offset);
        $info = $reflectionOffset->symbolInformation();

        if ($info->symbol()->symbolType() !== Symbol::PROPERTY) {
            throw new \RuntimeException(sprintf(
                'Symbol at offset "%s" is not a property, it is a symbol of type "%s"',
                $offset,
                $info->symbol()->symbolType()
            ));
        }

        return $info;
    }

    private function filePathFromType(ClassName $className)
    {
        return $this->reflector->reflectClassLike($className)->sourceCode()->path();
    }

    private function formatName(string $name)
    {
        if ($this->upperCaseFirst) {
            $name = ucfirst($name);
        }

        return $this->prefix . $name;
    }

    private function buildPrototype(SymbolInformation $info)
    {
        $builder = SourceCodeBuilder::create();
        $builder->namespace($info->containerType()->className()->namespace());
        $method = $builder
            ->class($info->containerType()->className()->short())
            ->method($this->formatName($info->symbol()->name()));
        $method->body()->line(sprintf('return $this->%s;', $info->symbol()->name()));

        if ($info->type()->isDefined()) {
            $method->returnType($info->type()->isClass() ? $info->type()->className()->short() : (string) $info->type());
        }

        return $builder->build();
    }
}
