<?php

namespace Phpactor\CodeTransform;

use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Domain\Transformers;

class CodeTransform
{
    /**
     * @var Transformers
     */
    private $transformers;

    private function __construct(Transformers $transformers)
    {
        $this->transformers = $transformers;
    }

    public static function fromTransformers(Transformers $transformers): CodeTransform
    {
        return new self($transformers);
    }

    public function transformers(): Transformers
    {
        return $this->transformers;
    }

    public function transform(string $code, array $transformations): SourceCode
    {
        $transformers = $this->transformers->in($transformations);

        return $transformers->applyTo(SourceCode::fromString($code));
    }
}
