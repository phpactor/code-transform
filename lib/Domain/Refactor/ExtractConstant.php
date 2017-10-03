<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

interface ExtractConstant
{
    public function extractConstant(string $souceCode, int $offset, string $constantName);
}
