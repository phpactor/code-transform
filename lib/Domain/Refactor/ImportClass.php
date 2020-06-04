<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\TextDocument\TextEdits;

interface ImportClass
{
    public function importClass(SourceCode $source, int $offset, string $name, string $alias = null): TextEdits;

    public function importFunction(SourceCode $source, int $offset, string $name, string $alias = null): TextEdits;
}
