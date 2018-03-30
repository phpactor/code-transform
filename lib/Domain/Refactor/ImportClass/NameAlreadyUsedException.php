<?php

namespace Phpactor\CodeTransform\Domain\Refactor\ImportClass;

use Phpactor\CodeTransform\Domain\Exception\TransformException;

abstract class NameAlreadyUsedException extends TransformException
{
    abstract public function name(): string;
}
