<?php

namespace Phpactor\CodeTransform\Tests\Adapter;

use Phpactor\CodeBuilder\Adapter\Twig\TwigRenderer;
use Phpactor\CodeBuilder\Adapter\TolerantParser\TolerantUpdater;
use PHPUnit\Framework\TestCase;
use Phpactor\CodeTransform\Domain\Macro\Macro;
use Phpactor\CodeTransform\Domain\Macro\MacroRegistry;
use Phpactor\CodeTransform\Domain\Macro\MacroRunner;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\TestUtils\Workspace;
use Phpactor\TestUtils\ExtractOffset;

class AdapterTestCase extends TestCase
{
    protected function executeMacro(Macro $macro, array $arguments): SourceCode
    {
        $macroRegistry = new MacroRegistry([$macro]);
        $macroRunner = new MacroRunner($macroRegistry);
        return $macroRunner->run($macro->name(), $arguments);
    }
    protected function renderer()
    {
        return new TwigRenderer();
    }

    protected function updater()
    {
        return new TolerantUpdater($this->renderer());
    }

    protected function workspace(): Workspace
    {
        return Workspace::create(__DIR__ . '/../Workspace');
    }

    protected function sourceExpected($manifestPath)
    {
        $workspace = $this->workspace();
        $workspace->reset();
        $workspace->loadManifest(file_get_contents($manifestPath));
        $source = $workspace->getContents('source');
        $expected = $workspace->getContents('expected');

        return [ $source, $expected ];
    }

    protected function sourceExpectedAndOffset($manifestPath)
    {
        list($source, $expected) = $this->sourceExpected($manifestPath);
        list($source, $offsetStart, $offsetEnd) = ExtractOffset::fromSource($source);

        return [ $source, $expected, $offsetStart, $offsetEnd ];
    }
}
