<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use InvalidArgumentException;
use Phpactor\CodeTransform\Domain\SourceCode;
use RuntimeException;

interface GenerateAccessor
{
    public function generate(SourceCode $sourceCode, string $propertyName, int $offset): SourceCode;
}
