<?php

namespace Phpactor\CodeTransform\Tests\Unit\Domain\Macro;

use PHPUnit\Framework\TestCase;
use Phpactor\CodeTransform\Domain\Macro\Exception\MacroNotFound;
use Phpactor\CodeTransform\Domain\Macro\Macro;
use Phpactor\CodeTransform\Domain\Macro\MacroRegistry;

class MacroRegistryTest extends TestCase
{
    private $macro;

    public function setUp()
    {
        $this->macro = $this->prophesize(Macro::class);
    }

    public function testThrowsExceptionIfNoMacros()
    {
        $this->expectException(MacroNotFound::class);
        $this->macro->name()->willReturn('barbar');
        $registry = new MacroRegistry([]);
        $registry->get('foobar');
    }

    public function testThrowsExceptionIfMacroNotExist()
    {
        $this->expectException(MacroNotFound::class);
        $this->macro->name()->willReturn('barbar');
        $registry = new MacroRegistry([
            $this->macro->reveal(),
        ]);
        $registry->get('foobar');
    }

    public function testReturnsMacro()
    {
        $this->macro->name()->willReturn('barbar');
        $registry = new MacroRegistry([
            $this->macro->reveal(),
        ]);
        $macro = $registry->get('barbar');
        $this->assertSame($this->macro->reveal(), $macro);
    }
}
