<?php

namespace Phpactor\CodeTransform\Tests\Unit\Domain\Utils;

use PHPUnit\Framework\TestCase;
use Phpactor\CodeTransform\Domain\Utils\TextUtils;

class TextUtilsTest extends TestCase
{
    /**
     * @dataProvider provideRemoveIndentation
     */
    public function testRemoveIndentation(string $code, string $expected)
    {
        $this->assertEquals($expected, TextUtils::removeIndentation($code));
    }

    public function provideRemoveIndentation()
    {
        return [
            [
                '    hello',
                'hello'
            ],
            [
                <<<'EOT'
    hello
        world
    hello
            world
EOT
                ,
                <<<'EOT'
hello
    world
hello
        world
EOT
            ],
            [
                <<<'EOT'
hello
    hello
        world
    hello
            world
EOT
                ,
                <<<'EOT'
hello
    hello
        world
    hello
            world
EOT
            ]
        ];
    }
}
