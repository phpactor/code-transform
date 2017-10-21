<?php

namespace Phpactor\CodeTransform\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Phpactor\CodeTransform\Domain\SourceCode;

class SourceCodeTest extends TestCase
{
    const PATH = '/bar';
    const SOURCE = 'asd';
    const OTHER_SOURCE = 'other source';


    public function testPath()
    {
        $source = SourceCode::fromStringAndPath(self::SOURCE, self::PATH);

        $this->assertEquals(self::PATH, $source->path());
    }

    public function testWithSource()
    {
        $source1 = SourceCode::fromStringAndPath(self::SOURCE, self::PATH);
        $source2 = $source1->withSource(self::OTHER_SOURCE);

        $this->assertEquals(self::OTHER_SOURCE, $source2->__toString());
        $this->assertNotSame($source1, $source2);
    }

    public function testNonAbsolutePath()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Path "path" must be absolute');
        SourceCode::fromStringAndPath('asdf', 'path');
    }

    public function testCanonicalizePath()
    {
        $sourceCode = SourceCode::fromStringAndPath('asd', '/path/to/here/../');
        $this->assertEquals('/path/to', $sourceCode->path());
    }
}
