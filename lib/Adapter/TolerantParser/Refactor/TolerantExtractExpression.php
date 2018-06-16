<?php

namespace Phpactor\CodeTransform\Adapter\TolerantParser\Refactor;

use Exception;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\StatementNode;
use Microsoft\PhpParser\Node\Statement\ExpressionStatement;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\TextEdit;
use Phpactor\CodeTransform\Domain\Exception\TransformException;
use Phpactor\CodeTransform\Domain\SourceCode;

class TolerantExtractExpression
{
    /**
     * @var Parser
     */
    private $parser;

    public function __construct(Parser $parser = null)
    {
        $this->parser = $parser ?: new Parser();
    }

    public function extractExpression(SourceCode $source, int $offsetStart, int $offsetEnd = null, string $variableName): SourceCode
    {
        $rootNode = $this->parser->parseSourceFile((string) $source);
        $startNode = $rootNode->getDescendantNodeAtPosition($offsetStart);

        if ($offsetEnd) {
            $endNode = $rootNode->getDescendantNodeAtPosition($offsetEnd);
        }

        if (null === $startNode) {
            return $source;
        }

        $startNode = $this->outerNode($startNode);

        $startPosition = $startNode->getStart();
        $endPosition = $offsetEnd ? $endNode->getEndPosition() : $startNode->getEndPosition();

        $extractedString = rtrim(trim($source->extractSelection($startPosition, $endPosition)), ';');
        $assigment = sprintf('$%s = %s;', $variableName, $extractedString) . PHP_EOL;

        if (!$startNode instanceof StatementNode) {
            $statement = $startNode->getFirstAncestor(StatementNode::class);
        } else {
            $statement = $startNode;
        }

        if (null === $statement) {
            return $source;
        }

        $edits = $this->resolveEdits($statement, $startNode, $extractedString, $assigment, $variableName);

        return $source->withSource(TextEdit::applyEdits($edits, (string) $source));
    }

    private function resolveEdits(
        Node $statement,
        Node $startNode,
        string $extractedString,
        string $assigment,
        string $variableName
    )
    {
        if ($statement->getStart() === $startNode->getStart()) {
            return [
                new TextEdit($statement->getStart(), $statement->getWidth(), $assigment)
            ];
        }
        
        return [
            new TextEdit($statement->getStart(), 0, $assigment),
            new TextEdit($startNode->getStart(), strlen($extractedString), '$' . $variableName),
        ];
    }

    private function outerNode(Node $node)
    {
        $parent = $node->getParent();

        if (null === $parent) {
            return $node;
        }

        if ($parent->getStart() !== $node->getStart()) {
            return $node;
        }

        return $this->outerNode($parent);
    }
}
