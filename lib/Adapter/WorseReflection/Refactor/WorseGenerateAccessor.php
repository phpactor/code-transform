<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Refactor;

use Phpactor\WorseReflection\Reflector;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\WorseReflection\Core\Inference\Symbol;
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

    public function generateAccessor(string $sourceCode, int $offset, $methodName = null): SourceCode
    {
        $reflectionOffset = $this->reflector->reflectOffset($sourceCode, $offset);
        $info = $reflectionOffset->symbolInformation();
        $symbol = $info->symbol();

        if ($symbol->symbolType() !== Symbol::PROPERTY) {
            throw new \RuntimeException(sprintf(
                'Symbol at offset "%s" is not a property, it is a symbol of type "%s"',
                $offset,
                $symbol->symbolType()
            ));
        }

        $builder = SourceCodeBuilder::create();
        $builder->namespace((string) $info->containerType()->className()->namespace());
        $method = $builder
            ->class((string) $info->containerType()->className()->short())
            ->method($this->formatName($symbol->name()));

        if ($info->type()->isDefined()) {
            $method->returnType($info->type()->isClass() ? $info->type()->className()->short() : (string) $info->type());
        }

        return SourceCode::fromString((string) $this->updater->apply($builder->build(), Code::fromString($sourceCode)));
    }

    private function formatName(string $name)
    {
        if ($this->upperCaseFirst) {
            $name = ucfirst($name);
        }

        return $this->prefix . $name;
    }
}
