<?php

namespace Phpactor\CodeTransform\Domain;

final class SourceCode
{
    private $code;

    private function __construct(string $code)
    {
        $this->code = $code;
    }
}
