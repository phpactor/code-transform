<?php

namespace Phpactor\CodeTransform\Tests\Adapter\TolerantParser\Refactor;

use Phpactor\CodeTransform\Tests\Adapter\TolerantParser\TolerantTestCase;
use Phpactor\CodeTransform\Adapter\TolerantParser\Refactor\TolerantRenameVariable;
use Phpactor\CodeTransform\Domain\Refactor\RenameVariable;

class TolerantRenameVariableTest extends TolerantTestCase
{
    /**
     * @dataProvider provideRenameMethod
     */
    public function testRenameVariable(string $test, int $offset, $name, string $scope = RenameVariable::SCOPE_FILE)
    {
        list($source, $expected) = $this->splitInitialAndExpectedSource(__DIR__ . '/fixtures/' . $test);

        $renameVariable = new TolerantRenameVariable($this->parser());
        $transformed = $renameVariable->renameVariable($source, $offset, $name, $scope);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideRenameMethod()
    {
        return [
            'one instance no context' => [
                'renameVariable1.test',
                9,
                'newName'
            ],
            'two instances no context' => [
                'renameVariable2.test',
                9,
                'newName'
            ],
            'local scope' => [
                'renameVariable3.test',
                83,
                'newName',
                RenameVariable::SCOPE_LOCAL
            ],
            'parameters from declaration' => [
                'renameVariable4.test',
                58,
                'newName'
            ],
            'local parameter from body' => [
                'renameVariable4.test',
                79,
                'newName',
                RenameVariable::SCOPE_LOCAL
            ],
        ];
    }
}
