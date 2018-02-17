<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection;

use Phpactor\CodeTransform\Tests\Adapter\AdapterTestCase;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StringSourceLocator;
use Phpactor\WorseReflection\Reflector;
use Phpactor\TestUtils\Workspace;
use Phpactor\CodeBuilder\Domain\BuilderFactory;
use Phpactor\CodeBuilder\Adapter\WorseReflection\WorseBuilderFactory;

class WorseTestCase extends AdapterTestCase
{
    public function reflectorFor(string $source)
    {
        return Reflector::create(new StringSourceLocator(SourceCode::fromString((string) $source)));
    }

    public function builderFactory(Reflector $reflector): BuilderFactory
    {
        return new WorseBuilderFactory($reflector);
    }
}
