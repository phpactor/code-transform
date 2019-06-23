<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Helper;

use Microsoft\PhpParser\Parser;
use Phpactor\CodeTransform\Domain\Helper\InterestingOffsetFinder;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocument;
use Phpactor\WorseReflection\Core\Inference\Symbol;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;

class WorseInterestingOffsetFinder implements InterestingOffsetFinder
{
    /**
     * @var SourceCodeReflector
     */
    private $reflector;

    /**
     * @var Parser
     */
    private $parser;

    public function __construct(SourceCodeReflector $reflector, Parser $parser = null)
    {
        $this->reflector = $reflector;
        $this->parser = $parser ?: new Parser();
    }

    public function find(TextDocument $source, ByteOffset $offset): ByteOffset
    {
        $node = $this->parser->parseSourceFile($source->__toString())->getDescendantNodeAtPosition($offset->toInt());

        do {
            $reflectionOffset = $this->reflector->reflectOffset($source, $node->getStart());

            $symbolType = $reflectionOffset->symbolContext()->symbol()->symbolType();

            if ($symbolType != Symbol::UNKNOWN) {
                return ByteOffset::fromInt($reflectionOffset->symbolContext()->symbol()->position()->start());
            }

            $node = $node->parent;
        } while ($node);

        return $offset;
    }
}
