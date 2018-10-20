<?php

namespace Phpactor\CodeTransform\Domain\Refactor\ImportClass;

use Phpactor\CodeTransform\Domain\Exception\TransformException;

class ClassAlreadyInNamespaceException extends TransformException
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        parent::__construct(sprintf(
            'Class "%s" is in the same namespace as current class',
            $name
        ));

        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }
}
