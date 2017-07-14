<?php

namespace Phpactor\CodeTransform\Adapter\Native\GenerateNew;

use Phpactor\CodeTransform\Domain\GenerateNew;
use Phpactor\CodeTransform\Domain\ClassName;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeBuilder\Domain\Builder\SourceCodeBuilder;
use Phpactor\CodeBuilder\Domain\Renderer;

class ClassGenerator implements GenerateNew
{
    /**
     * @var Renderer
     */
    private $renderer;

    public function __construct(Renderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * {@inheritDoc}
     */
    public function generateNew(ClassName $targetName): SourceCode
    {
        $builder = SourceCodeBuilder::create();
        $builder->namespace($targetName->namespace());
        $classPrototype = $builder->class($targetName->short())->build();

        return SourceCode::fromString(
            (string) $this->renderer->render($classPrototype)
        );
    }
}


