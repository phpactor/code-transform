<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Refactor;

use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\Offset;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\Node\Expression\MemberAccessExpression;
use Microsoft\PhpParser\Node\Expression\CallExpression;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\CodeBuilder\Domain\Code;
use Phpactor\CodeBuilder\Domain\Prototype\Visibility;

class WorseGenerateMethod
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

    public function __construct(Reflector $reflector, Updater $updater, Parser $parser = null)
    {
        $this->reflector = $reflector;
        $this->updater = $updater;
        $this->parser = $parser ?: new Parser();
    }

    public function generateMethod(string $sourceCode, int $offset, $methodName = null)
    {
        $varNames = $this->varNames($sourceCode, $offset);
        $sourceCode = $this->addMethod($sourceCode, $offset, $varNames);

        return $sourceCode;
    }

    private function varNames(string $sourceCode, int $offset)
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

        return [];
    }

    private function addMethod(string $sourceCode, int $offset, array $varNames, string $methodName = null)
    {
        $offset = $this->reflector
            ->reflectOffset(SourceCode::fromString($sourceCode), Offset::fromInt($offset));
        $symbolInformation = $offset->symbolInformation();

        $symbol = $symbolInformation->symbol();
        $frame = $offset->frame();

        $methodName = $methodName ?: $symbol->name();

        foreach ($varNames as $varName) {
            // do some shit
        }

        $thisClassVar = $frame->locals()->byName(self::VAR_NAME_THIS)->first();
        $thisClass = $this->reflector->reflectClassLike($thisClassVar->symbolInformation()->type()->className());

        $builder = SourceCodeBuilder::create();
        $builder->namespace((string) $thisClass->name()->namespace());
        $classBuilder = $builder->class((string) $thisClass->name()->short());
        $methodBuilder = $classBuilder->method($methodName);
        $methodBuilder->visibility(Visibility::PRIVATE);

        $prototype = $builder->build();

        return $this->updater->apply($prototype, Code::fromString($sourceCode));
    }
}

