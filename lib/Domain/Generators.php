<?php

namespace Phpactor\CodeTransform\Domain;

final class Generators extends AbstractCollection
{
    protected function type(): string
    {
        return Generator::class;
    }
}
