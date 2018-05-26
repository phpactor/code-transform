<?php

namespace Phpactor\CodeTransform\Domain\Macro;

class MacroDefinition
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ParameterDefinition[]
     */
    private $parameterDefinitions;

    public function __construct(string $name, array $parameterDefinitions)
    {
        $this->name = $name;
        $this->parameterDefinitions = $parameterDefinitions;
    }

    /**
     * @return ParameterDefinition[]
     */
    public function parameterDefinitions(): array
    {
        return $this->parameterDefinitions;
    }

    public function name(): string
    {
        return $this->name;
    }
}
