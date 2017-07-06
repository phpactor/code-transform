<?php

namespace Phpactor\CodeTransform\Tests\Adapter;

use Phpactor\CodeTransform\Adapter\TolerantParser\TolerantTypeInferer;
use Phpactor\CodeTransform\Domain\Offset;
use Phpactor\CodeTransform\Domain\InferredType;
use PHPUnit\Framework\TestCase;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Domain\TypeInferer;
use Phpactor\CodeTransform\Domain\SourceCodeLoader;
use Prophecy\Argument;

abstract class TypeInferrerTestCase extends TestCase
{
    private $sourceCodeLoader;

    public function setUp()
    {
        $this->sourceCodeLoader = $this->prophesize(SourceCodeLoader::class);
    }

    protected function sourceCodeLoader()
    {
        return $this->sourceCodeLoader->reveal();
    }

    abstract protected function inferrer(): TypeInferer;

    /**
     * @dataProvider provideTests
     */
    public function testAdapter(string $source, int $offset, InferredType $expectedType)
    {
        $this->sourceCodeLoader->loadSourceFor(Argument::any())->willReturn(SourceCode::fromString($source));
        $result = $this->inferrer()->inferTypeAtOffset(SourceCode::fromString($source), Offset::fromInt($offset));
        $this->assertEquals($expectedType, $result->type());
    }

    public function provideTests()
    {
        return [
            'It should return unknown type for whitespace' => [
                '    ',
                1,
                InferredType::unknown()
            ],
            'It should return the name of a class' => [
                <<<'EOT'
<?php

$foo = new ClassName();

EOT
                , 23, InferredType::fromString('ClassName')
            ],
            'It should return the fully qualified name of a class' => [
                <<<'EOT'
<?php

namespace Foobar\Barfoo;

$foo = new ClassName();

EOT
                , 47, InferredType::fromString('Foobar\Barfoo\ClassName')
            ],
            'It should return the fully qualified name of a with an imported name.' => [
                <<<'EOT'
<?php

namespace Foobar\Barfoo;

use BarBar\ClassName();

$foo = new ClassName();

EOT
                , 70, InferredType::fromString('BarBar\ClassName')
            ],
            'It should return the fully qualified name of a use definition' => [
                <<<'EOT'
<?php

namespace Foobar\Barfoo;

use BarBar\ClassName();

$foo = new ClassName();

EOT
                , 46, InferredType::fromString('BarBar\ClassName')
            ],
            'It returns the FQN of a method parameter' => [
                <<<'EOT'
<?php

namespace Foobar\Barfoo;

class Foobar
{
    public function foobar(Barfoo $barfoo)
    {
    }
}

EOT
                , 77, InferredType::fromString('Foobar\Barfoo\Barfoo')
            ],
            'It returns the FQN of a static call' => [
                <<<'EOT'
<?php

namespace Foobar\Barfoo;

use Acme\Factory;

$foo = Factory::create();

EOT
                , 63, InferredType::fromString('Acme\Factory')
            ],
            'It returns the FQN of a method parameter' => [
                <<<'EOT'
<?php

namespace Foobar\Barfoo;

use Acme\Factory;

class Foobar
{
    public function hello(World $world)
    {
    }
}

EOT
                , 102, InferredType::fromString('Foobar\Barfoo\World')
            ],
            'It returns the FQN of variable assigned in the method declaration' => [
                <<<'EOT'
<?php

namespace Foobar\Barfoo;

use Acme\Factory;

class Foobar
{
    public function hello(World $world)
    {
        echo $world;
    }
}

EOT
                , 127, InferredType::fromString('Foobar\Barfoo\World')
            ],
            'It returns types for reassigned variables' => [
                <<<'EOT'
<?php

namespace Foobar\Barfoo;

use Acme\Factory;

class Foobar
{
    public function hello(World $world)
    {
        $foobar = $world;
        $foobar;
    }
}

EOT
                , 154, InferredType::fromString('Foobar\Barfoo\World')
            ],
            'It returns type for $this' => [
                <<<'EOT'
<?php

namespace Foobar\Barfoo;

use Acme\Factory;

class Foobar
{
    public function hello(World $world)
    {
        $this;
    }
}

EOT
                , 126, InferredType::fromString('Foobar\Barfoo\Foobar')
            ],
            'It returns type for a method' => [
                <<<'EOT'
<?php

namespace Foobar\Barfoo;

use Acme\Factory;
use Things\Response;

class Barfoo
{
    public function callMe(): Response
}

class Foobar
{
    public function hello(Barfoo $world)
    {
        $world->callMe();
    }
}

EOT
                , 211, InferredType::fromString('Things\Response')
            ],
            'It returns type for a property' => [
                <<<'EOT'
<?php

namespace Foobar\Barfoo;

use Acme\Factory;
use Things\Response;

class Foobar
{
    /**
     * @var \Hello\World
     */
    private $foobar;

    public function hello(Barfoo $world)
    {
        $this->foobar;
    }
}
EOT
                , 215, InferredType::fromString('Hello\World')
            ],
            'It returns type for a member access expression' => [
                <<<'EOT'
<?php

namespace Foobar\Barfoo;

class Type3
{
    public function foobar(): Foobar
    {
    }
    }

class Type2
{
    public function type3(): Type3
    {
    }
}

class Type1
{
    public function type2(): Type2
    {
    }
}

class Foobar
{
    /**
     * @var Type1
     */
    private $foobar;

    public function hello(Barfoo $world)
    {
        $this->foobar->type2()->type3();
    }
}
EOT
                , 384, InferredType::fromString('Foobar\Barfoo\Type3')
            ],
            'It returns type for a variable assigned to an access expression' => [
                <<<'EOT'
<?php

namespace Foobar\Barfoo;

class Type1
{
    public function type2(): Type2
    {
    }
}

class Foobar
{
    /**
     * @var Type1
     */
    private $foobar;

    public function hello(Barfoo $world)
    {
        $foobar = $this->foobar->type2();
        $foobar;
    }
}
EOT
                , 269, InferredType::fromString('Foobar\Barfoo\Type2')
            ],
            'It returns type for a new instantiation' => [
                <<<'EOT'
<?php

new Bar();
EOT
                , 9, InferredType::fromString('Bar')
            ],
            'It returns type for an array access' => [
                <<<'EOT'
<?php

$foobar['barfoo'] = new Bar();
$barbar = $foobar['barfoo'];
$barbar;
EOT
                , 69, InferredType::fromString('Bar')
            ],
            'It returns type for a for each member (with a docblock)' => [
                <<<'EOT'
<?php

/** @var $foobar Foobar */
foreach ($collection as $foobar) {
    $foobar->foobar();
}
EOT
                , 75, InferredType::fromString('Foobar')
            ],
            'It returns the FQN for self' => [
                <<<'EOT'
<?php

namespace Foobar\Barfoo;

class Foobar
{
    public function foobar(Barfoo $barfoo)
    {
        self::foobar();
    }
}

EOT
                , 106, InferredType::fromString('Foobar\Barfoo\Foobar')
            ],
        ];

    }
}
