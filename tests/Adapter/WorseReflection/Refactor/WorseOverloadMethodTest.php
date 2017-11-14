<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Refactor;

use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;
use Phpactor\CodeTransform\Adapter\WorseReflection\Refactor\WorseGenerateMethod;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Domain\Exception\TransformException;
use Phpactor\CodeTransform\Adapter\WorseReflection\Refactor\WorseOverloadMethod;

class WorseOverloadMethodTest extends WorseTestCase
{
    /**
     * @dataProvider provideExtractMethod
     */
    public function testOverloadMethod(string $test, string $className, $methodName)
    {
        list($source, $expected) = $this->splitInitialAndExpectedSource(__DIR__ . '/fixtures/' . $test);

        $transformed = $this->overloadMethod($source, $className, $methodName);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideExtractMethod()
    {
        return [
            'root type as param and return type' => [
                'overloadMethod1.test',
                'ChildClass',
                'barbar'
            ],
            'no params or return type' => [
                'overloadMethod2.test',
                'ChildClass',
                'barbar'
            ],
            'scalar type as param and return type' => [
                'overloadMethod3.test',
                'ChildClass',
                'barbar'
            ],
            'default value' => [
                'overloadMethod4.test',
                'ChildClass',
                'barbar'
            ],
        ];
    }

    public function testClassNoParent()
    {
        $this->expectException(TransformException::class);
        $this->overloadMethod('<?php class Foobar {}', 'Foobar', 'foo');
    }

    private function overloadMethod($source, string $className, $methodName)
    {
        $overload = new WorseOverloadMethod($this->reflectorFor($source), $this->updater());
        return $overload->overloadMethod(SourceCode::fromString($source), $className, $methodName);
    }
}
