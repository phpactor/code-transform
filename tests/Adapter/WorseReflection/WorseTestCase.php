<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection;

use Phpactor\CodeTransform\Tests\Adapter\AdapterTestCase;
use Phpactor\WorseReflection\Core\Logger;
use Phpactor\WorseReflection\Core\Logger\ArrayLogger;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StringSourceLocator;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StubSourceLocator;
use Phpactor\WorseReflection\Core\SourceCodeLocator\TemporarySourceLocator;
use Phpactor\WorseReflection\Reflector;
use Phpactor\TestUtils\Workspace;
use Phpactor\CodeBuilder\Domain\BuilderFactory;
use Phpactor\CodeBuilder\Adapter\WorseReflection\WorseBuilderFactory;
use Phpactor\WorseReflection\ReflectorBuilder;
use Psr\Log\AbstractLogger;

class WorseTestCase extends AdapterTestCase
{
    public function reflectorFor(string $source)
    {
        $builder = ReflectorBuilder::create();

        foreach (glob($this->workspace()->path('/*.php')) as $file) {
            $locator = new TemporarySourceLocator(ReflectorBuilder::create()->build());
            $locator->setSourceCode(SourceCode::fromString(file_get_contents($file)));
            $builder->addLocator($locator);
        }
        $builder->addSource($source);


        return $builder->build();
    }

    public function builderFactory(Reflector $reflector): BuilderFactory
    {
        return new WorseBuilderFactory($reflector);
    }
}
