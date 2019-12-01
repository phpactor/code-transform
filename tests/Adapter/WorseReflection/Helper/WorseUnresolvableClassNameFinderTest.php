<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Helper;

use PharIo\Manifest\Manifest;
use Phpactor\CodeTransform\Adapter\WorseReflection\Helper\WorseUnresolvableClassNameFinder;
use Phpactor\CodeTransform\Domain\NameWithByteOffset;
use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;
use Phpactor\Name\QualifiedName;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextDocumentBuilder;

class WorseUnresolvableClassNameFinderTest extends WorseTestCase
{
    /**
     * @dataProvider provideReturnsUnresolableClass
     */
    public function testReturnsUnresolableClass(string $manifest, array $expectedNames)
    {
        $this->workspace()->reset();
        $this->workspace()->loadManifest($manifest);
        $source = $this->workspace()->getContents('test.php');

        $finder = new WorseUnresolvableClassNameFinder(
            $this->reflectorFor($source)
        );
        $this->assertEquals($expectedNames, $finder->find(
            TextDocumentBuilder::create($source)->build()
        ));
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

        yield 'resolvable class' => [
            <<<'EOT'
// File: test.php
<?php class Foo() {} new Foo();
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
    }
}
