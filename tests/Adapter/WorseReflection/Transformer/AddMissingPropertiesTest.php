<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Transformer;

use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Adapter\WorseReflection\Transformer\AddMissingProperties;
use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;

class AddMissingPropertiesTest extends WorseTestCase
{
    /**
     * @dataProvider provideCompleteConstructor
     */
    public function testAddMissingProperties(string $example, string $expected)
    {
        $source = SourceCode::fromString($example);
        $transformer = new AddMissingProperties($this->reflectorFor($example), $this->updater());
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
            'It adds missing properties' => [
                <<<'EOT'
<?php

class Foobar
{
    public function hello()
    {
        $this->hello = 'Hello';
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
    private $hello;

    public function hello()
    {
        $this->hello = 'Hello';
    }
}
EOT

            ],
            'It ignores existing properties' => [
                <<<'EOT'
<?php

class Foobar
{
    /**
     * @var string
     */
    private $hello;

    public function hello()
    {
        $this->hello = 'Hello';
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
    private $hello;

    public function hello()
    {
        $this->hello = 'Hello';
    }
}
EOT

            ],
            'It ignores existing properties of a different visibility' => [
                <<<'EOT'
<?php

class Foobar
{
    /**
     * @var string
     */
    public $hello;

    public function hello()
    {
        $this->hello = 'Hello';
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
    public $hello;

    public function hello()
    {
        $this->hello = 'Hello';
    }
}
EOT
            ],
            'It appends new properties' => [
                <<<'EOT'
<?php

class Foobar
{
    /**
     * @var string
     */
    public $hello;

    public function hello()
    {
        $this->foobar = 1234;
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
    public $hello;

    /**
     * @var int
     */
    private $foobar;


    public function hello()
    {
        $this->foobar = 1234;
    }
}
EOT
            ],
            'It appends new properties in a namespaced class' => [
                <<<'EOT'
<?php

namespace Hello;

class Foobar
{
    /**
     * @var string
     */
    public $hello;

    public function hello()
    {
        $this->foobar = 1234;
    }
}
EOT
                ,
                <<<'EOT'
<?php

namespace Hello;

class Foobar
{
    /**
     * @var string
     */
    public $hello;

    /**
     * @var int
     */
    private $foobar;


    public function hello()
    {
        $this->foobar = 1234;
    }
}
EOT
            ],
            'Properties should only be taken from current class' => [
                <<<'EOT'
<?php

namespace Hello;

class Dodo
{
    public function goodbye()
    {
        $this->dodo = 'string';
    }
}

class Foobar extends Dodo
{
    public function hello()
    {
        $this->foobar = 1234;
    }
}
EOT
                ,
                <<<'EOT'
<?php

namespace Hello;

class Dodo
{
    /**
     * @var string
     */
    private $dodo;

    public function goodbye()
    {
        $this->dodo = 'string';
    }
}

class Foobar extends Dodo
{
    /**
     * @var int
     */
    private $foobar;

    public function hello()
    {
        $this->foobar = 1234;
    }
}
EOT
            ],
            'It adds missing properties using the imported type' => [
                <<<'EOT'
<?php

use MyLibrary\Hello;

class Foobar
{
    public function hello()
    {
        $this->hello = new Hello();
    }
}
EOT
                ,
                <<<'EOT'
<?php

use MyLibrary\Hello;

class Foobar
{
    /**
     * @var Hello
     */
    private $hello;

    public function hello()
    {
        $this->hello = new Hello();
    }
}
EOT

            ],
            'It missing properties with an untyped parameter' => [
                <<<'EOT'
<?php

use MyLibrary\Hello;

class Foobar
{
    public function hello($string)
    {
        $this->hello = $string;
    }
}
EOT
                ,
                <<<'EOT'
<?php

use MyLibrary\Hello;

class Foobar
{
    private $hello;

    public function hello($string)
    {
        $this->hello = $string;
    }
}
EOT

            ],
            'It adds missing trait properties within the Trait' => [
                <<<'EOT'
<?php

trait Foobar
{
    public function hello()
    {
        $this->hello = 'goodbye';
    }
}
EOT
                ,
                <<<'EOT'
<?php

trait Foobar
{
    /**
     * @var string
     */
    private $hello;

    public function hello()
    {
        $this->hello = 'goodbye';
    }
}
EOT
            ],
        ];
    }
}
