<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\TextDocument\TextEdits;

interface GenerateMethod
{
    public function generateMethod(SourceCode $sourceCode, int $offset): TextEdits;
}
