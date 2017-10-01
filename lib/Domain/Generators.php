<?php

namespace Phpactor\CodeTransform\Domain;

use Phpactor\CodeTransform\Domain\Generator;

final class Generators extends AbstractCollection
{
    protected function type(): string
    {
        return Generator::class;
    }
}
