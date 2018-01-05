<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection;

use Phpactor\CodeTransform\Tests\Adapter\AdapterTestCase;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StringSourceLocator;
use Phpactor\WorseReflection\Reflector;
use Phpactor\TestUtils\Workspace;

class WorseTestCase extends AdapterTestCase
{
    public function reflectorFor(string $source)
    {
        return Reflector::create(new StringSourceLocator(SourceCode::fromString((string) $source)));
    }
}
