<?php

namespace Phpactor\CodeTransform\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Phpactor\CodeTransform\Domain\ClassName;

class ClassNameTest extends TestCase
{
    /**
     * It returns the namespace
     */
    public function testNamespace()
    {
        $class = ClassName::fromString('This\\Is\\A\\Namespace\\ClassName');
        $this->assertEquals('This\\Is\\A\\Namespace', $class->namespace());
    }

    /**
     * It returns empty strsing if no namespace
     */
    public function testNamespaceNone()
    {
        $class = ClassName::fromString('ClassName');
        $this->assertEquals('', $class->namespace());
    }

    /**
     * @testdox It returns the class short name
     */
    public function testShort()
    {
        $class = ClassName::fromString('Namespace\\ClassName');
        $this->assertEquals('ClassName', $class->short());
    }

    /**
     * @testdox It returns the class short name with no namespace
     */
    public function testShortNoNamespace()
    {
        $class = ClassName::fromString('ClassName');
        $this->assertEquals('ClassName', $class->short());
    }

    /**
     * @testdox It throws exception if classname is empty.
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Class name cannot be empty
     */
    public function testEmpty()
    {
        ClassName::fromString('');
    }
}
