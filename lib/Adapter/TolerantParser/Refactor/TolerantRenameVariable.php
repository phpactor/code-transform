<?php

namespace Phpactor\CodeTransform\Adapter\TolerantParser\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\Node\Expression\Variable;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\TextEdit;
use Microsoft\PhpParser\Node;
use Phpactor\CodeTransform\Domain\Refactor\RenameVariable;
use Microsoft\PhpParser\FunctionLike;
use Microsoft\PhpParser\ClassLike;
use Microsoft\PhpParser\Node\Parameter;

class TolerantRenameVariable implements RenameVariable
{
    /**
     * @var Parser
     */
    private $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function renameVariable(string $source, int $offset, string $newName, string $scope = self::SCOPE_FILE): SourceCode
    {
        $sourceNode = $this->sourceNode($source);
        $variable = $this->variableNodeFromSource($sourceNode, $offset);
        $scopeNode = $this->scopeNode($variable, $scope);
        $textEdits = $this->textEditsToRename($scopeNode, $variable, $newName);

        return SourceCode::fromString(TextEdit::applyEdits($textEdits, $source));
    }

    private function sourceNode(string $source): SourceFileNode
    {
        return $this->parser->parseSourceFile($source);
    }

    private function variableNodeFromSource(SourceFileNode $sourceNode, int $offset): Node
    {
        $node = $sourceNode->getDescendantNodeAtPosition($offset);

        if (false === $node instanceof Variable && false === $node instanceof Parameter) {
            throw new \InvalidArgumentException(sprintf(
                'Expected Variable or Parameter node, got "%s"',
                get_class($node)
            ));
        }

        return $node;
    }

    private function textEditsToRename(Node $scopeNode, Node $variable, string $newName): array
    {
        /** @var Node $node */
        foreach ($scopeNode->getDescendantNodes() as $node) {
            if (
                false === $node instanceof Variable &&
                false === $node instanceof Parameter
            ) {
                continue;
            }

            if ($node->getText() !== $variable->getText()) {
                continue;
            }

            $textEdits[] = new TextEdit($node->getStart(), $node->getEndPosition() - $node->getStart(), '$' . $newName);
        }

        return $textEdits;
    }

    private function scopeNode(Node $variable, string $scope): Node
    {
        if ($scope === RenameVariable::SCOPE_FILE) {
            return $variable->getRoot();
        }

        $scopeNode = $variable->getFirstAncestor(FunctionLike::class, ClassLike::class, SourceFileNode::class);

        if (null === $scopeNode) {
            throw new \RuntimeException(
                'Could not determine scope node, this should not happen as ' .
                'there should always be a SourceFileNode.'
            );
        }

        return $scopeNode;
    }
}
