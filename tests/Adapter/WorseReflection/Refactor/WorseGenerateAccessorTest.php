<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Refactor;

use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;
use Phpactor\CodeTransform\Adapter\WorseReflection\Refactor\WorseGenerateAccessor;

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
        $transformed = $generateAccessor->generateAccessor($source, $start);

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
        ];
    }

    public function testNonProperty()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Symbol at offset "9" is not a property');
        $source = '<?php echo "hello";';

        $generateAccessor = new WorseGenerateAccessor($this->reflectorFor(''), $this->updater());
        $generateAccessor->generateAccessor($source, 9);
    }
}
