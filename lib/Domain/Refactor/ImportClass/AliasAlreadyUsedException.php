<?php

namespace Phpactor\CodeTransform\Domain\Refactor\ImportClass;

class AliasAlreadyUsedException extends NameAlreadyUsedException
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $type, string $name)
    {
        parent::__construct(sprintf(
            '%s alias "%s" is already used',
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
