<?php

namespace Phpactor\CodeTransform\Domain;

use Phpactor\Name\Name;
use Phpactor\TextDocument\ByteOffset;

final class NameWithByteOffset
{
    /**
     * @var Name
     */
    private $name;
    /**
     * @var ByteOffset
     */
    private $byteOffset;

    public function __construct(Name $name, ByteOffset $byteOffset)
    {
        $this->name = $name;
        $this->byteOffset = $byteOffset;
    }

    public function byteOffset(): ByteOffset
    {
        return $this->byteOffset;
    }

    public function name(): Name
    {
        return $this->name;
    }
}
