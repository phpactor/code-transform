<?php

namespace Phpactor\CodeTransform\Domain\Refactor\ImportClass;

class NameAlreadyImportedException extends NameAlreadyUsedException
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $existingName;

    public function __construct(NameImport $nameImport, string $existingName)
    {
        parent::__construct(sprintf(
            '%s "%s" is already imported',
            ucfirst($nameImport->type()),
            $nameImport->name()->head()
        ));

        $this->name = $nameImport->name()->head()->__toString();
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
