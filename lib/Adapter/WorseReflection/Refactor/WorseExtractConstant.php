<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Refactor;

use Phpactor\CodeTransform\Domain\Refactor\ExtractConstant;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\Offset;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\CodeBuilder\Domain\Prototype\Prototype;
use Phpactor\CodeBuilder\SourceBuilder;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\WorseReflection\Core\Reflection\Inference\Symbol;

class WorseExtractConstant implements ExtractConstant
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
        $this->reflector = $reflector;
        $this->updater = $updater;
    }

    public function extractConstant(string $sourceCode, int $offset, string $constantName)
    {
        $offset = $this->reflector->reflectOffset(SourceCode::fromString($sourceCode), Offset::fromInt($offset));
        $symbolInformation = $offset->symbolInformation();
        $symbol = $symbolInformation->symbol();

        $sourceCode = $this->replaceValueWithConstant($sourceCode, $symbol, $constantName);

        $builder = SourceCodeBuilder::create();
        $builder->namespace((string) $symbolInformation->containerType()->className()->namespace());
        $builder
            ->class((string) $symbolInformation->containerType()->className()->short())
                ->constant($constantName, $symbolInformation->value())
            ->end();

        $sourceCode = $this->updater->apply($builder->build(), Code::fromString($sourceCode));

        return $sourceCode;
    }

    private function replaceValueWithConstant(string $sourceCode, Symbol $symbol, $constantName)
    {
        return implode([
            substr($sourceCode, 0, $symbol->position()->start()),
            'self::',
            $constantName,
            substr($sourceCode, $symbol->position()->end())
        ]);
    }
}
