<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Refactor;

use Phpactor\CodeTransform\Domain\Refactor\ExtractConstant;
use Phpactor\WorseReflection\Reflector;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\ClassLike;
use Microsoft\PhpParser\Node\StringLiteral;
use Microsoft\PhpParser\Node\NumericLiteral;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\TextEdit;
use Phpactor\WorseReflection\Core\Inference\SymbolContext;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Domain\Exception\TransformException;

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

    public function extractConstant(SourceCode $sourceCode, int $offset, string $constantName): SourceCode
    {
        $symbolInformation = $this->reflector
            ->reflectOffset($sourceCode->__toString(), $offset)
            ->symbolContext();

        $sourceCode = $this->replaceValues($sourceCode, $offset, $constantName);

        return $this->addConstant($sourceCode, $symbolInformation, $constantName);
    }

    private function addConstant(SourceCode $sourceCode, SymbolContext $symbolInformation, string $constantName)
    {
        $symbol = $symbolInformation->symbol();

        $builder = SourceCodeBuilder::create();
        $containerType = $symbolInformation->containerType();

        if (!$containerType) {
            throw new TransformException(sprintf('Could not find container class'));
        }

        $className = $containerType->className();

        if (!$className) {
            throw new TransformException(sprintf('Could not find container class'));
        }

        $builder->namespace($className->namespace());
        $builder
            ->class($className->short())
                ->constant($constantName, $symbolInformation->value())
            ->end();

        return $sourceCode->withSource($this->updater->apply($builder->build(), Code::fromString($sourceCode)));
    }

    private function replaceValues(SourceCode $sourceCode, int $offset, string $constantName): SourceCode
    {
        $node = $this->parser->parseSourceFile($sourceCode->__toString());
        $targetNode = $node->getDescendantNodeAtPosition($offset);
        $targetValue = $this->getComparableValue($targetNode);
        $classNode = $targetNode->getFirstAncestor(ClassLike::class);

        if (null === $classNode) {
            throw new TransformException('Node does not belong to a class');
        }

        $textEdits = [];
        foreach ($classNode->getDescendantNodes() as $node) {
            if (!$node instanceof $targetNode) {
                continue;
            }

            if ($targetValue == $this->getComparableValue($node)) {
                $textEdits[] = new TextEdit(
                    $node->getStart(),
                    $node->getEndPosition() - $node->getStart(),
                    'self::' . $constantName
                );
            }
        }

        return $sourceCode->withSource(TextEdit::applyEdits($textEdits, $sourceCode->__toString()));
    }

    private function getComparableValue(Node $node)
    {
        if ($node instanceof StringLiteral) {
            return $node->getStringContentsText();
        }

        if ($node instanceof NumericLiteral) {
            return $node->getText();
        }

        throw new TransformException(sprintf(
            'Do not know how to replace node of type "%s"',
            get_class($node)
        ));
    }
}
