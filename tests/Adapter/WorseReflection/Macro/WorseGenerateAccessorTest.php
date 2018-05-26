<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Macro;

use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;
use Phpactor\CodeTransform\Adapter\WorseReflection\Macro\WorseGenerateAccessor;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Domain\Exception\TransformException;

class WorseGenerateAccessorTest extends WorseTestCase
{
    /**
     * @dataProvider provideExtractAccessor
     */
    public function testGenerateAccessor(
        string $test,
        string $prefix = '',
        bool $upperCaseFirst = false
    ) {
        list($source, $expected, $offset) = $this->sourceExpectedAndOffset(__DIR__ . '/fixtures/' . $test);

        $generateAccessor = new WorseGenerateAccessor($this->reflectorFor($source), $this->updater(), $prefix, $upperCaseFirst);
        $transformed = $this->executeMacro($generateAccessor, [
            'sourceCode' => SourceCode::fromString($source),
            'offset' => $offset
        ]);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideExtractAccessor()
    {
        return [
            'property' => [
                'generateAccessor1.test',
            ],
            'prefix and ucfirst' => [
                'generateAccessor2.test',
                'get',
                true,
            ],
            'return type' => [
                'generateAccessor3.test',
            ],
            'namespaced' => [
                'generateAccessor4.test',
            ],
            'pseudo-type' => [
                'generateAccessor5.test',
            ],
        ];
    }

    public function testNonProperty()
    {
        $this->expectException(TransformException::class);
        $this->expectExceptionMessage('Symbol at offset "9" is not a property');
        $source = '<?php echo "hello";';

        $generateAccessor = new WorseGenerateAccessor($this->reflectorFor($source), $this->updater());
        $this->executeMacro($generateAccessor, [
            'sourceCode' => SourceCode::fromString($source),
            'offset' => 9
        ]);
    }
}
