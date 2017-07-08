<?php

namespace Phpactor\CodeTransform\Domain;

final class SourceCode
{
    /**
     * @var string
     */
    private $code;

    private function __construct(string $code)
    {
        $this->code = $code;
    }

    public static function fromString(string $code)
    {
        return new self($code);
    }

    public function __toString()
    {
        return $this->code;
    }
}

