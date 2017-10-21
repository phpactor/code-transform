<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;

interface GenerateMethod
{
    public function generateMethod(SourceCode $sourceCode, int $offset): SourceCode;
}
