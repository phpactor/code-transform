<?php

namespace Phpactor\CodeTransform\Adapter\TolerantParser\Transformer;

use Phpactor\CodeTransform\Domain\Transformer;
use Phpactor\CodeTransform\Domain\SourceCode;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Node\MethodDeclaration;
use Microsoft\PhpParser\Node\Parameter;
use Microsoft\PhpParser\TextEdit;
use Microsoft\PhpParser\Node\Statement\CompoundStatementNode;
use Microsoft\PhpParser\Node\ClassMembersNode;
use Microsoft\PhpParser\Token;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Node;

class CompleteConstructor implements Transformer
{
    private $parser;
    private $indentation;

    public function __construct(Parser $parser = null, int $indentation = 4)
    {
        $this->parser = $parser ?: new Parser();
        $this->indentation = 4;
    }

    public function transform(SourceCode $code): SourceCode
    {
        $node = $this->parser->parseSourceFile((string) $code);
        $edits = $this->generateTextEdits($node);
        $code = SourceCode::fromString(TextEdit::applyEdits($edits, (string) $code));

        return $code;
    }

    private function generateTextEdits(SourceFileNode $node)
    {
        foreach ($node->getChildNodes() as $child) {
            if ($child instanceof ClassDeclaration) {
                // TODO: Process all classes
                return $this->processClass($child) ?: [];
            }
        }

        return [];
    }

    private function processClass(ClassDeclaration $class): array
    {
        $constructor = $this->getConstructor($class);

        if (!$constructor) {
            return [];
        }

        //$existingProperties = $this->getClassProperties($class);
        //$existingAssigns = $this->getClassProperties($class);
        $parameters = $this->getParameters($constructor);

        if (empty($parameters)) {
            return [];
        }

        $classMembers = $class->classMembers;
        $classPos = $classMembers->openBrace->start + 1;
        $properties = $this->generateProperties($classMembers, $parameters);

        $statementNode = $constructor->getFirstDescendantNode(CompoundStatementNode::class);
        $assignPos = $statementNode->openBrace->start + 1;
        $assigns = $this->generateAssigns($statementNode, $parameters);

        return [
            new TextEdit($classPos, 0, PHP_EOL . implode(PHP_EOL, $properties) . PHP_EOL),
            new TextEdit($assignPos, 0, PHP_EOL . implode(PHP_EOL, $assigns)),
        ];
    }

    private function getConstructor(ClassDeclaration $class)
    {
        foreach ($class->classMembers->classMemberDeclarations as $member) {
            if ($member instanceof MethodDeclaration && $member->getName() === '__construct') {
                return $member;
            }
        }
    }

    private function getParameters(MethodDeclaration $method)
    {
        if (!$method->parameters) {
            return [];
        }

        return array_filter($method->parameters->children, function ($child) {
            return $child instanceof Parameter;
        });
    }

    private function generateProperties(ClassMembersNode $members, array $parameters)
    {
        $properties = [];

        $indent = $members->getLeadingCommentAndWhitespaceText();
        $indent = strlen(substr($indent, strrpos($indent, PHP_EOL) + 1)) + $this->indentation;

        foreach ($parameters as $index => $parameter) {
            if ($parameter->typeDeclaration) {
                $properties[] = ($index > 0 ? PHP_EOL : '') . $this->indent($indent, $this->docBlockFromType($members, $parameter->typeDeclaration));
            }
            $properties[] = sprintf('%sprivate $%s;', str_repeat(' ', $indent), $parameter->getName());
        }

        return $properties;
    }

    private function generateAssigns(CompoundStatementNode $node, array $parameters)
    {
        $assigns = [];
        $assignPos = $node->openBrace->start + 1;
        $indent = $node->getLeadingCommentAndWhitespaceText();
        $indent = strlen(substr($indent, strrpos($indent, PHP_EOL) + 1)) + $this->indentation;
        foreach ($parameters as $parameter) {
            $assigns[] = sprintf('%s$this->%s = $%s;', str_repeat(' ', $indent), $parameter->getName(), $parameter->getName());
        }

        return $assigns;
    }

    private function docBlockFromType(Node $node, $tokenOrName)
    {
        $type = $tokenOrName->getText();
        if ($tokenOrName instanceof Token) {
            $type = $tokenOrName->getText($node->getFileContents());
        }
        return <<<EOT
/**
 * @var {$type}
 */
EOT
        ;
    }

    private function indent(int $spaces, string $text)
    {
        $text = explode(PHP_EOL, $text);
        $text = array_map(function ($line) use ($spaces) {
            return str_repeat(' ', $spaces) . $line;
        }, $text);

        return implode(PHP_EOL, $text);
    }
}
