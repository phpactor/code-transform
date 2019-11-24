<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Helper;

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
    public function testReturnsUnresolableClass(string $source, array $expectedNames)
    {
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
            '',
            []
        ];

        yield 'resolvable class' => [
            '<?php class Foo() {} new Foo();',
            [
            ]
        ];

        yield 'unresolvable class' => [
            '<?php new NotFound();',
            [
                new NameWithByteOffset(
                    QualifiedName::fromString('NotFound'),
                    ByteOffset::fromInt(10)
                ),
            ]
        ];

        yield 'namespaced unresolvable class' => [
            '<?php namespace Foo; new NotFound();',
            [
                new NameWithByteOffset(
                    QualifiedName::fromString('Foo\\NotFound'),
                    ByteOffset::fromInt(25)
                ),
            ]
        ];

        yield 'multiple unresolvable classes' => [
            <<<'EOT'
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
    }
}
