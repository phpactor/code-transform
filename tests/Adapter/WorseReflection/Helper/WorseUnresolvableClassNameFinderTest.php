<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Helper;

use PHPUnit\Framework\TestCase;
use Phpactor\CodeTransform\Adapter\WorseReflection\Helper\WorseUnresolvableClassNameFinder;
use Phpactor\CodeTransform\Tests\Adapter\AdapterTestCase;
use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;
use Phpactor\Name\QualifiedName;
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
        $this->assertEquals($expectedNames, iterator_to_array($finder->find(
            TextDocumentBuilder::create($source)->build()
        )));
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
                QualifiedName::fromString('NotFound')
            ]
        ];

        yield 'namespaced unresolvable class' => [
            '<?php namespace Foo; new NotFound();',
            [
                QualifiedName::fromString('Foo\\NotFound')
            ]
        ];

        yield 'multiple unresolvable classes' => [
            <<<'EOT'
<?php 
new Bar\NotFound();
new NotFound36();
EOT
,
            [
                QualifiedName::fromString('Bar\\NotFound'),
                QualifiedName::fromString('NotFound36')
            ]
        ];
    }
}
