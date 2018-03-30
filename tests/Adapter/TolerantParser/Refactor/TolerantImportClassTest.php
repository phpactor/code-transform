<?php

namespace Phpactor\CodeTransform\Tests\Adapter\TolerantParser\Refactor;

use Phpactor\CodeTransform\Tests\Adapter\TolerantParser\TolerantTestCase;
use Phpactor\CodeTransform\Adapter\TolerantParser\Refactor\TolerantImportClass;
use Phpactor\CodeTransform\Domain\ClassFinder\ArrayClassFinder;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\ClassAlreadyImportedException;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\AliasAlreadyUsedException;

class TolerantImportClassTest extends TolerantTestCase
{
    /**
     * @dataProvider provideImportClass
     */
    public function testImportClass(string $test, string $name, string $alias = null)
    {
        list($expected, $transformed) = $this->importClass($test, $name, $alias);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideImportClass()
    {
        yield 'with existing class imports' => [
            'importClass1.test',
            'Foobar',
        ];

        yield 'with namespace' => [
            'importClass2.test',
            'Foobar',
        ];

        yield 'with no namespace declaration or use statements' => [
            'importClass3.test',
            'Foobar',
        ];

        yield 'with alias' => [
            'importClass4.test',
            'Foobar',
            'Barfoo',
        ];
    }

    public function testThrowsExceptionIfClassAlreadyImported()
    {
        $this->expectException(ClassAlreadyImportedException::class);
        $this->expectExceptionMessage('Class "DateTime" is already imported');
        $this->importClass('importClass1.test', 'DateTime');
    }

    public function testThrowsExceptionIfAliasAlredayUsed()
    {
        $this->expectException(AliasAlreadyUsedException::class);
        $this->expectExceptionMessage('Alias "DateTime" is already used');
        $this->importClass('importClass1.test', 'Foobar', 'DateTime');
    }

    private function importClass(string $test, string $name, string $alias = null)
    {
        list($source, $expected, $offset) = $this->sourceExpectedAndOffset(__DIR__ . '/fixtures/' . $test);
        
        $renameVariable = new TolerantImportClass($this->updater(), $this->parser());
        $transformed = $renameVariable->importClass(SourceCode::fromString($source), $offset, $name, $alias);
        return [$expected, $transformed];
    }
}
