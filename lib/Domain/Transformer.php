<?php

namespace Phpactor\CodeTransform\Domain;

interface Transformer
{
    public function transform(SourceCode $code): SourceCode;
}
