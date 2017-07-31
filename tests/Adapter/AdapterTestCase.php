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
}
