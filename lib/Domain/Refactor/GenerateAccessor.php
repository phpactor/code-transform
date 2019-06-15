<?php

namespace Phpactor\CodeTransform\Domain\Refactor;

use InvalidArgumentException;
use Phpactor\CodeTransform\Domain\SourceCode;
use RuntimeException;

interface GenerateAccessor
{
    /**
     * Generates an accessor.
     *
     * @param SourceCode $sourceCode The source code of the working file.
     * @param string $propertyName The name of the property to generate the accessor for.
     * @param int $offset The position of the cursor, used to identified the class if there
     * is more than one in the `$sourceCode`.
     *
     * @return SourceCode
     *
     * @throws InvalidArgumentException If there is no class in the code.
     * @throws RuntimeException If it's impossible to determine which class to use.
     */
    public function generate(SourceCode $sourceCode, string $propertyName, int $offset): SourceCode;
}
