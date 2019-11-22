<?php

namespace Phpactor\CodeTransform\Domain\Helper;

use Phpactor\Name\Names;
use Phpactor\TextDocument\TextDocument;

interface UnresolvableClassNameFinder
{
    /**
     * @return NameWithByteOffset[]
     */
    public function find(TextDocument $sourceCode): array;
}
