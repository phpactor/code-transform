<?php

namespace Phpactor\CodeTransform\Domain;

use Phpactor\CodeTransform\Domain\AbstractCollection;

class ClassNames extends AbstractCollection
{
    public function type(): string
    {
        return ClassName::class;
    }
}
