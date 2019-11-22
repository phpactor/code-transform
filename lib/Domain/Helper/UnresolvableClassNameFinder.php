<?php

namespace Phpactor\CodeTransform\Domain\Helper;

use Phpactor\Name\Names;
use Phpactor\TextDocument\TextDocument;

interface UnresolvableClassNameFinder
{
    public function find(TextDocument $sourceCode): Names;
}
