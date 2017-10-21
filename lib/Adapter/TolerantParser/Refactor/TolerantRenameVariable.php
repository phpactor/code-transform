<?php

namespace Phpactor\CodeTransform\Adapter\TolerantParser\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\Node\Expression\Variable;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\TextEdit;
use Microsoft\PhpParser\Node;

class TolerantRenameVariable
{
    /**
     * @var Parser
     */
    private $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function renameVariable(string $source, int $offset, string $newName): SourceCode
    {
        $sourceNode = $this->sourceNode($source);
        $variable = $this->variableFromSource($sourceNode, $offset);
        $textEdits = $this->textEditsToRename($sourceNode, $variable, $newName);

        return SourceCode::fromString(TextEdit::applyEdits($textEdits, $source));
    }

    private function sourceNode(string $source): SourceFileNode
    {
        return $this->parser->parseSourceFile($source);
    }

    private function variableFromSource(SourceFileNode $sourceNode, int $offset): Variable
    {
        return $sourceNode->getDescendantNodeAtPosition($offset);
    }

    private function textEditsToRename(SourceFileNode $sourceNode, Variable $variable, string $newName): array
    {
        /** @var Node $node */
        foreach ($sourceNode->getDescendantNodes() as $node) {
            if (false === $node instanceof Variable) {
                continue;
            }

            if ($node->getText() !== $variable->getText()) {
                continue;
            }

            $textEdits[] = new TextEdit($node->getStart(), $node->getEndPosition() - $node->getStart(), '$' . $newName);
        }

        return $textEdits;
    }
}

