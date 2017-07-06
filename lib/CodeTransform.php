<?php

namespace Phpactor\CodeTransform\Domain;

class ClassTransformer
{
    private $transformers;

    public function __construct(Transformers $transformers, Generators $generators)
    {
        $this->transformers = $transformers;
    }

    public function transform(SourceCode $code, array $transformationNames): SourceCode
    {
        $transformers = $this->transformers->in($transformations);
        return $transformers->applyTo($code);
    }
}
