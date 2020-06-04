<?php

namespace Phpactor\CodeTransform\Tests\Adapter\TolerantParser\Refactor;

use Phpactor\CodeTransform\Domain\Refactor\ImportClass\NameImport;
use Phpactor\CodeTransform\Tests\Adapter\TolerantParser\TolerantTestCase;
use Phpactor\CodeTransform\Adapter\TolerantParser\Refactor\TolerantImportName;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\NameAlreadyImportedException;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\AliasAlreadyUsedException;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\ClassIsCurrentClassException;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\NameAlreadyInNamespaceException;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\TextEdits;

class TolerantImportNameTest extends TolerantTestCase
{
    /**
     * @dataProvider provideImportClass
     */
    public function testImportClass(string $test, string $name, string $alias = null)
    {
        list($expected, $transformed) = $this->importNameFromTestFile('class', $test, $name, $alias);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideImportClass()
    {
        yield 'with existing class imports' => [
            'importClass1.test',
            'Barfoo\Foobar',
        ];

        yield 'with namespace' => [
            'importClass2.test',
            'Barfoo\Foobar',
        ];

        yield 'with no namespace declaration or use statements' => [
            'importClass3.test',
            'Barfoo\Foobar',
        ];

        yield 'with alias' => [
            'importClass4.test',
            'Barfoo\Foobar',
            'Barfoo',
        ];

        yield 'with static alias' => [
            'importClass5.test',
            'Barfoo\Foobar',
            'Barfoo',
        ];

        yield 'with multiple aliases' => [
            'importClass6.test',
            'Barfoo\Foobar',
            'Barfoo',
        ];

        yield 'with alias and existing name' => [
            'importClass7.test',
            'Barfoo\Foobar',
            'Barfoo',
        ];
    }

    public function testThrowsExceptionIfClassAlreadyImported()
    {
        $this->expectException(NameAlreadyImportedException::class);
        $this->expectExceptionMessage('Class "DateTime" is already imported');
        $this->importNameFromTestFile('class', 'importClass1.test', 'DateTime');
    }

    public function testThrowsExceptionIfImportedClassIsTheCurrentClass1()
    {
        $this->expectException(ClassIsCurrentClassException::class);
        $this->expectExceptionMessage('Class "Foobar" is the current class');
        $this->importName('<?php class Foobar {}', 14, NameImport::forClass('Foobar'));
    }

    public function testThrowsExceptionIfAliasAlredayUsed()
    {
        $this->expectException(AliasAlreadyUsedException::class);
        $this->expectExceptionMessage('Class alias "DateTime" is already used');
        $this->importNameFromTestFile('class', 'importClass1.test', 'Foobar', 'DateTime');
    }

    public function testThrowsExceptionIfImportedClassHasSameNameAsCurrentClassName()
    {
        $this->expectException(NameAlreadyImportedException::class);
        $this->importName('<?php namespace Barfoo; class Foobar extends Foobar', 47, NameImport::forClass('BazBar\Foobar'));
    }

    public function testThrowsExceptionIfImportedClassHasSameNameAsCurrentInterfaceName()
    {
        $this->expectException(NameAlreadyImportedException::class);
        $this->importName('<?php namespace Barfoo; interface Foobar extends Foobar', 50, NameImport::forClass('BazBar\Foobar'));
    }

    public function testThrowsExceptionIfImportedClassInSameNamespace()
    {
        $this->expectException(NameAlreadyInNamespaceException::class);
        $this->expectExceptionMessage('Class "Barfoo" is in the same namespace as current class');
        $source = <<<'EOT'
<?php

namespace Barfoo;
class Foobar {
    public function use(Barfoo $barfoo) {}
    }
}
EOT
        ;
        $this->importName($source, 64, NameImport::forClass('Barfoo\Barfoo'));
    }

    /**
     * @dataProvider provideImportFunction
     */
    public function testImportFunction(string $test, string $name, string $alias = null)
    {
        list($expected, $transformed) = $this->importNameFromTestFile('function', $test, $name, $alias);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideImportFunction()
    {
        yield 'import function' => [
            'importFunction1.test',
            'Acme\foobar',
        ];
    }

    public function testThrowsExceptionIfFunctionAlreadyImported(): void
    {
        $this->expectExceptionMessage('Function "foobar" is already imported');
        $this->importNameFromTestFile('function', 'importFunction2.test', 'Acme\foobar');
    }

    private function importNameFromTestFile(string $type, string $test, string $name, string $alias = null)
    {
        list($source, $expected, $offset) = $this->sourceExpectedAndOffset(__DIR__ . '/fixtures/' . $test);
        $edits = TextEdits::none();

        if ($type === 'class') {
            $edits = $this->importName($source, $offset, NameImport::forClass($name, $alias));
        }

        if ($type === 'function') {
            $edits = $this->importName($source, $offset, NameImport::forFunction($name, $alias));
        }

        return [$expected, $edits->apply($source)];
    }

    private function importName($source, int $offset, NameImport $nameImport): TextEdits
    {
        $importClass = (new TolerantImportName($this->updater(), $this->parser()));
        return $importClass->importName(SourceCode::fromString($source), ByteOffset::fromInt($offset), $nameImport);
    }
}
