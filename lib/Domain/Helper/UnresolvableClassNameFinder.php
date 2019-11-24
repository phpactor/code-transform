<?php

namespace Phpactor\CodeTransform\Domain\Helper;

use Phpactor\CodeTransform\Domain\NameWithByteOffset;
use Phpactor\TextDocument\TextDocument;

interface UnresolvableClassNameFinder
{
    /**
     * @return NameWithByteOffset[]
     */
    public function find(TextDocument $sourceCode): array;
}
