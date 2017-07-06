<?php

namespace Phpactor\CodeTransform\Tests\Adapter\TolerantParser\Transformer;

use Phpactor\CodeTransform\Domain\SourceCode;

use PHPUnit\Framework\TestCase;
use Phpactor\CodeTransform\Adapter\TolerantParser\Transformer\CompleteConstructor;

class CompleteConstructorTest extends TestCase
{
    /**
     * @dataProvider provideCompleteConstructor
     */
    public function testCompleteConstructor(string $example, string $expected)
    {
        $source = SourceCode::fromString($example);
        $transformer = new CompleteConstructor();
        $source = $transformer->transform($source);
        $this->assertEquals((string) $expected, (string) $source);
    }

    public function provideCompleteConstructor()
    {
        return [
            'It does nothing on source with no classes' => [
                <<<'EOT'
<?php

EOT
                , 
                <<<'EOT'
<?php

EOT

            ],
            'It does nothing on an empty constructor' => [
                <<<'EOT'
<?php

class Foobar
{
    public function __construct()
    {
    }
}
EOT
                , 
                <<<'EOT'
<?php

class Foobar
{
    public function __construct()
    {
    }
}
EOT

            ],
            'It does nothing with no constructor' => [
                <<<'EOT'
<?php

class Foobar
{
}
EOT
                , 
                <<<'EOT'
<?php

class Foobar
{
}
EOT

            ],
            'It does adds assignations and properties' => [
                <<<'EOT'
<?php

class Foobar
{
    public function __construct($foo, $bar)
    {
    }
}
EOT
                , 
                <<<'EOT'
<?php

class Foobar
{
    private $foo;
    private $bar;

    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}
EOT

            ],
            'It does adds type docblocks' => [
                <<<'EOT'
<?php

class Foobar
{
    public function __construct(string $foo, Foobar $bar)
    {
    }
}
EOT
                , 
                <<<'EOT'
<?php

class Foobar
{
    /**
     * @var string
     */
    private $foo;

    /**
     * @var Foobar
     */
    private $bar;

    public function __construct(string $foo, Foobar $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}
EOT

            ],
            'It is idempotent' => [
                <<<'EOT'
<?php

class Foobar
{
    /**
     * @var string
     */
    private $foo;

    public function __construct(string $foo)
    {
        $this->foo = $foo;
    }
}
EOT
                , 
                <<<'EOT'
<?php

class Foobar
{
    /**
     * @var string
     */
    private $foo;

    public function __construct(string $foo)
    {
        $this->foo = $foo;
    }
}
EOT

            ],
            'It is updates missing' => [
                <<<'EOT'
<?php

class Foobar
{
    /**
     * @var string
     */
    private $foo;

    public function __construct(string $foo, Acme $acme)
    {
        $this->foo = $foo;
    }
}
EOT
                , 
                <<<'EOT'
<?php

class Foobar
{

    /**
     * @var Acme
     */
    private $acme;

    /**
     * @var string
     */
    private $foo;

    public function __construct(string $foo, Acme $acme)
    {
        $this->acme = $acme;
        $this->foo = $foo;
    }
}
EOT

            ],
        ];
    }
}
