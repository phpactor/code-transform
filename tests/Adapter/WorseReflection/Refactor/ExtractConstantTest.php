<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Refactor;

use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;
use Phpactor\CodeTransform\Adapter\WorseReflection\Refactor\WorseExtractConstant;

class ExtractConstantTest extends WorseTestCase
{
    /**
     * @dataProvider provideExtractMethod
     */
    public function testExtractConstant(string $test, int $start, $name)
    {
        $source = file_get_contents(__DIR__ . '/fixtures/' . $test . '.a.php');
        $expected = file_get_contents(__DIR__ . '/fixtures/' . $test . '.b.php');

        $extractConstant = new WorseExtractConstant($this->reflectorFor($source), $this->updater());
        $transformed = $extractConstant->extractConstant($source, 85, $name);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideExtractMethod()
    {
        return [
            'string' => [
                'extractConstant1',
                88,
                'HELLO_WORLD'
            ],
            'numeric' => [
                'extractConstant2',
                83,
                'HELLO_WORLD'
            ],
        ];
    }
}
