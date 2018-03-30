<?php

namespace Phpactor\CodeTransform\Domain\Refactor\ImportClass;

use Phpactor\CodeTransform\Domain\Exception\TransformException;

class AliasAlreadyUsedException extends TransformException
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        parent::__construct(sprintf(
            'Alias "%s" is already used', $name
        ));

        $this->name = $name;
    }

    public function name()
    {
        return $this->name;
    }
}
