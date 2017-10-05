<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Refactor;

use Microsoft\PhpParser\Node\Expression\ArgumentExpression;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Parser;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\CodeBuilder\Domain\Prototype\Visibility;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeTransform\Domain\Refactor\GenerateMethod;
use Phpactor\WorseReflection\Core\Offset;
use Phpactor\WorseReflection\Core\Reflection\Inference\Symbol;
use Phpactor\WorseReflection\Core\Reflection\Inference\SymbolInformation;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Reflector;

class WorseGenerateMethod implements GenerateMethod
{
    const VAR_NAME_THIS = '$this';

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

    /** @var int
     */
    private $methodSuffixIndex = 0;

    public function __construct(Reflector $reflector, Updater $updater, Parser $parser = null)
    {
        $this->reflector = $reflector;
        $this->updater = $updater;
        $this->parser = $parser ?: new Parser();
    }

    public function generateMethod(string $sourceCode, int $offset, $methodName = null)
    {
        $parameters = $this->parameters($sourceCode, $offset);
        $sourceCode = $this->addMethod($sourceCode, $offset, $parameters);

        return $sourceCode;
    }

    private function parameters(string $sourceCode, int $offset)
    {
        $node = $this->parser->parseSourceFile($sourceCode);
        $methodNode = $node->getDescendantNodeAtPosition($offset);

        if ($methodNode instanceof MemberAccessExpression) {
            $methodNode = $methodNode->getParent();
        }

        if (!isset($methodNode->parent->expression->callableExpression->dereferencableExpression)) {
            throw new \InvalidArgumentException(sprintf(
                'Only $this->methodName() for generation is supported'
            ));
        }

        $base = $methodNode->parent->expression->callableExpression->dereferencableExpression->name->getText($sourceCode);

        if ($base !== self::VAR_NAME_THIS) {
            throw new \InvalidArgumentException(
                'Only support generating new methods on ' . self::VAR_NAME_THIS
            );
        }

        if (false === $methodNode instanceof CallExpression) {
            throw new \InvalidArgumentException(sprintf(
                'Node does not part of a call expression, it is a "%s"',
                get_class($methodNode)
            ));
        }

        if (null === $methodNode->argumentExpressionList) {
            return [];
        }

        $parameters = [];

        /** @var ArgumentExpression $expression */
        foreach ($methodNode->argumentExpressionList->children as $expression) {
            // NOTE: This is _really_ inefficient, the code is parsed, and the
            //       frames resolved for EACH parameter.
            $offset = $this->reflector->reflectOffset(
                SourceCode::fromString($sourceCode),
                Offset::fromInt($expression->getEndPosition())
            );

            $parameters[] = $offset->symbolInformation();
        }

        return $parameters;
    }

    private function addMethod(string $sourceCode, int $offset, array $parameters, string $methodName = null)
    {
        $offset = $this->reflector
            ->reflectOffset(SourceCode::fromString($sourceCode), Offset::fromInt($offset));
        $symbolInformation = $offset->symbolInformation();

        $symbol = $symbolInformation->symbol();
        $frame = $offset->frame();

        $methodName = $methodName ?: $symbol->name();

        $thisClassVar = $frame->locals()->byName(self::VAR_NAME_THIS)->first();
        $thisClass = $this->reflector->reflectClassLike($thisClassVar->symbolInformation()->type()->className());

        $builder = SourceCodeBuilder::create();
        $builder->namespace((string) $thisClass->name()->namespace());
        $classBuilder = $builder->class((string) $thisClass->name()->short());
        $methodBuilder = $classBuilder->method($methodName);
        $methodBuilder->visibility(Visibility::PRIVATE);

        /** @var SymbolInformation $parameter */
        foreach ($parameters as $parameter) {
            $type = $parameter->type();

            $parameterName = $parameter->symbol()->name();

            if ($parameterName === Symbol::UNKNOWN) {
                $parameterName = sprintf('param%s', $this->methodSuffixIndex++);
            }

            $parameterBuilder = $methodBuilder->parameter($parameterName);

            if ($type->isDefined()) {
                if ($type->isPrimitive()) {
                    $parameterBuilder->type((string) $type);
                }

                if ($type->isClass()) {
                    $parameterBuilder->type($type->className()->short());
                }
            }
        }

        $prototype = $builder->build();

        return $this->updater->apply($prototype, Code::fromString($sourceCode));
    }
}

