<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Refactor;

use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;
use Phpactor\CodeTransform\Adapter\WorseReflection\Refactor\WorseExtractConstant;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Domain\Exception\TransformException;

class WorseExtractConstantTest extends WorseTestCase
{
    /**
     * @dataProvider provideExtractMethod
     */
    public function testExtractConstant(string $test, int $start, $name)
    {
        list($source, $expected) = $this->splitInitialAndExpectedSource(__DIR__ . '/fixtures/' . $test);

        $extractConstant = new WorseExtractConstant($this->reflectorFor($source), $this->updater());
        $transformed = $extractConstant->extractConstant(SourceCode::fromString($source), $start, $name);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideExtractMethod()
    {
        return [
            'string' => [
                'extractConstant1.test',
                88,
                'HELLO_WORLD'
            ],
            'numeric' => [
                'extractConstant2.test',
                83,
                'HELLO_WORLD'
            ],
            'array_key' => [
                'extractConstant3.test',
                83,
                'HELLO_WORLD'
            ],
            'namespaced' => [
                'extractConstant4.test',
                102,
                'HELLO_WORLD'
            ],
            'replace all' => [
                'extractConstant5.test',
                83,
                'HELLO_WORLD'
            ],
            'replace all numeric' => [
                'extractConstant6.test',
                79,
                'HOUR'
            ],
        ];
    }

    public function testNoClass()
    {
        $this->expectException(TransformException::class);
        $this->expectExceptionMessage('Node does not belong to a class');

        $code = <<<'EOT'
<?php 1234;
EOT
        ;

        $extractConstant = new WorseExtractConstant($this->reflectorFor($code), $this->updater());
        $transformed = $extractConstant->extractConstant(SourceCode::fromString($code), 8, 'asd');
    }
}
