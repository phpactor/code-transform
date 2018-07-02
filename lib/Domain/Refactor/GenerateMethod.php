<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;

interface GenerateMethod
{
    public function __invoke(SourceCode $sourceCode, int $offset): SourceCode;
}
