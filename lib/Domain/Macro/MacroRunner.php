<?php

namespace Phpactor\CodeTransform\Domain\Macro;

use Phpactor\CodeTransform\Domain\SourceCode;

class MacroRunner
{
    /**
     * @var MacroRegistry
     */
    private $macroRegistry;

    /**
     * @var MacroDefinitionFactory
     */
    private $definitionFactory;

    /**
     * @var ArgumentResolver
     */
    private $argumentResolver;

    public function __construct(
        MacroRegistry $macroRegistry,
        MacroDefinitionFactory $definitionFactory = null,
        ArgumentResolver $argumentResolver = null
    )
    {
        $this->macroRegistry = $macroRegistry;
        $this->definitionFactory = $definitionFactory ?: new MacroDefinitionFactory();
        $this->argumentResolver = $argumentResolver ?: new ArgumentResolver();
    }

    public function run(string $macroName, array $arguments): SourceCode
    {
        $macro = $this->macroRegistry->get($macroName);

        $definition = $this->definitionFactory->definitionFor(get_class($macro));
        $arguments = $this->argumentResolver->resolve($definition, $arguments);

        return $macro->__invoke(...$arguments);
    }
}
