<?php

namespace Phpactor\CodeTransform\Domain\Refactor\ImportClass;

class ClassAlreadyImportedException extends NameAlreadyUsedException
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $existingName;

    public function __construct(string $name, string $existingName)
    {
        parent::__construct(sprintf(
            'Class "%s" is already imported',
            $name
        ));

        $this->name = $name;
        $this->existingName = $existingName;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function existingName(): string
    {
        return $this->existingName;
    }
}
