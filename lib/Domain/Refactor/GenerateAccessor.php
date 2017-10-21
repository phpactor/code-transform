<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;

interface GenerateAccessor
{
    public function generateAccessor(SourceCode $sourceCode, int $offset): SourceCode;
}
