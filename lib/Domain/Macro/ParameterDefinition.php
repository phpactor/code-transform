<?php

namespace Phpactor\CodeTransform\Domain\Macro;

class ParameterDefinition
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $default;

    public function __construct(string $name, string $type = null, string $default = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->default = $default;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type():? string
    {
        return $this->type;
    }

    public function default()
    {
        return $this->default;
    }
}
