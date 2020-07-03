<?php

namespace Phpactor\CodeTransform\Adapter\TolerantParser\Refactor;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\StatementNode;
use Microsoft\PhpParser\Parser;
use Phpactor\CodeTransform\Domain\Refactor\ExtractExpression;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\TextDocument\TextEdit;
use Phpactor\TextDocument\TextEdits;

class TolerantExtractExpression implements ExtractExpression
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

        $endPosition = $startNode->getEndPosition();
        if ($offsetEnd) {
            $endNode = $rootNode->getDescendantNodeAtPosition($offsetEnd);
            $endPosition = $endNode->getEndPosition();
        }

        $startNode = $this->outerNode($startNode);

        $startPosition = $startNode->getStart();
        $endPosition = $offsetEnd ?  : $startNode->getEndPosition();

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

        assert($statement instanceof StatementNode);

        $edits = $this->resolveEdits($statement, $startNode, $extractedString, $assigment, $variableName);

        return $source->withSource(TextEdits::fromTextEdits($edits)->apply((string) $source));
    }

    /**
     * @return array<TextEdit>
     */
    private function resolveEdits(
        Node $statement,
        Node $startNode,
        string $extractedString,
        string $assigment,
        string $variableName
    ): array {
        if ($statement->getStart() === $startNode->getStart()) {
            return [
                TextEdit::create($statement->getStart(), $statement->getWidth(), $assigment)
            ];
        }

        $indentation = mb_substr_count($statement->getLeadingCommentAndWhitespaceText(), ' ');
        
        return [
            TextEdit::create($statement->getStart(), 0, $assigment . str_repeat(' ', $indentation)),
            TextEdit::create($startNode->getStart(), strlen($extractedString), '$' . $variableName),
        ];
    }

    private function outerNode(Node $node, Node $originalNode = null): Node
    {
        $originalNode = $originalNode ?: $node;

        $parent = $node->getParent();

        if (null === $parent) {
            return $node;
        }

        if ($parent->getStart() !== $originalNode->getStart()) {
            return $originalNode;
        }

        return $this->outerNode($parent, $originalNode);
    }
}
