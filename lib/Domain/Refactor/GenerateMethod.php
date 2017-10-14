<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;

interface GenerateMethod
{
    public function generateMethod(string $sourceCode, int $offset): SourceCode;
}
