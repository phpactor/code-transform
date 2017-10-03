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
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\ClassLike;
use Microsoft\PhpParser\Node\StringLiteral;
use Microsoft\PhpParser\Node\NumericLiteral;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\TextEdit;
use Phpactor\WorseReflection\Core\Reflection\Inference\SymbolInformation;

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

    /**
     * @var Parser
     */
    private $parser;

    public function __construct(Reflector $reflector, Updater $updater, Parser $parser = null)
    {
        $this->reflector = $reflector;
        $this->updater = $updater;
        $this->parser = $parser ?: new Parser();
    }

    public function extractConstant(string $sourceCode, int $offset, string $constantName)
    {
        $symbolInformation = $this->reflector
            ->reflectOffset(SourceCode::fromString($sourceCode), Offset::fromInt($offset))
            ->symbolInformation();

        $sourceCode = $this->replaceValues($sourceCode, $offset, $constantName);
        $sourceCode = $this->addConstant($sourceCode, $symbolInformation, $constantName);

        return $sourceCode;
    }

    private function addConstant(string $sourceCode, SymbolInformation $symbolInformation, string $constantName)
    {
        $symbol = $symbolInformation->symbol();

        $builder = SourceCodeBuilder::create();
        $builder->namespace((string) $symbolInformation->containerType()->className()->namespace());
        $builder
            ->class((string) $symbolInformation->containerType()->className()->short())
                ->constant($constantName, $symbolInformation->value())
            ->end();

        $sourceCode = $this->updater->apply($builder->build(), Code::fromString($sourceCode));

        return $sourceCode;
    }

    private function replaceValues(string $sourceCode, int $offset, string $constantName)
    {
        $node = $this->parser->parseSourceFile($sourceCode);
        $targetNode = $node->getDescendantNodeAtPosition($offset);
        $targetValue = $this->getComparableValue($targetNode);
        $classNode = $targetNode->getFirstAncestor(ClassLike::class);

        if (null === $classNode) {
            throw new \RuntimeException('Node does not belong to a class');
        }

        $textEdits = [];
        foreach ($classNode->getDescendantNodes() as $node) {
            if (false === $node instanceof $targetNode) {
                continue;
            }

            if ($targetValue == $this->getComparableValue($node)) {
                $textEdits[] = new TextEdit(
                    $node->getStart(), $node->getEndPosition() - $node->getStart(),
                    'self::' . $constantName
                );
            }
        }

        $sourceCode = TextEdit::applyEdits($textEdits, $sourceCode);

        return $sourceCode;
    }

    private function getComparableValue(Node $node)
    {
        if ($node instanceof StringLiteral) {
            return $node->getStringContentsText();
        }

        if ($node instanceof NumericLiteral) {
            return $node->getText();
        }

        throw new \InvalidArgumentException(sprintf(
            'Do not know how to replace node of type "%s"', get_class($node)
        ));
    }
}

