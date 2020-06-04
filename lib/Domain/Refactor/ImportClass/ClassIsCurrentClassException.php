<?php

namespace Phpactor\CodeTransform\Domain\Refactor\ImportClass;

use Phpactor\CodeTransform\Domain\Exception\TransformException;

class ClassIsCurrentClassException extends TransformException
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $type, string $name)
    {
        parent::__construct(sprintf(
            '%s "%s" is the current class',
            ucfirst($type),
            $name
        ));

        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }
}
