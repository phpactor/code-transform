<?php

namespace Phpactor\CodeTransform\Tests\Unit\Domain\Macro;

use PHPUnit\Framework\TestCase;
use Phpactor\CodeTransform\Domain\Macro\ArgumentResolver;
use Phpactor\CodeTransform\Domain\Macro\Exception\ExtraArguments;
use Phpactor\CodeTransform\Domain\Macro\MacroDefinition;
use Phpactor\CodeTransform\Domain\Macro\ParameterDefinition;
use Phpactor\CodeTransform\Domain\Macro\Exception\MissingArguments;

class ArgumentResolverTest extends TestCase
{
    /**
     * @var ArgumentResolver
     */
    private $resolver;

    public function setUp()
    {
        $this->resolver = new ArgumentResolver();
    }

    public function testThrowsExceptionIfRequiredArgumentsAreMissing()
    {
        $this->expectException(MissingArguments::class);
        $definition = new MacroDefinition('definition', [
            new ParameterDefinition('hello'),
        ]);
        $this->resolver->resolve($definition, []);
    }

    public function testThrowsExceptionIfExtraManyArgumentsGiven()
    {
        $this->expectException(ExtraArguments::class);
        $this->expectExceptionMessage('Unknown named argument(s) "barbar", valid names: "hello"');
        $definition = new MacroDefinition('definition', [
            new ParameterDefinition('hello'),
        ]);
        $this->resolver->resolve($definition, [
            'hello' => 'asd',
            'barbar' => 'goodbye',
        ]);
    }

    public function testResolvesDefaultValues()
    {
        $definition = new MacroDefinition('definition', [
            new ParameterDefinition('hello', null, 'hello'),
        ]);
        $arguments = $this->resolver->resolve($definition, []);

        $this->assertEquals(['hello'], $arguments);
    }

    public function testResolvesInCorrectOrder()
    {
        $definition = new MacroDefinition('definition', [
            new ParameterDefinition('a'),
            new ParameterDefinition('b'),
            new ParameterDefinition('c'),
        ]);
        $arguments = $this->resolver->resolve($definition, [
            'c' => 1,
            'a' => 2,
            'b' => 3,
        ]);

        $this->assertEquals([2, 3, 1], $arguments);
    }
}
