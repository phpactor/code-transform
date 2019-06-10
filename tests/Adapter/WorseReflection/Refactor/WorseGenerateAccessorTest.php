<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Refactor;

use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;
use Phpactor\CodeTransform\Adapter\WorseReflection\Refactor\WorseGenerateAccessor;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\WorseReflection\Core\Exception\ItemNotFound;

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
        list($source, $expected, $propertyName) = $this->sourceExpectedAndWordUnderCursor(
            __DIR__ . '/fixtures/' . $test
        );

        $generateAccessor = new WorseGenerateAccessor(
            $this->reflectorFor($source),
            $this->updater(),
            $prefix,
            $upperCaseFirst
        );
        $transformed = $generateAccessor->generate(SourceCode::fromString($source), $propertyName);

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
        $this->expectException(ItemNotFound::class);
        $this->expectExceptionMessage('Unknown item "bar", known items: "foo"');
        $source = '<?php class Foo { private $foo; }';

        $generateAccessor = new WorseGenerateAccessor($this->reflectorFor(''), $this->updater());
        $generateAccessor->generate(SourceCode::fromString($source), 'bar');
    }
}
