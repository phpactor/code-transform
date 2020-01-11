<?php

namespace Phpactor\CodeTransform\Domain;

use ArrayIterator;
use Iterator;
use IteratorAggregate;

class NameWithByteOffsets implements IteratorAggregate
{
    private $nameWithByteOffsets;

    public function __construct(NameWithByteOffset ...$nameWithByteOffsets)
    {
        $this->nameWithByteOffsets = $nameWithByteOffsets;
    }

    private function add($element): void
    {
        $this->nameWithByteOffsets[] = $element;
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->nameWithByteOffsets);
    }
}
