<?php

namespace Phpactor\CodeTransform\Domain;

interface Transformer
{
    public function transform(SourceCode $code): SourceCode;

    /**
     * Return the issues that this transform will fix.
     */
    public function diagnostics(SourceCode $code): Diagnostics;
}
