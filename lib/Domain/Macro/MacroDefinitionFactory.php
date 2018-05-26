<?php

namespace Phpactor\CodeTransform\Domain\Macro;

use ReflectionClass;
use RuntimeException;

class MacroDefinitionFactory
{
    public function definitionFor(string $className): MacroDefinition
    {
        if (!class_exists($className)) {
            throw new RuntimeException(sprintf(
                'Macro class "%s" does not exist'
            , $className));
        }

        $reflectionClass = new ReflectionClass($className);

        if (false === $reflectionClass->hasMethod('__invoke')) {
            throw new RuntimeException(sprintf(
                'Macros must implement the __invoke method'
            ));
        }


        $parameterDefinitions = [];
        foreach ($reflectionClass->getMethod('__invoke')->getParameters() as $parameter) {
            $parameterDefinitions[] = new ParameterDefinition(
                $parameter->getName(),
                $parameter->getType() ? $parameter->getType()->getName() : null,
                $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null
            );
        }

        return new MacroDefinition($reflectionClass->getShortName(), $parameterDefinitions);
    }
}
