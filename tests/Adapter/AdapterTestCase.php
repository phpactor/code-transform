<?php

namespace Phpactor\CodeTransform\Tests\Adapter;

use PHPUnit\Framework\TestCase;
use Phpactor\CodeBuilder\Adapter\Twig\TwigRenderer;
use Phpactor\CodeBuilder\Adapter\TolerantParser\TolerantUpdater;

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
}
