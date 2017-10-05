<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection;

use Phpactor\CodeTransform\Tests\Adapter\AdapterTestCase;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\SourceCodeLocator\StringSourceLocator;
use Phpactor\WorseReflection\Reflector;

class WorseTestCase extends AdapterTestCase
{
    public function reflectorFor(string $source)
    {
        return Reflector::create(new StringSourceLocator(SourceCode::fromString((string) $source)));
    }

    public function splitInitialAndExpectedSource($sourceFile)
    {
        $files = [
            0 => [],
            1 => [],
        ];
        $index = 0;
        $contents = explode(PHP_EOL, file_get_contents($sourceFile));
        foreach ($contents as $line) {
            if ($line === '========') {
                $index++;
                continue;
            }

            $files[$index][] = $line;
        }

        return [
            implode(PHP_EOL, $files[0]),
            implode(PHP_EOL, $files[1]),
        ];
    }
}
