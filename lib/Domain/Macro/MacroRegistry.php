<?php

namespace Phpactor\CodeTransform\Domain\Macro;

use Phpactor\CodeTransform\Domain\Macro\Exception\MacroNotFound;

class MacroRegistry
{
    /**
     * @var Macro[]
     */
    private $macros = [];

    public function __construct(array $macros)
    {
        foreach ($macros as $macro) {
            $this->addMacro($macro);
        }
    }

    private function addMacro(Macro $macro)
    {
        $this->macros[$macro->name()] = $macro;
    }

    public function get(string $macroName)
    {
        if (!isset($this->macros[$macroName])) {
            throw new MacroNotFound(sprintf(
                'Macro "%s" is not known, known macros: "%s"', 
                $macroName,
                implode('", "', array_keys($this->macros))
            ));
        }

        return $this->macros[$macroName];
    }
}
