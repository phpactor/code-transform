<?php

namespace Phpactor\CodeTransform\Adapter\TolerantParser\Refactor;

use Microsoft\PhpParser\FunctionLike;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression;
use Microsoft\PhpParser\Node\StatementNode;
use Microsoft\PhpParser\Node\Statement\ExpressionStatement;
use Microsoft\PhpParser\Parser;
use Phpactor\CodeTransform\Domain\Refactor\ExtractExpression;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\TextDocument\TextEdit;
use Phpactor\TextDocument\TextEdits;
use function end;
use function iterator_to_array;

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

    public function canExtractExpression(SourceCode $source, int $offsetStart, ?int $offsetEnd = null): bool
    {
        return $this->getExtractedExpression($source, $offsetStart, $offsetEnd) !== null;
    }

    public function extractExpression(SourceCode $source, int $offsetStart, ?int $offsetEnd = null, string $variableName): TextEdits
    {
        $expression = $this->getExtractedExpression($source, $offsetStart, $offsetEnd);
        if($expression === null)
            return TextEdits::none();

        $startPosition = $expression->getStart();
        $endPosition = $expression->getEndPosition();

        $extractedString = rtrim(trim($source->extractSelection($startPosition, $endPosition)), ';');
        $assigment = sprintf('$%s = %s;', $variableName, $extractedString) . PHP_EOL;

        $statement = $expression->getFirstAncestor(StatementNode::class);
        assert($statement instanceof StatementNode);

        $edits = $this->resolveEdits($statement, $expression, $extractedString, $assigment, $variableName);

        return TextEdits::fromTextEdits($edits);
    }

    private function getExtractedExpression(SourceCode $source, int $offsetStart, ?int $offsetEnd): ?Expression
    {
        $rootNode = $this->parser->parseSourceFile((string) $source);
        $startNode = $rootNode->getDescendantNodeAtPosition($offsetStart);

        if ($offsetEnd) {
            $endNode = $rootNode->getDescendantNodeAtPosition($offsetEnd);

            $expression = $this->getCommonExpression($startNode, $endNode);
            if($expression === null) {
                // check if $endNode is not the whole row - try the last child
                $children = iterator_to_array($endNode->getDescendantNodes());
                $endNode = end($children);
                if($endNode === false) {
                    return null;
                }
                $expression = $this->getCommonExpression($startNode, $endNode);
            }
        } else {
            $expression = $this->outerExpression($startNode);
        }

        if($expression === null) {
            return null;
        }
        return $expression;
    }
    
    private function getCommonExpression(Node $node1, Node $node2): ?Expression
    {
        if($node1 == $node2 && $node1 instanceof Expression)
            return $node1;
        $ancestor = $node1;
        $expressions = [];
        if($node1 instanceof Expression)
            $expressions[] = $node1;

        while (($ancestor = $ancestor->parent) !== null) {
            if ($ancestor instanceof FunctionLike) {
                break;
            }
            if($ancestor instanceof Expression === false) {
                continue;
            }
            $expressions[] = $ancestor;
        }

        if(empty($expressions))
            return null;

        $ancestor = $node2;
        while (($ancestor = $ancestor->parent) !== null) {
            if(in_array($ancestor, $expressions, true)) {
                return $ancestor;
            }
        }

        return null;
    }
    /**
     * @return array<TextEdit>
     */
    private function resolveEdits(
        Node $statement,
        Node $expression,
        string $extractedString,
        string $assignment,
        string $variableName
    ): array {
        if ($statement->getStart() === $expression->getStart()) {
            return [
                TextEdit::create($statement->getStart(), $statement->getWidth(), $assignment)
            ];
        }

        $indentation = mb_substr_count($statement->getLeadingCommentAndWhitespaceText(), ' ');
        
        return [
            TextEdit::create($statement->getStart(), 0, $assignment . str_repeat(' ', $indentation)),
            TextEdit::create($expression->getStart(), strlen($extractedString), '$' . $variableName),
        ];
    }

    private function outerExpression(Node $node, Node $originalNode = null): ?Expression
    {
        $originalNode = $originalNode ?: $node;

        $parent = $node->getParent();

        if (null === $parent) {
            return $node instanceof Expression ? $node : null;
        }

        if ($parent->getStart() !== $originalNode->getStart() && $originalNode instanceof Expression) {
            return $originalNode;
        }

        return $this->outerExpression($parent, $originalNode);
    }
}
