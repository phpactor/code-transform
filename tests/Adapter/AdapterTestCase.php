<?php

namespace Phpactor\CodeTransform\Tests\Adapter;

use Phpactor\CodeBuilder\Adapter\TolerantParser\StyleProposer\DocblockIndentationProposer;
use Phpactor\CodeBuilder\Adapter\TolerantParser\StyleProposer\IndentationProposer;
use Phpactor\CodeBuilder\Adapter\TolerantParser\StyleProposer\MemberBlankLineProposer;
use Phpactor\CodeBuilder\Adapter\TolerantParser\TolerantStyleFixer;
use Phpactor\CodeBuilder\Adapter\Twig\TwigRenderer;
use Phpactor\CodeBuilder\Adapter\TolerantParser\TolerantUpdater;
use PHPUnit\Framework\TestCase;
use Phpactor\CodeBuilder\Util\TextFormat;
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
        $textFormat = new TextFormat();
        return new TolerantUpdater(
            $this->renderer(),
            null,
            null,
            new TolerantStyleFixer([
                new MemberBlankLineProposer($textFormat),
                new IndentationProposer($textFormat),
                new DocblockIndentationProposer($textFormat),
            ])
        );
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
}
