<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Refactor;

use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;
use Phpactor\CodeTransform\Adapter\WorseReflection\Refactor\WorseExtractMethod;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeBuilder\Adapter\WorseReflection\WorseBuilderFactory;

class WorseExtractMethodTest extends WorseTestCase
{
    /**
     * @dataProvider provideExtractMethod
     */
    public function testExtractMethod(string $test, $name)
    {
        list($source, $expected, $offsetStart, $offsetEnd) = $this->sourceExpectedAndOffset(__DIR__ . '/fixtures/' . $test);

        $reflector = $this->reflectorFor($source);
        $factory = new WorseBuilderFactory($reflector);
        $extractMethod = new WorseExtractMethod($reflector, $factory, $this->updater());
        $transformed = $extractMethod->extractMethod(SourceCode::fromString($source), $offsetStart, $offsetEnd, $name);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideExtractMethod()
    {
        return [
            'string' => [
                'extractMethod1.test',
                'newMethod'
            ],
        ];
    }
}
