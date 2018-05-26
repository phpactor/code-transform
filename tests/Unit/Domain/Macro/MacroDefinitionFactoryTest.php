<?php

namespace Phpactor\CodeTransform\Tests\Unit\Domain\Macro;

use PHPUnit\Framework\TestCase;
use Phpactor\CodeTransform\Domain\Macro\MacroDefinitionFactory;
use Phpactor\CodeTransform\Domain\Macro\ParameterDefinition;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Tests\Unit\Domain\Macro\example\TestAllParameters;
use Phpactor\CodeTransform\Tests\Unit\Domain\Macro\example\TestNoParameters;
use RuntimeException;

class MacroDefinitionFactoryTest extends TestCase
{
    /**
     * @var MacroDefinitionFactory
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new MacroDefinitionFactory();
    }

    public function testThrowsExceptionIfClassDoesNotExist()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');
        $this->factory->definitionFor('NotExistingMe');
    }

    public function testThrowsExceptionIfNoInvoke()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must implement the __invoke');

        $this->factory->definitionFor('stdClass');
    }

    public function testProvidesDefinitionWithNoParameters()
    {
        $definition = $this->factory->definitionFor(TestNoParameters::class);
        $this->assertCount(0, $definition->parameterDefinitions());
    }

    public function testProvidesDefinitionWithParameters()
    {
        $definition = $this->factory->definitionFor(TestAllParameters::class);
        $parameters = $definition->parameterDefinitions();
        $this->assertCount(4, $parameters);

        $this->assertParameter($parameters[0], 'code', SourceCode::class);
        $this->assertParameter($parameters[1], 'noType');
        $this->assertParameter($parameters[2], 'intType', 'int');
        $this->assertParameter($parameters[3], 'withDefault', 'string', 'default');
    }

    private function assertParameter(
        ParameterDefinition $parameter,
        string $name,
        string $type = null,
        string $default = null
    )
    {
        $this->assertEquals($parameter->name(), $name);
        $this->assertEquals($parameter->type(), $type);
        $this->assertEquals($parameter->default(), $default);
    }
}
