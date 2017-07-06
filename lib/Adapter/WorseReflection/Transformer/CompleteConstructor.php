<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection\Transformer;

use Phpactor\CodeTransform\Domain\Transformer;
use Phpactor\CodeTransform\Domain\SourceCode;

class CompleteConstructor implements Transformer
{
    public function transform(SourceCode $code): SourceCode
        /**
        * @dataProvider provideCompleteConstructor
         */
    {
        return $code;
    }
}
