<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\GenerateFromExisting;

use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;
use Phpactor\CodeTransform\Domain\ClassName;
use Phpactor\CodeTransform\Adapter\WorseReflection\GenerateFromExisting\InterfaceFromExistingGenerator;

class InterfaceFromExistingGeneratorTest extends WorseTestCase
{
    /**
     * @testdox Generate interface
     * @dataProvider provideGenerateInterface
     */
    public function testGenerateInterface(string $className, string $targetName, string $source, string $expected)
    {
        $reflector = $this->reflectorFor($source);
        $generator = new InterfaceFromExistingGenerator($reflector, $this->renderer());
        $source = $generator->generateFromExisting(ClassName::fromString($className), ClassName::fromString($targetName));
        $this->assertEquals($expected, (string) $source);
    }

    public function provideGenerateInterface()
    {
        return [
            [
                'Music\Beat',
                'Music\BeatInterface',
                <<<'EOT'
<?php

namespace Music;

use Sound\Snare;

class Beat
{
    public function play(Snare $snare)
    {
        $snare->hit();
    }
}
EOT
                , <<<'EOT'
<?php

namespace Music;

use Sound\Snare;

interface Beat
{
    public function play(Snare $snare);
}
EOT

            ]
        ];
    }
}
