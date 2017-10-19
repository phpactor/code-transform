<?php

namespace Phpactor\CodeTransform\Domain;

final class SourceCode
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $path;

    private function __construct(string $code, string $path = null)
    {
        $this->code = $code;
        $this->path = $path;
    }

    public static function fromString(string $code)
    {
        return new self($code);
    }

    public static function fromStringAndPath(string $code, string $path = null)
    {
        return new self($code, $path);
    }

    public function __toString()
    {
        return $this->code;
    }

    public function path()
    {
        return $this->path;
    }
}

