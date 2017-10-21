<?php

namespace Phpactor\CodeTransform\Tests\Adapter;

use Phpactor\CodeBuilder\Adapter\Twig\TwigRenderer;
use Phpactor\CodeBuilder\Adapter\TolerantParser\TolerantUpdater;
use PHPUnit\Framework\TestCase;

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

    protected function splitInitialAndExpectedSource($sourceFile)
    {
        $files = [
            0 => [],
            1 => [],
        ];
        $index = 0;
        $contents = explode(PHP_EOL, file_get_contents($sourceFile));
        foreach ($contents as $line) {
            if ($line === '========') {
                $index++;
                continue;
            }

            $files[$index][] = $line;
        }

        return [
            implode(PHP_EOL, $files[0]),
            implode(PHP_EOL, $files[1]),
        ];
    }
}
