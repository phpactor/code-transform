<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Refactor;

use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;
use Phpactor\CodeTransform\Adapter\WorseReflection\Refactor\WorseGenerateAccessor;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Domain\Exception\TransformException;

class WorseGenerateAccessorTest extends WorseTestCase
{
    /**
     * @dataProvider provideExtractAccessor
     */
    public function testGenerateAccessorFromOffset(
        string $test,
        string $prefix = '',
        bool $upperCaseFirst = false
    ) {
        list($source, $expected, $offset) = $this->sourceExpectedAndOffset(__DIR__ . '/fixtures/' . $test);

        $generateAccessor = new WorseGenerateAccessor($this->reflectorFor($source), $this->updater(), $prefix, $upperCaseFirst);
        $transformed = $generateAccessor->generateFromOffset(SourceCode::fromString($source), $offset);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    /**
     * @dataProvider provideExtractAccessor
     */
    public function testGenerateAccessorFromPropertyName(
        string $test,
        string $prefix = '',
        bool $upperCaseFirst = false
    ) {
        list($source, $expected) = $this->sourceExpectedAndWordUnderCursor(__DIR__ . '/fixtures/' . $test);

        $generateAccessor = new WorseGenerateAccessor($this->reflectorFor($source), $this->updater(), $prefix, $upperCaseFirst);
        $transformed = $generateAccessor->generateFromPropertyName(SourceCode::fromString($source), 'method');

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

        $generateAccessor = new WorseGenerateAccessor($this->reflectorFor(''), $this->updater());
        $generateAccessor->generateFromOffset(SourceCode::fromString($source), 9);
    }
}
