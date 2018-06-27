<?php

namespace Phpactor\CodeTransform\Tests\Adapter\TolerantParser\Refactor;

use Exception;
use Phpactor\CodeTransform\Adapter\TolerantParser\Refactor\TolerantExtractExpression;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Tests\Adapter\TolerantParser\TolerantTestCase;

class TolerantExtractExpressionTest extends TolerantTestCase
{
    /**
     * @dataProvider provideExtractExpression
     */
    public function testExtractExpression(string $test, string $name, string $expectedExceptionMessage = null)
    {
        list($source, $expected, $offsetStart, $offsetEnd) = $this->sourceExpectedAndOffset(__DIR__ . '/fixtures/' . $test);

        if ($expectedExceptionMessage) {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $extractMethod = new TolerantExtractExpression();
        $transformed = $extractMethod->extractExpression(SourceCode::fromString($source), $offsetStart, $offsetEnd, $name);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideExtractExpression()
    {
        yield 'no op' => [
            'extractExpression1.test',
            'foobar',
        ];

        yield 'extract string literal' => [
            'extractExpression2.test',
            'foobar',
        ];

        yield 'extract on end position semi-colon' => [
            'extractExpression3.test',
            'foobar',
        ];

        yield 'single node' => [
            'extractExpression4.test',
            'foobar',
        ];

        yield 'single node' => [
            'extractExpression4.test',
            'foobar',
        ];

        yield 'single array expression' => [
            'extractExpression5.test',
            'foobar',
        ];

        yield 'single stand-alone array expression' => [
            'extractExpression6.test',
            'foobar',
        ];

        yield 'string concatenation' => [
            'extractExpression7.test',
            'foobar',
        ];

        yield 'preserve statement indentation' => [
            'extractExpression8.test',
            'foobar',
        ];

        yield 'extract element in array' => [
            'extractExpression9.test',
            'foobar',
        ];
    }
}
