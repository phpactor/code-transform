<?php

namespace Phpactor\CodeTransform\Tests\Adapter\TolerantParser\Refactor;

use Phpactor\CodeTransform\Tests\Adapter\TolerantParser\TolerantTestCase;

class TolerantChangeVisiblityTest extends TolerantTestCase
{
    /**
     * @dataProvider provideChangeVisibility
     */
    public function testExtractExpression(string $test)
    {
        list($source, $expected, $offsetStart) = $this->sourceExpectedAndOffset(__DIR__ . '/fixtures/' . $test);

        $extractMethod = new TolerantChangeVisiblity();
        $transformed = $extractMethod->changeVisiblity(SourceCode::fromString($source), $offsetStart);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideChangeVisibility()
    {
        yield 'no op' => [ 'changeVisibility1.test' ];
        yield 'from public to protected' => [ 'changeVisibility2.test' ];
    }
}
