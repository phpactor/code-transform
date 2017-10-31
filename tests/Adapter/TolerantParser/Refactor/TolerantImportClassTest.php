<?php

namespace Phpactor\CodeTransform\Tests\Adapter\TolerantParser\Refactor;

use Phpactor\CodeTransform\Tests\Adapter\TolerantParser\TolerantTestCase;
use Phpactor\CodeTransform\Adapter\TolerantParser\Refactor\TolerantImportClass;
use Phpactor\CodeTransform\Domain\ClassFinder\ArrayClassFinder;
use Phpactor\CodeTransform\Domain\SourceCode;

class TolerantImportClassTest extends TolerantTestCase
{
    /**
     * @dataProvider provideRenameMethod
     */
    public function testImportClass(string $test, int $offset, string $name)
    {
        list($source, $expected) = $this->splitInitialAndExpectedSource(__DIR__ . '/fixtures/' . $test);

        $renameVariable = new TolerantImportClass($this->updater(), $this->parser());
        $transformed = $renameVariable->importClass(SourceCode::fromString($source), $offset, $name);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideRenameMethod()
    {
        return [
            'import class with existing class imports' => [
                'importClass1.test',
                48,
                'Foobar',
            ],
            'import class with namespace' => [
                'importClass2.test',
                31,
                'Foobar',
            ],
            'import class with no namespace declaration or use statements' => [
                'importClass3.test',
                12,
                'Foobar',
            ],
        ];
    }
}
