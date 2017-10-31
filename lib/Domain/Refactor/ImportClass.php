<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;

interface ImportClass
{
    public function importClass(SourceCode $source, int $offset, string $alias = null);
}
