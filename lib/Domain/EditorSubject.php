<?php

namespace Phpactor\CodeTransform\Domain;

use Phpactor\CodeTransform\Domain\EditorSubject;

final class EditorSubject
{
    /**
     * @var Editor
     */
    private $editor;

    /**
     * @var text
     */
    private $text;

    public function __construct(Editor $editor, string $text)
    {
        $this->editor = $editor;
        $this->text = $text;
    }

    public function __toString()
    {
        return $this->text;
    }

    public function spawn(string $text)
    {
        return new EditorSubject($this->editor, $text);
    }

    public function indent(int $amount)
    {
        $lines = explode(PHP_EOL, $this->text);

        return implode(PHP_EOL, array_map(function ($line) use ($amount) {
            return str_repeat($this->editor->indentString(), $amount) . $line;
        }, $lines));
    }

    public function pregReplace(string $pattern, string $replacement)
    {
        return $this->spawn(preg_replace($pattern, $replacement, $this->text));
    }

    public function trim()
    {
        return $this->spawn(trim($this->text));
    }
}


