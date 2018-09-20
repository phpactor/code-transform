<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Transformer;

use Phpactor\CodeTransform\Domain\SourceCode;

use Phpactor\CodeTransform\Adapter\WorseReflection\Transformer\CompleteConstructor;
use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;

class CompleteConstructorTest extends WorseTestCase
{
    /**
     * @dataProvider provideCompleteConstructor
     */
    public function testCompleteConstructor(string $example, string $expected)
    {
        $source = SourceCode::fromString($example);
        $transformer = new CompleteConstructor($this->reflectorFor($example), $this->updater());
        $source = $transformer->transform($source);
        $this->assertEquals((string) $expected, (string) $source);
    }

    public function provideCompleteConstructor()
    {
        yield 'It does nothing on source with no classes' => [
            <<<'EOT'
<?php
EOT
        ,
            <<<'EOT'
<?php
EOT

        ];

        yield  'It does nothing on an empty constructor' => [
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

        ];

        yield  'It does nothing with no constructor' => [
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

        ];

        yield  'It does adds assignations and properties' => [
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

        ];

        yield  'adds assignations and properties on abstract class' => [
            <<<'EOT'
<?php

abstract class Foobar
{
    public function __construct($foo, $bar)
    {
    }
}
EOT
        ,
            <<<'EOT'
<?php

abstract class Foobar
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

        ];

        yield  'It does adds type docblocks' => [
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

        ];

        yield  'It is idempotent' => [
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

        ];

        yield  'It is updates missing' => [
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
     * @var string
     */
    private $foo;

    /**
     * @var Acme
     */
    private $acme;


    public function __construct(string $foo, Acme $acme)
    {
        $this->foo = $foo;
        $this->acme = $acme;
    }
}
EOT

        ];

        yield  'It does not redeclare' => [
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
        $this->foo = $foo ?: null;
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
        $this->foo = $foo ?: null;
    }
}
EOT

        ];

        yield  'Existing property with assignment' => [
            <<<'EOT'
<?php

class Foobar
{
    /**
     * @var string
     */
    private $foo = false;

    public function __construct($bar)
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
    private $foo = false;
    private $bar;


    public function __construct($bar)
    {
        $this->bar = $bar;
    }
}
EOT

        ];
    }
}
