<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Refactor;

use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;
use Phpactor\CodeTransform\Adapter\WorseReflection\Refactor\WorseGenerateMethod;

class WorseGenerateMethodTest extends WorseTestCase
{
    /**
     * @dataProvider provideExtractMethod
     */
    public function testGenerateMethod(string $test, int $start, $name = null)
    {
        list($source, $expected) = $this->splitInitialAndExpectedSource(__DIR__ . '/fixtures/' . $test);

        $generateMethod = new WorseGenerateMethod($this->reflectorFor($source), $this->updater());
        $transformed = $generateMethod->generateMethod($source, $start, $name);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideExtractMethod()
    {
        return [
            'string' => [
                'generateMethod1.test',
                82,
            ]
        ];
    }
}
