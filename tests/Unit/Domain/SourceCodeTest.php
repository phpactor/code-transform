<?php

namespace Phpactor\CodeTransform\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Phpactor\CodeTransform\Domain\SourceCode;
use RuntimeException;

class SourceCodeTest extends TestCase
{
    const PATH = '/bar';
    const SOURCE = 'asd';
    const OTHER_SOURCE = 'other source';
    const OTHER_PATH = '/other/path.php';


    public function testPath()
    {
        $source = SourceCode::fromStringAndPath(self::SOURCE, self::PATH);

        $this->assertEquals(self::PATH, $source->path());
    }

    public function testFromUnknownReturnsSourceCodeIfPassedSourceCode()
    {
        $source1 = SourceCode::fromStringAndPath(self::SOURCE, self::PATH);
        $source2 = SourceCode::fromUnknown($source1);

        $this->assertSame($source1, $source2);
    }

    public function testFromUnknownReturnsSourceCodeIfPassedString()
    {
        $source1 = 'hello';
        $source2 = SourceCode::fromUnknown($source1);

        $this->assertEquals(SourceCode::fromString($source1), $source2);
    }

    public function testFromUnknownThrowsExceptionIfTypeIsInvalid()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Do not know');
        $source2 = SourceCode::fromUnknown(1234);
    }

    public function testWithSource()
    {
        $source1 = SourceCode::fromStringAndPath(self::SOURCE, self::PATH);
        $source2 = $source1->withSource(self::OTHER_SOURCE);

        $this->assertEquals(self::OTHER_SOURCE, $source2->__toString());
        $this->assertNotSame($source1, $source2);
    }

    public function testWithPath()
    {
        $source1 = SourceCode::fromStringAndPath(self::SOURCE, self::PATH);
        $source2 = $source1->withPath(self::OTHER_PATH);

        $this->assertEquals(self::OTHER_PATH, $source2->path());
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

    public function testExtractSelection()
    {
        $sourceCode = SourceCode::fromString('12345678');
        $this->assertEquals('34', $sourceCode->extractSelection(2, 4));
    }

    public function testReplaceSelection()
    {
        $sourceCode = SourceCode::fromString('12345678');
        $this->assertEquals('12HE5678', (string) $sourceCode->replaceSelection('HE', 2, 4));
    }
}
