<?php

namespace Phpactor\CodeTransform\Tests\Adapter;

use Phpactor\CodeBuilder\Adapter\Twig\TwigRenderer;
use Phpactor\CodeBuilder\Adapter\TolerantParser\TolerantUpdater;
use PHPUnit\Framework\TestCase;
use Phpactor\TestUtils\Workspace;
use Phpactor\TestUtils\ExtractOffset;

class AdapterTestCase extends TestCase
{
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

        if (!file_exists($manifestPath)) {
            touch($manifestPath);
        }

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

    protected function sourceExpectedAndWordUnderCursor($manifestPath): array
    {
        list($source, $expected) = $this->sourceExpected($manifestPath);

        preg_match('/(\w+)<>(\w+)/', $source, $matches);
        $word = $matches[1] . $matches[2];

        $source = preg_replace('/<>/', '', $source);

        return [ $source, $expected, $word ];
    }
}
