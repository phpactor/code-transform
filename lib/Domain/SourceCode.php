<?php

namespace Phpactor\CodeTransform\Domain;

use Webmozart\PathUtil\Path;

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

        if ($path && false === Path::isAbsolute($path)) {
            throw new \RuntimeException(sprintf(
                'Path "%s" must be absolute',
                $path
            ));
        }

        $this->path = $path ? Path::canonicalize($path) : null;
    }

    public static function fromString(string $code): SourceCode
    {
        return new self($code);
    }

    public static function fromStringAndPath(string $code, string $path = null): SourceCode
    {
        return new self($code, $path);
    }

    public function __toString()
    {
        return $this->code;
    }

    public function withSource(string $code): SourceCode
    {
        return new self($code, $this->path);
    }

    public function path()
    {
        return $this->path;
    }
}
