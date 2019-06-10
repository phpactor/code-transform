<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;

interface GenerateAccessor
{
    public function generateFromOffset(SourceCode $sourceCode, int $offset): SourceCode;
    public function generateFromPropertyName(SourceCode $sourceCode, string $propertyName): SourceCode;
}
