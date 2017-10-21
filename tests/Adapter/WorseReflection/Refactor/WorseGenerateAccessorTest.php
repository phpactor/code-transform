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
    public function testGenerateAccessor(
        string $test,
        int $start,
        string $prefix = '',
        bool $upperCaseFirst = false
    ) {
        list($source, $expected) = $this->splitInitialAndExpectedSource(__DIR__ . '/fixtures/' . $test);

        $generateAccessor = new WorseGenerateAccessor($this->reflectorFor($source), $this->updater(), $prefix, $upperCaseFirst);
        $transformed = $generateAccessor->generateAccessor(SourceCode::fromString($source), $start);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideExtractAccessor()
    {
        return [
            'property' => [
                'generateAccessor1.test',
                44,
            ],
            'prefix and ucfirst' => [
                'generateAccessor2.test',
                44,
                'get',
                true,
            ],
            'return type' => [
                'generateAccessor3.test',
                80,
            ],
            'namespaced' => [
                'generateAccessor4.test',
                95,
            ],
        ];
    }

    public function testNonProperty()
    {
        $this->expectException(TransformException::class);
        $this->expectExceptionMessage('Symbol at offset "9" is not a property');
        $source = '<?php echo "hello";';

        $generateAccessor = new WorseGenerateAccessor($this->reflectorFor(''), $this->updater());
        $generateAccessor->generateAccessor(SourceCode::fromString($source), 9);
    }
}
