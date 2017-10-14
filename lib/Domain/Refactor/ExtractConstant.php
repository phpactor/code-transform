<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;

interface ExtractConstant
{
    public function extractConstant(string $souceCode, int $offset, string $constantName): SourceCode;
}
