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
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\Collection\ReflectionArgumentCollection;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\AbstractReflectionMethodCall;
use Phpactor\WorseReflection\Core\Reflection\ReflectionArgument;

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

    /** @var int
     */
    private $methodSuffixIndex = 0;

    public function __construct(Reflector $reflector, Updater $updater)
    {
        $this->reflector = $reflector;
        $this->updater = $updater;
    }

    public function generateMethod(string $sourceCode, int $offset, $methodName = null)
    {
        $methodCall = $this->reflector->reflectMethodCall($sourceCode, $offset);
        $prototype = $this->generatePrototype($methodCall, $methodName);

        return $this->updater->apply($prototype, Code::fromString($sourceCode));
    }

    private function generatePrototype(AbstractReflectionMethodCall $methodCall, string $methodName = null)
    {
        $methodName = $methodName ?: $methodCall->name();

        $builder = SourceCodeBuilder::create();
        $builder->namespace((string) $methodCall->class()->name()->namespace());

        $classBuilder = $builder->class((string) $methodCall->class()->name()->short());
        $methodBuilder = $classBuilder->method($methodName);
        $methodBuilder->visibility(Visibility::PRIVATE);

        /** @var ReflectionArgument $argument */
        foreach ($methodCall->arguments() as $argument) {
            $type = $argument->type();

            $argumentBuilder = $methodBuilder->parameter($argument->guessName());

            if ($type->isDefined()) {
                if ($type->isPrimitive()) {
                    $argumentBuilder->type((string) $type);
                }

                if ($type->isClass()) {
                    $argumentBuilder->type($type->className()->short());
                }
            }
        }

        return $builder->build();
    }
}

