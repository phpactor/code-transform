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
use Microsoft\PhpParser\Node\Expression\AssignmentExpression;
use Microsoft\PhpParser\Node\Statement\ExpressionStatement;
use Microsoft\PhpParser\Node\PropertyDeclaration;
use Phpactor\CodeTransform\Domain\Editor;

class CompleteConstructor implements Transformer
{
    /**
     * @var Editor
     */
    private $editor;

    /**
     * @var Parser
     */
    private $parser;

    public function __construct(Parser $parser = null, Editor $editor = null)
    {
        $this->editor = $editor ?: new Editor();
        $this->parser = $parser ?: new Parser();
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

        $parameters = $this->getParameters($constructor);

        if (empty($parameters)) {
            return [];
        }

        $classMembers = $class->classMembers;
        $classPos = $classMembers->openBrace->start + 1;
        $existingProperties = $this->existingProperties($classMembers, $parameters);
        $properties = $this->generateProperties($classMembers, $parameters, $existingProperties);

        $statementNode = $constructor->getFirstDescendantNode(CompoundStatementNode::class);
        $assignPos = $statementNode->openBrace->start + 1;
        $existingAssigns = $this->existingAssigns($statementNode, $parameters);
        $assigns = $this->generateAssigns($statementNode, $parameters, $existingAssigns);

        $edits = [];
        if ($properties) {
            $edits[] = new TextEdit($classPos, 0, PHP_EOL . implode(PHP_EOL, $properties) . PHP_EOL);
        }

        if ($assigns) {
            $edits[] = new TextEdit($assignPos, 0, PHP_EOL . implode(PHP_EOL, $assigns));
        }

        return $edits;
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

    private function generateProperties(ClassMembersNode $members, array $parameters, array $existing)
    {
        $properties = [];

        foreach ($parameters as $index => $parameter) {
            if (in_array($parameter->getName(), $existing)) {
                continue;
            }
            if ($parameter->typeDeclaration) {
                $properties[] = 
                    ($index > 0 ? PHP_EOL : '') . 
                    $this->editor->edit(
                        $this->docBlockFromType($members, $parameter->typeDeclaration)
                    )->indent(1);
            }
            $properties[] = $this->editor->edit(sprintf('private $%s;', $parameter->getName()))->indent(1);
        }

        return $properties;
    }

    private function existingProperties(ClassMembersNode $node, array $parameters)
    {
        /** @var $node Node */
        return array_reduce($node->classMemberDeclarations, function ($list, $node) use ($parameters) {
            if (!$node instanceof PropertyDeclaration) {
                return $list;
            }

            foreach ($node->propertyElements->getChildNodes() as $variable) {
                $list[] = $variable->getName();
            }

            return $list;
        }, []);
    }

    private function existingAssigns(CompoundStatementNode $node, array $parameters)
    {
        /** @var $node Node */
        return array_reduce($node->statements, function ($list, $node) use ($parameters) {
            if ($node instanceof ExpressionStatement && $node->expression instanceof AssignmentExpression) {

                $varName = $node->expression->rightOperand->getText();
                $memberName = $node->expression->leftOperand->memberName->getText($node->getFileContents());

                $list[] = $memberName;
            }

            return $list;
        }, []);
    }

    private function generateAssigns(CompoundStatementNode $node, array $parameters, array $existing)
    {
        $assigns = [];
        $assignPos = $node->openBrace->start + 1;
        foreach ($parameters as $parameter) {
            if (in_array($parameter->getName(), $existing)) {
                continue;
            }

            $assigns[] = (string) $this->editor->edit(sprintf(
                '$this->%s = $%s;',
                $parameter->getName(),
                $parameter->getName()
            ))->indent(2);
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
}
