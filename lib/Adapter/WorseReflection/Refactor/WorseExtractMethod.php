<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Refactor;

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

class WorseExtractMethod
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

    public function __construct(Reflector $reflector, BuilderFactory $factory, Updater $updater)
    {
        $this->reflector = $reflector;
        $this->updater = $updater;
        $this->parser = new Parser();
        $this->factory = $factory;
    }

    public function extractMethod(SourceCode $source, int $offsetStart, int $offsetEnd, string $name): SourceCode
    {
        $selection = $source->extractSelection($offsetStart, $offsetEnd);
        $builder = $this->factory->fromSource($source);

        $offset = $this->reflector->reflectOffset((string) $source, $offsetEnd);
        $thisVariable = $offset->frame()->locals()->byName('this');
        $methodBuilder = $builder->class((string) $thisVariable->last()->symbolInformation()->type()->className())->method($name);
        $methodBuilder->body()->line($this->removeIndentation($selection));
        $methodBuilder->visibility('private');

        $locals = $this->reflector->reflectOffset((string) $source->replaceSelection('', $offsetStart, $offsetEnd), $offsetStart)->frame()->locals();
        $freeVariables = $this->freeVariablesFrom($locals, $selection);
        $args = [];
        /** @var Variable $freeVariable */
        foreach ($freeVariables as $freeVariable) {
            $parameterBuilder = $methodBuilder->parameter($freeVariable->name());

            $variableType = $freeVariable->symbolInformation()->type();
            if ($variableType->isDefined()) {
                $parameterBuilder->type($variableType->short());
            }

            $args[] = '$' . $freeVariable->name();
        }

        $prototype = $builder->build();
        $source = $source->replaceSelection('$this->'  . $name . '(' . implode(', ', $args) . ');', $offsetStart, $offsetEnd);

        return $source->withSource(
            (string) $this->updater->apply($prototype, Code::fromString((string) $source))
        );
    }

    private function freeVariablesFrom(Assignments $locals, string $selection)
    {
        $code = '<?php ' . $selection;
        $node = $this->parser->parseSourceFile($code);
        $variables = [];

        /** @var Token $token */
        foreach ($node->getDescendantTokens() as $token) {
            if ($token->kind == TokenKind::VariableName) {
                $variables[] = substr($token->getText($code), 1);
            }
        }

        $frees = [];
        foreach ($variables as $variable) {
            if ($variables = $locals->byName($variable)) {
                if ($variables->count()) {
                    $frees[] = $variables->last();
                }
            }
        }

        return $frees;
    }

    private function removeIndentation(string $selection)
    {
        $indentation = null;
        $lines = explode(PHP_EOL, $selection);

        if (empty($lines)) {
            return $selection;
        }

        foreach ($lines as $line) {
            preg_match('{^\s+$}', $line, $matches);

            if (false === isset($matches[0])) {
                continue;
            }

            $count = mb_strlen($matches[0]);
            if (null === $indentation || $count < $indentation) {
                $indentation = $count;
            }
        }

        foreach ($lines as &$line) {
            $line = substr($line, $indentation);
        }

        return trim(implode(PHP_EOL, $lines), PHP_EOL);
    }
}
