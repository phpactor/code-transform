<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use Phpactor\CodeTransform\Domain\SourceCode;

interface OverloadMethod
{
    public function overloadMethod(SourceCode $source, string $className, string $methodName);
}
