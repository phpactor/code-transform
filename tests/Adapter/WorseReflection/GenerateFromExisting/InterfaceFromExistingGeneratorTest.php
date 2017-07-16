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
            'Generates interface' => [
                'Music\Beat',
                'Music\BeatInterface',
                <<<'EOT'
<?php

namespace Music;

use Sound\Snare;

class Beat
{
    private $foobar;

    public function __construct(string $foobar)
    {
        $this->foobar = $foobar;
    }

    /**
     * This is some documentation.
     */
    public function play(Snare $snare = null, int $bar = "boo")
    {
        $snare->hit();
    }

    public function empty()
    {
    }

    private function something()
    {
    }

    protected function somethingElse()
    {
    }
}
EOT
                , <<<'EOT'
<?php

namespace Music;

use Sound\Snare;

interface BeatInterface
{
    /**
     * This is some documentation.
     */
    public function play(Snare $snare = null, int $bar = 'boo');

    public function empty();
}
EOT

            ]
        ];
    }
}
