<?php

namespace Phpactor\CodeTransform\Domain;

/**
 * @extends AbstractCollection<Diagnostic>
 */
class Diagnostics extends AbstractCollection
{
    protected function type(): string
    {
        return Diagnostic::class;
    }

    public static function none(): self
    {
        return new self([]);
    }
}
