<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

interface GenerateMethod
{
    public function generateMethod(string $sourceCode, int $offset);
}
