<?php

declare(strict_types=1);

namespace Phpactor\CodeTransform\Tests\Adapter\TolerantParser\Refactor;

use Generator;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\AliasAlreadyUsedException;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\ClassIsCurrentClassException;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\NameAlreadyImportedException;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\NameAlreadyInNamespaceException;
use Phpactor\CodeTransform\Domain\Refactor\ImportClass\NameImport;
use Phpactor\CodeTransform\Tests\Adapter\TolerantParser\TolerantTestCase;
use Phpactor\TextDocument\TextEdits;

abstract class AbstractTolerantImportNameTest extends TolerantTestCase
{
    /**
     * @dataProvider provideImportClass
     */
    public function testImportClass(string $test, string $name, string $alias = null): void
    {
        list($expected, $transformed) = $this->importNameFromTestFile('class', $test, $name, $alias);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    abstract public function provideImportClass(): Generator;

    public function testThrowsExceptionIfClassAlreadyImported(): void
    {
        $this->expectException(NameAlreadyImportedException::class);
        $this->expectExceptionMessage('Class "DateTime" is already imported');
        $this->importNameFromTestFile('class', 'importClass1.test', 'DateTime');
    }

    public function testThrowsExceptionIfImportedClassIsTheCurrentClass1(): void
    {
        $this->expectException(ClassIsCurrentClassException::class);
        $this->expectExceptionMessage('Class "Foobar" is the current class');
        $this->importName('<?php class Foobar {}', 14, NameImport::forClass('Foobar'));
    }

    public function testThrowsExceptionIfAliasAlreadyUsed(): void
    {
        $this->expectException(AliasAlreadyUsedException::class);
        $this->expectExceptionMessage('Class alias "DateTime" is already used');
        $this->importNameFromTestFile('class', 'importClass1.test', 'Foobar', 'DateTime');
    }

    public function testThrowsExceptionIfImportedClassHasSameNameAsCurrentClassName(): void
    {
        $this->expectException(NameAlreadyImportedException::class);
        $this->importName(
            '<?php namespace Barfoo; class Foobar extends Foobar',
            47,
            NameImport::forClass('BazBar\Foobar')
        );
    }

    public function testThrowsExceptionIfImportedClassHasSameNameAsCurrentInterfaceName(): void
    {
        $this->expectException(NameAlreadyImportedException::class);
        $this->importName(
            '<?php namespace Barfoo; interface Foobar extends Foobar',
            50,
            NameImport::forClass('BazBar\Foobar')
        );
    }

    public function testThrowsExceptionIfImportedClassInSameNamespace(): void
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
            EOT;
        $this->importName($source, 64, NameImport::forClass('Barfoo\Barfoo'));
    }

    /**
     * @dataProvider provideImportFunction
     */
    public function testImportFunction(string $test, string $name, string $alias = null): void
    {
        list($expected, $transformed) = $this->importNameFromTestFile('function', $test, $name, $alias);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    abstract public function provideImportFunction(): Generator;

    abstract protected function importName($source, int $offset, NameImport $nameImport): TextEdits;

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
}
