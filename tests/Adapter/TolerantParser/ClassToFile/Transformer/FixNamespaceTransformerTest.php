<?php

namespace Phpactor\CodeTransform\Tests\Adapter\TolerantParser\ClassToFile\Transformer;

use Phpactor\ClassFileConverter\Adapter\Composer\ComposerFileToClass;
use Phpactor\ClassFileConverter\Adapter\Simple\SimpleFileToClass;
use Phpactor\ClassFileConverter\Domain\ClassToFile;
use Phpactor\ClassFileConverter\Domain\FileToClass;
use Phpactor\CodeTransform\Adapter\TolerantParser\ClassToFile\Transformer\FixNameTransformer;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Tests\Adapter\AdapterTestCase;

class FixNamespaceTransformerTest extends AdapterTestCase
{
    /**
     * @dataProvider provideFixClassName
     */
    public function testFixClassName(string $filePath, string $test)
    {
        $workspace = $this->workspace();
        $workspace->reset();
        $workspace->loadManifest(file_get_contents(__DIR__ . '/fixtures/' . $test));
        $source = $workspace->getContents($filePath);
        $expected = $workspace->getContents('expected');

        $fileToClass = new SimpleFileToClass($this->workspace()->path('/'));
        $transformer = new FixNameTransformer($fileToClass);
        $transformed = $transformer->transform(SourceCode::fromStringAndPath($source, $this->workspace()->path($filePath)));

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideFixClassName()
    {
        yield 'no op' => [ 'FileOne.php', 'fixNamespace0.test' ];
        yield 'fix file with missing namespace' => [ 'PathTo/FileOne.php', 'fixNamespace1.test' ];
    }
}
