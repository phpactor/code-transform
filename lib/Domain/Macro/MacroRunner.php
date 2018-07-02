<?php

namespace Phpactor\CodeTransform\Domain\Macro;

use DTL\ArgumentResolver\ArgumentResolver;
use Phpactor\CodeTransform\Domain\SourceCode;

class MacroRunner
{
    /**
     * @var MacroRegistry
     */
    private $macroRegistry;

    /**
     * @var ArgumentResolver
     */
    private $argumentResolver;

    public function __construct(
        MacroRegistry $macroRegistry,
        ArgumentResolver $argumentResolver = null
    )
    {
        $this->macroRegistry = $macroRegistry;
        $this->argumentResolver = $argumentResolver ?: new ArgumentResolver();
    }

    public function run(string $macroName, array $arguments): SourceCode
    {
        $macro = $this->macroRegistry->get($macroName);

        $arguments = $this->argumentResolver->resolveArguments(get_class($macro), '__invoke', $arguments);

        return $macro->__invoke(...$arguments);
    }
}
