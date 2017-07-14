<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection;

use Phpactor\CodeTransform\Tests\Adapter\AdapterTestCase;
use Phpactor\WorseReflection\SourceCode as WorseSourceCode;
use Phpactor\WorseReflection\SourceCodeLocator\StringSourceLocator;
use Phpactor\WorseReflection\Reflector;

class WorseTestCase extends AdapterTestCase
{
    public function reflectorFor(string $source)
    {
        return new Reflector(new StringSourceLocator(WorseSourceCode::fromString((string) $source)));
    }
}
