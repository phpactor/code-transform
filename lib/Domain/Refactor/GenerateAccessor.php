<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;

interface GenerateAccessor
{
    public function __invoke(SourceCode $sourceCode, int $offset): SourceCode;
}
