<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;

interface ExtractMethod
{
    public function __invoke(SourceCode $source, int $offsetStart, int $offsetEnd, string $name): SourceCode;
}
