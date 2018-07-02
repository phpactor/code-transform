<?php

namespace Phpactor\CodeTransform\Tests\Unit\Domain\Macro;

use DTL\ArgumentResolver\ArgumentResolver;
use PHPUnit\Framework\TestCase;
use Phpactor\CodeTransform\Domain\Macro\Macro;
use Phpactor\CodeTransform\Domain\Macro\MacroRegistry;
use Phpactor\CodeTransform\Domain\Macro\MacroRunner;
use Phpactor\CodeTransform\Domain\Macro\ParameterDefinition;
use Phpactor\CodeTransform\Domain\SourceCode;

class MacroRunnerTest extends TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $registry;

    /**
     * @var ObjectProphecy
     */
    private $factory;

    /**
     * @var MacroRunner
     */
    private $runner;

    /**
     * @var ObjectProphecy
     */
    private $argumentResolver;

    /**
     * @var Macro
     */
    private $macro;

    /**
     * @var ObjectProphecy
     */
    private $inputSourceCode;

    /**
     * @var ObjectProphecy
     */
    private $outputSourceCode;

    public function setUp()
    {
        $this->registry = $this->prophesize(MacroRegistry::class);
        $this->argumentResolver = $this->prophesize(ArgumentResolver::class);

        $this->runner = new MacroRunner(
            $this->registry->reveal(),
            $this->argumentResolver->reveal()
        );

        $this->inputSourceCode = SourceCode::fromString('input');
        $this->macro = new class implements Macro {
            public $foo;
            public $bar;
            public function name() { return 'hello'; }
            public function __invoke($foo, $bar): SourceCode {
                $this->bar = $bar;
                $this->foo = $foo;
                return SourceCode::fromString('result'); }
        };
    }

    public function testRunsMacro()
    {
        $arguments = [
            'bar' => 345,
            'foo' => 123,
        ];

        $this->registry->get('macro1')->willReturn($this->macro);
        $this->argumentResolver->resolveArguments(get_class($this->macro), '__invoke', $arguments)->willReturn(
            [123, 456]
        );

        $sourceCode = $this->runner->run('macro1', $arguments);
        $this->assertEquals(123, $this->macro->foo);
        $this->assertEquals(456, $this->macro->bar);
        $this->assertEquals('result', (string) $sourceCode);
    }
}
