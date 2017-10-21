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
            ],
            'parameter' => [
                'generateMethod2.test',
                82,
            ],
            'typed parameter' => [
                'generateMethod3.test',
                90,
            ],
            'undeclared parameter' => [
                'generateMethod4.test',
                79,
            ],
            'expression' => [
                'generateMethod5.test',
                225,
            ],
            'public accessor in another class' => [
                'generateMethod6.test',
                185,
            ],
        ];
    }
}
