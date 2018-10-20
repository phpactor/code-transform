<?php

namespace Phpactor\CodeTransform\Tests\Adapter\TolerantParser\Refactor;

use Phpactor\CodeTransform\Tests\Adapter\TolerantParser\TolerantTestCase;
use Phpactor\CodeTransform\Adapter\TolerantParser\Refactor\TolerantImportClass;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\ClassAlreadyImportedException;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\AliasAlreadyUsedException;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\ClassIsCurrentClassException;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\ClassAlreadyInNamespaceException;

class TolerantImportClassTest extends TolerantTestCase
{
    /**
     * @dataProvider provideImportClass
     */
    public function testImportClass(string $test, string $name, string $alias = null)
    {
        list($expected, $transformed) = $this->importClassFromTestFile($test, $name, $alias);

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
    }

    public function testThrowsExceptionIfClassAlreadyImported()
    {
        $this->expectException(ClassAlreadyImportedException::class);
        $this->expectExceptionMessage('Class "DateTime" is already imported');
        $this->importClassFromTestFile('importClass1.test', 'DateTime');
    }

    public function testThrowsExceptionIfImportedClassIsTheCurrentClass1()
    {
        $this->expectException(ClassIsCurrentClassException::class);
        $this->expectExceptionMessage('Class "Foobar" is the current class');
        $this->importClass('<?php class Foobar {}', 16, 'Foobar');
    }

    public function testThrowsExceptionIfImportedClassIsTheCurrentClass2()
    {
        $this->expectException(ClassIsCurrentClassException::class);
        $this->expectExceptionMessage('Class "Foobar" is the current class');
        $this->importClass('<?php class Foobar { function hello(Foobar $foobar) {}}', 21, 'Foobar');
    }

    public function testThrowsExceptionIfAliasAlredayUsed()
    {
        $this->expectException(AliasAlreadyUsedException::class);
        $this->expectExceptionMessage('Alias "DateTime" is already used');
        $this->importClassFromTestFile('importClass1.test', 'Foobar', 'DateTime');
        $this->importClass('<?php namespace Barfoo; class Foobar { function hello(Foobar $foobar) {}}', 21, 'Foobar');
    }

    public function testThrowsExceptionIfImportedClassInSameNamespace()
    {
        $this->expectException(ClassAlreadyInNamespaceException::class);
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
        $this->importClass($source, 64, 'Barfoo\Barfoo');
    }

    private function importClassFromTestFile(string $test, string $name, string $alias = null)
    {
        list($source, $expected, $offset) = $this->sourceExpectedAndOffset(__DIR__ . '/fixtures/' . $test);
        
        $transformed = $this->importClass($source, $offset, $name, $alias);
        return [$expected, $transformed];
    }

    private function importClass($source, int $offset, string $name, string $alias = null)
    {
        $renameVariable = new TolerantImportClass($this->updater(), $this->parser());
        $transformed = $renameVariable->importClass(SourceCode::fromString($source), $offset, $name, $alias);

        return $transformed;
    }
}
