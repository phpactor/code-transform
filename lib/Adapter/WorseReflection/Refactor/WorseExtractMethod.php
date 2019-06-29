<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Refactor;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Node\Statement\CompoundStatementNode;
use Microsoft\PhpParser\Node\Statement\ReturnStatement;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\WorseReflection\Reflector;
use Microsoft\PhpParser\Parser;
use Phpactor\CodeBuilder\Domain\BuilderFactory;
use Phpactor\CodeBuilder\Domain\Code;
use Microsoft\PhpParser\Token;
use Microsoft\PhpParser\TokenKind;
use Phpactor\WorseReflection\Core\Inference\Assignments;
use Phpactor\WorseReflection\Core\Inference\Variable;
use Phpactor\CodeTransform\Domain\Refactor\ExtractMethod;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\CodeBuilder\Domain\Builder\MethodBuilder;
use Phpactor\CodeTransform\Domain\Exception\TransformException;
use Phpactor\CodeTransform\Domain\Utils\TextUtils;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;

class WorseExtractMethod implements ExtractMethod
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

    /**
     * @var BuilderFactory
     */
    private $factory;

    public function __construct(Reflector $reflector, BuilderFactory $factory, Updater $updater, Parser $parser = null)
    {
        $this->reflector = $reflector;
        $this->updater = $updater;
        $this->parser = $parser ?: new Parser();
        $this->factory = $factory;
    }

    public function extractMethod(SourceCode $source, int $offsetStart, int $offsetEnd, string $name): SourceCode
    {
        $isExpression = $this->isSelectionAnExpression($source, $offsetStart, $offsetEnd);

        $selection = $source->extractSelection($offsetStart, $offsetEnd);
        $builder = $this->factory->fromSource($source);
        $reflectionMethod = $this->reflectMethod($offsetEnd, $source, $name);

        $methodBuilder = $this->createMethodBuilder($reflectionMethod, $builder, $name);
        $newMethodBody = $this->removeIndentation($selection);
        if ($isExpression) {
            $newMethodBody = $this->addExpressionReturn($newMethodBody, $source, $offsetEnd, $methodBuilder);
        }
        $methodBuilder->body()->line($newMethodBody);

        $locals = $this->scopeLocalVariables($source, $offsetStart, $offsetEnd);

        $parameterVariables = $this->parameterVariables($locals->lessThan($offsetStart), $selection, $offsetStart);
        $args = $this->addParametersAndGetArgs($parameterVariables, $methodBuilder, $builder);

        $returnVariables = $this->returnVariables($locals, $reflectionMethod, $source, $offsetStart, $offsetEnd);

        $returnAssignment = $this->addReturnAndGetAssignment(
            $returnVariables,
            $methodBuilder,
            $args
        );

        $prototype = $builder->build();

        $replacement = $this->replacement($name, $args, $selection, $returnAssignment);

        if ($isExpression) {
            $replacement = rtrim($replacement, ';');
        }

        $source = $source->replaceSelection(
            $replacement,
            $offsetStart,
            $offsetEnd
        );

        return $source->withSource(
            (string) $this->updater->apply($prototype, Code::fromString((string) $source))
        );
    }

    private function parameterVariables(Assignments $locals, string $selection, int $offsetStart)
    {
        $variableNames = $this->variableNames($selection);

        $parameterVariables = [];
        foreach ($variableNames as $variable) {
            $variables = $locals->lessThanOrEqualTo($offsetStart)->byName($variable);
            if ($variables->count()) {
                $parameterVariables[$variable] = $variables->last();
            }
        }

        return $parameterVariables;
    }

    private function returnVariables(Assignments $locals, ReflectionMethod $reflectionMethod, string $source, int $offsetStart, int $offsetEnd)
    {
        // variables that are:
        //
        // - defined in the selection
        // - and used in the parent scope
        // - after the end offset
        $tailDependencies = $this->variableNames(
            $tail = mb_substr(
                $source,
                $offsetEnd,
                $reflectionMethod->position()->end() - $offsetEnd
            )
        );

        $returnVariables = [];
        foreach ($tailDependencies as $variable) {
            $variables = $locals->byName($variable)
                ->lessThanOrEqualTo($offsetEnd)
                ->greaterThanOrEqualTo($offsetStart);

            if ($variables->count()) {
                $returnVariables[$variable] = $variables->last();
            }
        }

        return $returnVariables;
    }

    private function removeIndentation(string $selection)
    {
        return TextUtils::removeIndentation($selection);
    }

    private function createMethodBuilder(ReflectionMethod $reflectionMethod, SourceCodeBuilder $builder, string $name): MethodBuilder
    {
        $methodBuilder = $builder->class(
            $reflectionMethod->class()->name()->short()
        )->method($name);
        $methodBuilder->visibility('private');

        return $methodBuilder;
    }

    private function reflectMethod(int $offsetEnd, string $source, string $name): ReflectionMethod
    {
        $offset = $this->reflector->reflectOffset($source, $offsetEnd);
        $thisVariable = $offset->frame()->locals()->byName('this');

        if (empty($thisVariable)) {
            throw new TransformException('Cannot extract method, not in class scope');
        }

        $className = $thisVariable->last()->symbolContext()->type()->className();

        if (!$className) {
            throw new TransformException('Cannot extract method, not in class scope');
        }

        $reflectionClass = $this->reflector->reflectClass((string) $className);

        $methods = $reflectionClass->methods();
        if ($methods->belongingTo($className)->has($name)) {
            throw new TransformException(sprintf('Class "%s" already has method "%s"', (string) $className, $name));
        }

        // returns the method that the offset is within
        return $methods->belongingTo($className)->atOffset($offsetEnd)->first();
    }

    private function addParametersAndGetArgs(array $freeVariables, $methodBuilder, SourceCodeBuilder $builder): array
    {
        $args = [];

        /** @var Variable $freeVariable */
        foreach ($freeVariables as $freeVariable) {
            if (in_array($freeVariable->name(), [ 'this', 'self' ])) {
                continue;
            }

            $parameterBuilder = $methodBuilder->parameter($freeVariable->name());
            $variableType = $freeVariable->symbolContext()->type();

            if ($variableType->isDefined()) {
                $parameterBuilder->type($variableType->short());
                if ($variableType->isClass()) {
                    $builder->use((string) $variableType);
                }
            }

            $args[] = '$' . $freeVariable->name();
        }

        return $args;
    }

    private function scopeLocalVariables(SourceCode $source, int $offsetStart, int $offsetEnd): Assignments
    {
        return $this->reflector->reflectOffset(
            (string) $source,
            $offsetEnd
        )->frame()->locals();
    }

    private function variableNames(string $source)
    {
        $node = $this->parseSelection($source);
        $variables = [];

        /** @var Token $token */
        foreach ($node->getDescendantTokens() as $token) {
            if ($token->kind == TokenKind::VariableName) {
                $text = $token->getText($node->getFileContents());
                if (is_string($text)) {
                    $name = substr($text, 1);
                    $variables[$name] = $name;
                }
            }
        }

        return array_values($variables);
    }

    private function addReturnAndGetAssignment(array $returnVariables, MethodBuilder $methodBuilder, array $args)
    {
        $returnVariables = array_filter($returnVariables, function (Variable $variable) {
            return false === in_array($variable->name(), ['self', 'this']);
        });

        $returnVariables = array_filter($returnVariables, function (Variable $variable) use ($args) {
            if ($variable->symbolContext()->type()->isPrimitive()) {
                return true;
            }

            return false === in_array('$' . $variable->name(), $args);
        });

        if (empty($returnVariables)) {
            return null;
        }

        if (count($returnVariables) === 1) {
            /** @var Variable $variable */
            $variable = reset($returnVariables);
            $methodBuilder->body()->line('return $' . $variable->name() . ';');

            if ($variable->symbolContext()->type()->isDefined()) {
                $type = $variable->symbolContext()->type();
                $className = $type->className();
                $methodBuilder->returnType($type->short());
                if ($className) {
                    $methodBuilder->end()->end()->use($className->full());
                }
            }

            return '$' . $variable->name();
        }

        $names = implode(', ', array_map(function (Variable $variable) {
            return '$' . $variable->name();
        }, $returnVariables));

        $methodBuilder->body()->line('return [' . $names . '];');
        $methodBuilder->returnType('array');

        return 'list(' . $names . ')';
    }

    private function replacement(string $name, array $args, string $selection, ?string $returnAssignment)
    {
        $indentation = str_repeat(' ', TextUtils::stringIndentation($selection));
        $callString = '$this->'  . $name . '(' . implode(', ', $args) . ');';

        if (empty($returnAssignment)) {
            $replacement = $indentation . $callString;
            $selectionRootNode = $this->parseSelection($selection);

            if ($selectionRootNode->getFirstDescendantNode(ReturnStatement::class)) {
                $replacement = 'return ' . $replacement;
            }

            return $replacement;
        }

        return $indentation . $returnAssignment . ' = ' . $callString;
    }

    private function isSelectionAnExpression(SourceCode $source, int $offsetStart, int $offsetEnd)
    {
        $node = $this->parser->parseSourceFile($source->__toString());
        $endNode = $node->getDescendantNodeAtPosition($offsetEnd);
        
        // end node is in the statement body, get last child node
        if ($endNode instanceof CompoundStatementNode) {
            $childNodes = iterator_to_array($endNode->getChildNodes());
            $endNode = end($childNodes);
            assert($endNode instanceof Node);
        }
        
        // get the positional parent of the node
        while ($endNode->parent && $endNode->getEndPosition() === $endNode->parent->getEndPosition()) {
            $endNode = $endNode->parent;
        }
        
        return !$endNode->parent instanceof CompoundStatementNode;
    }

    private function addExpressionReturn($newMethodBody, SourceCode $source, int $offsetEnd, MethodBuilder $methodBuilder): string
    {
        $newMethodBody = 'return ' . $newMethodBody .';';
        $offset = $this->reflector->reflectOffset($source->__toString(), $offsetEnd);
        $expressionTypes = $offset->symbolContext()->types();
        if ($expressionTypes->count() === 1) {
            $type = $expressionTypes->best();
            if ($type->isDefined()) {
                $methodBuilder->returnType($type->short());
            }
            $className = $type->className();
            if ($className) {
                $methodBuilder->end()->end()->use($className->full());
            }
        }
        return $newMethodBody;
    }

    private function parseSelection(string $source): SourceFileNode
    {
        $source = '<?php ' . $source;
        $node = $this->parser->parseSourceFile($source);
        return $node;
    }
}
