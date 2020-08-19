<?php

namespace Phpactor\CodeTransform\Domain;

class Diagnostics extends AbstractCollection
{
    protected function type(): string
    {
        return Diagnostic::class;
    }

    public static function empty(): self
    {
        return new self([]);
    }
}
