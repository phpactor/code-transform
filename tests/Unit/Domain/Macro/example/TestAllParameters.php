<?php

namespace Phpactor\CodeTransform\Tests\Unit\Domain\Macro\example;

use Phpactor\CodeTransform\Domain\SourceCode;

class TestAllParameters
{
    public function __invoke(SourceCode $code, $noType, int $intType, string $withDefault = 'default')
    {
    }
}
