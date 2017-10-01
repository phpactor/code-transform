<?php

namespace Phpactor\CodeTransform\Domain;

final class Transformers extends AbstractCollection
{
    public function applyTo(SourceCode $code)
    {
        foreach ($this as $transformer) {
            $code = $transformer->transform($code);
        }

        return $code;
    }

    public function in(array $transformerNames)
    {
        $transformers = [];

        foreach ($transformerNames as $transformerName) {
            $transformers[] = $this->get($transformerName);
        }

        return new self($transformers);
    }

    protected function type(): string
    {
        return Transformer::class;
    }
}
