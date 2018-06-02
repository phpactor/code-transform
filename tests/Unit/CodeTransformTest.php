<?php

namespace Phpactor\CodeTransform\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phpactor\CodeTransform\CodeTransform;
use Phpactor\CodeTransform\Domain\Transformer;
use Phpactor\CodeTransform\Domain\SourceCode;
use Prophecy\Argument;
use Phpactor\CodeTransform\Domain\Transformers;

class CodeTransformTest extends TestCase
{
    /**
     * @testdox It should apply the given transformers to source code.
     */
    public function testApplyTransformers()
    {
        $expectedCode = SourceCode::fromString('hello goodbye');
        $trans1 = $this->prophesize(Transformer::class);
        $trans1->transform(Argument::type(SourceCode::class))->willReturn($expectedCode);

        $code = $this->create([
            'one' => $trans1->reveal()
        ])->transform('hello', [ 'one' ]);
        $this->assertSame($expectedCode, $code);
    }

    public function testAcceptsSourceCodeAsParameter()
    {
        $expectedCode = SourceCode::fromStringAndPath('hello goodbye', '/path/to');

        $trans1 = $this->prophesize(Transformer::class);
        $trans1->transform($expectedCode)->willReturn($expectedCode);

        $code = $this->create([
            'one' => $trans1->reveal()
        ])->transform($expectedCode, [ 'one' ]);

        $this->assertSame($expectedCode, $code);
    }

    public function create(array $transformers): CodeTransform
    {
        return CodeTransform::fromTransformers(Transformers::fromArray($transformers));
    }
}
