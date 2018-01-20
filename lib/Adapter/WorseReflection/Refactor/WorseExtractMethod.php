<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\WorseReflection\Reflector;
use Microsoft\PhpParser\Parser;
use Phpactor\CodeBuilder\Domain\BuilderFactory;
use Phpactor\CodeBuilder\Domain\Code;

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
        $offset = $this->reflector->reflectOffset((string) $source, $offsetEnd);
        $thisVariable = $offset->frame()->locals()->byName('this');

        $builder = $this->factory->fromSource($source);
        $methodBuilder = $builder->class((string) $thisVariable->last()->symbolInformation()->type()->className())->method($name);
        $methodBuilder->body()->line($selection);
        $methodBuilder->visibility('private');

        $prototype = $builder->build();

        $source = $source->replaceSelection('$this->'  . $name . '();', $offsetStart, $offsetEnd);

        return $source->withSource(
            (string) $this->updater->apply($prototype, Code::fromString((string) $source))
        );
    }
}
