<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;

interface ExtractMethod
{
    public function extractMethod(SourceCode $source, int $offsetStart, int $offsetEnd, string $name): SourceCode;
}
