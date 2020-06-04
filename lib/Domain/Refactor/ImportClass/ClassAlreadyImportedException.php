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

    public function __construct(string $type, string $name, string $existingName)
    {
        parent::__construct(sprintf(
            '%s "%s" is already imported',
            ucfirst($type),
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
