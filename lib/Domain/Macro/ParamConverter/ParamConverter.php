<?php

namespace Phpactor\CodeTransform\Domain\Macro\ParamConverter;

use Phpactor\CodeTransform\Domain\Macro\ParameterDefinition;
use Phpactor\CodeTransform\Domain\SourceCode;

interface ParamConverter
{
    public function canConvert(ParameterDefinition $parameterDefinition)
    {
        return $parameterDefinition->type() == SourceCode::class;
    }
}
