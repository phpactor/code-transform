<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Helper;

use Generator;
use Phpactor\CodeTransform\Adapter\WorseReflection\Helper\WorseUnresolvableClassNameFinder;
use Phpactor\CodeTransform\Domain\NameWithByteOffset;
use Phpactor\CodeTransform\Domain\NameWithByteOffsets;
use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;
use Phpactor\Name\QualifiedName;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;

class WorseUnresolvableClassNameFinderTest extends WorseTestCase
{
    /**
     * @dataProvider provideReturnsUnresolableFunctions
     * @dataProvider provideReturnsUnresolableClass
     * @dataProvider provideConstants
     */
    public function testReturnsUnresolableClass(string $manifest, array $expectedNames)
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest($manifest);
        $source = $this->workspace()->getContents('test.php');

        $finder = new WorseUnresolvableClassNameFinder(
            $this->reflectorForWorkspace()
        );
        $found = $finder->find(
            TextDocumentBuilder::create($source)->build()
        );
        $this->assertEquals(new NameWithByteOffsets(...$expectedNames), $found);
    }

    public function provideReturnsUnresolableClass()
    {
        yield 'no classes' => [
            <<<'EOT'
// File: test.php
<?
EOT
            , []
        ];

        yield 'resolvable class in method' => [
            <<<'EOT'
// File: test.php
<?php class Foo { public function bar(Bar $bar) {} }
EOT
            , [
                new NameWithByteOffset(
                    QualifiedName::fromString('Bar'),
                    ByteOffset::fromInt(38)
                ),
            ]
        ];
        
        yield 'class imported in list' => [
            <<<'EOT'
// File: test.php
<?php use Bar\{Foo}; Foo::foo();
// File: Bar.php
<?php namespace Bar; class Foo {}
EOT
            , [
            ]
        ];

        yield 'unresolvable class' => [
            <<<'EOT'
// File: test.php
<?php new NotFound();
EOT
            ,[
                new NameWithByteOffset(
                    QualifiedName::fromString('NotFound'),
                    ByteOffset::fromInt(10)
                ),
            ]
        ];

        yield 'namespaced unresolvable class' => [
            <<<'EOT'
// File: test.php
<?php namespace Foo; new NotFound();
EOT
            , [
                new NameWithByteOffset(
                    QualifiedName::fromString('Foo\\NotFound'),
                    ByteOffset::fromInt(25)
                ),
            ]
        ];

        yield 'multiple unresolvable classes' => [
            <<<'EOT'
// File: test.php
<?php 

new Bar\NotFound();

class Bar {}

new NotFound36();
EOT
,
            [
                new NameWithByteOffset(
                    QualifiedName::fromString('Bar\\NotFound'),
                    ByteOffset::fromInt(12)
                ),
                new NameWithByteOffset(
                    QualifiedName::fromString('NotFound36'),
                    ByteOffset::fromInt(47)
                ),
            ]
        ];

        yield 'external resolvable class' => [
            <<<'EOT'
// File: Foobar.php
<?php

namespace Foobar;

class Barfoo {}
// File: test.php
<?php 

use Foobar\Barfoo;

new Barfoo();
EOT
,
            [
            ]
        ];

        yield 'reserved names' => [
            <<<'EOT'
// File: test.php
<?php

namespace Foobar;

class Barfoo { 
    public function foo(): self {}
    public function bar(): {
        static::foo();
        parent::foo();
    }
}
EOT
,
            [
            ]
        ];
    }

    public function provideReturnsUnresolableFunctions(): Generator
    {
        yield 'resolvable function' => [
            <<<'EOT'
// File: test.php
<?php function bar() {} bar();
EOT
            , [
            ]
        ];

        yield 'unresolvable function' => [
            <<<'EOT'
// File: test.php
<?php foo();
EOT
            ,[
                new NameWithByteOffset(
                    QualifiedName::fromString('foo'),
                    ByteOffset::fromInt(6),
                    NameWithByteOffset::TYPE_FUNCTION
                ),
            ]
        ];

        yield 'namespaced unresolveable function' => [
            <<<'EOT'
// File: test.php
<?php namespace Foobar; foo();
EOT
            ,[
                new NameWithByteOffset(
                    QualifiedName::fromString('Foobar\foo'),
                    ByteOffset::fromInt(24),
                    NameWithByteOffset::TYPE_FUNCTION
                ),
            ]
        ];

        yield 'resolveable namespaced function' => [
            <<<'EOT'
// File: test.php
<?php namespace Foobar; function foo() {} foo();
EOT
            ,[
            ]
        ];

        yield 'function imported in list' => [
            <<<'EOT'
// File: test.php
<?php use function Bar\{foo}; foo();
// File: Bar.php
<?php namespace Bar; function foo() {}
EOT
            , [
            ]
        ];
    }

    public function provideConstants(): Generator
    {
        yield 'global constant' => [
            <<<'EOT'
// File: test.php
<?php namespace Foobar; INF;
EOT
            ,[
            ]
        ];
    }
}
