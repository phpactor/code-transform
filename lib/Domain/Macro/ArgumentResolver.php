<?php

namespace Phpactor\CodeTransform\Domain\Macro;

use Phpactor\CodeTransform\Domain\Macro\Exception\ExtraArguments;
use Phpactor\CodeTransform\Domain\Macro\Exception\MissingArguments;

class ArgumentResolver
{
    public function resolve(MacroDefinition $definition, array $arguments = [])
    {
        $required = $this->requiredParametersFor($definition);

        if ($diff = array_diff($required, array_keys($arguments))) {
            throw new MissingArguments(sprintf(
                'Required arguments "%s" are missing',
                implode('", "', $diff)
            ));
        }

        $allNames = $this->allNames($definition);

        if ($diff = array_diff(array_keys($arguments), $allNames)) {
            throw new ExtraArguments(sprintf(
                'Unknown named argument(s) "%s", valid names: "%s"',
                implode('", "', $diff), implode('", "', $allNames)
            ));
        }

        $resolved = [];
        foreach ($definition->parameterDefinitions() as $parameterDefinition) {
            if (isset($arguments[$parameterDefinition->name()])) {
                $resolved[] = $arguments[$parameterDefinition->name()];
                continue;
            }

            $resolved[] = $parameterDefinition->default();
        }

        return $resolved;
    }

    private function requiredParametersFor(MacroDefinition $definition): array
    {
        $required = [];
        foreach ($definition->parameterDefinitions() as $parameterDefinition) {
            if ($parameterDefinition->default() === null) {
                $required[] = $parameterDefinition->name();
            }
        }

        return $required;
    }

    private function allNames(MacroDefinition $definition): array
    {
        $all = [];
        foreach ($definition->parameterDefinitions() as $parameterDefinition) {
            $all[] = $parameterDefinition->name();
        }

        return $all;
    }
}
