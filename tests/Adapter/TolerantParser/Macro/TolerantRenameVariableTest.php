<?php

namespace Phpactor\CodeTransform\Tests\Adapter\TolerantParser\Macro;

use Phpactor\CodeTransform\Tests\Adapter\TolerantParser\TolerantTestCase;
use Phpactor\CodeTransform\Adapter\TolerantParser\Macro\TolerantRenameVariable;
use Phpactor\CodeTransform\Domain\Refactor\RenameVariable;
use Phpactor\CodeTransform\Domain\SourceCode;

class TolerantRenameVariableTest extends TolerantTestCase
{
    /**
     * @dataProvider provideRenameMethod
     */
    public function testRenameVariable(string $test, $name, string $scope = TolerantRenameVariable::SCOPE_FILE)
    {
        list($source, $expected, $offset) = $this->sourceExpectedAndOffset(__DIR__ . '/fixtures/' . $test);

        $renameVariable = new TolerantRenameVariable($this->parser());
        $transformed = $this->executeMacro($renameVariable, [
            'sourceCode' => SourceCode::fromString($source),
            'offset' => $offset,
            'newName' => $name,
            'scope' => $scope
        ]);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideRenameMethod()
    {
        return [
            'one instance no context' => [
                'renameVariable1.test',
                'newName'
            ],
            'two instances no context' => [
                'renameVariable2.test',
                'newName'
            ],
            'local scope' => [
                'renameVariable3.test',
                'newName',
                TolerantRenameVariable::SCOPE_LOCAL
            ],
            'parameters from declaration' => [
                'renameVariable4.test',
                'newName'
            ],
            'local parameter from body' => [
                'renameVariable4.test',
                'newName',
                TolerantRenameVariable::SCOPE_LOCAL
            ],
            'typed parameter' => [
                'renameVariable5.test',
                'newName',
                TolerantRenameVariable::SCOPE_LOCAL
            ],
        ];
    }
}
