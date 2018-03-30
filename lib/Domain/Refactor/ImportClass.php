<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;

interface ImportClass
{
    public function importClass(SourceCode $source, int $offset, string $name, string $alias = null): SourceCode;
}
