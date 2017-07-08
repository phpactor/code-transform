<?php

namespace Phpactor\CodeTransform\Domain;

use Phpactor\CodeTransform\Domain\EditorSubject;

final class Editor
{
    /**
     * @var string
     */
    private $indentString;

    public function __construct(string $indentString = '  ')
    {
        $this->indentString = $indentString;
    }

    public function edit(string $text)
    {
        return new EditorSubject($this, $text);
    }

    public function indentString(): string
    {
        return $this->indentString;
    }
}

