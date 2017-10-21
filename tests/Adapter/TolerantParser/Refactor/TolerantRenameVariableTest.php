<?php

namespace Phpactor\CodeTransform\Tests\Adapter\TolerantParser\Refactor;

use Phpactor\CodeTransform\Tests\Adapter\TolerantParser\TolerantTestCase;
use Phpactor\CodeTransform\Adapter\TolerantParser\Refactor\TolerantRenameVariable;

class TolerantRenameVariableTest extends TolerantTestCase
{
    /**
     * @dataProvider provideRenameMethod
     */
    public function testRenameVariable(string $test, int $offset, $name)
    {
        list($source, $expected) = $this->splitInitialAndExpectedSource(__DIR__ . '/fixtures/' . $test);

        $extractConstant = new TolerantRenameVariable($this->parser());
        $transformed = $extractConstant->renameVariable($source, $offset, $name);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideRenameMethod()
    {
        return [
            [
                'renameVariable1.test',
                9,
                'newName'
            ]
        ];
    }
}
