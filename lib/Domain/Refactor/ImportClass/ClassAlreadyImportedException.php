<?php

namespace Phpactor\CodeTransform\Domain\Refactor\ImportClass;

use Phpactor\CodeTransform\Domain\Exception\TransformException;

class ClassAlreadyImportedException extends NameAlreadyUsedException
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        parent::__construct(sprintf(
            'Class "%s" is already imported', $name
        ));

        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }
}
