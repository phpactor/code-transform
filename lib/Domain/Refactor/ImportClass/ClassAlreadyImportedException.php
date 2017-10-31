<?php

namespace Phpactor\CodeTransform\Domain\Refactor\ImportClass;

use Phpactor\CodeTransform\Domain\Exception\TransformException;

class ClassAlreadyImportedException extends TransformException
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        parent::__construct(sprintf(
            'Class "%s" already imported', $name
        ));

        $this->name = $name;
    }

    public function name()
    {
        return $this->name;
    }
}
