<?php

namespace Phpactor\CodeTransform\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Phpactor\CodeTransform\Domain\Editor;

class EditorTest extends TestCase
{
    /**
     * @testdox It will indent a given number of setps.
     */
    public function testIndent()
    {
        $this->assertEquals('    Bear', (string) $this->edit('Bear')->indent(2));
    }

    /**
     * @testdox It preg_replaces
     */
    public function testPregReplace()
    {
        $this->assertEquals('Stoat', (string) $this->edit('Bear')->pregReplace('{^Bear}', 'Stoat'));
    }

    /**
     * @testdox It trims
     */
    public function testTrim()
    {
        $this->assertEquals('Bear', (string) $this->edit('    Bear    ')->trim());
    }

    /**
     * @testdox It allows different indentation
     */
    public function testIndentation()
    {
        $this->assertEquals('        Bear', (string) $this->edit('Bear', '    ')->indent(2));
    }

    /**
     * @testdox It indents multiple lines
     */
    public function testIndentMultipleLines()
    {
        $this->assertEquals(<<<EOT
    Hello
    This
EOT
        , (string) $this->edit("Hello\nThis")->indent(2));
    }

    private function edit(string $text, $indentation = '  ')
    {
        return (new Editor($indentation))->edit($text);
    }
}
