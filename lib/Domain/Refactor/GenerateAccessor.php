<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;

interface GenerateAccessor
{
    public function generateAccessor(string $sourceCode, int $offset): SourceCode;
}
