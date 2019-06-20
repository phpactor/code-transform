<?php

namespace Phpactor\CodeTransform\Tests\Adapter\WorseReflection\Refactor;

use Phpactor\CodeTransform\Tests\Adapter\WorseReflection\WorseTestCase;
use Phpactor\CodeTransform\Adapter\WorseReflection\Refactor\WorseGenerateMethod;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\CodeTransform\Domain\Exception\TransformException;
use Phpactor\CodeBuilder\Adapter\WorseReflection\WorseBuilderFactory;

class WorseGenerateMethodTest extends WorseTestCase
{
    /**
     * @dataProvider provideExtractMethod
     */
    public function testGenerateMethod(string $test, $name = null)
    {
        list($source, $expected, $offset) = $this->sourceExpectedAndOffset(__DIR__ . '/fixtures/' . $test);

        $transformed = $this->generateMethod($source, $offset, $name);

        $this->assertEquals(trim($expected), trim($transformed));
    }

    public function provideExtractMethod()
    {
        return [
            'string' => [
                'generateMethod1.test',
            ],
            'parameter' => [
                'generateMethod2.test',
            ],
            'typed parameter' => [
                'generateMethod3.test',
            ],
            'undeclared parameter' => [
                'generateMethod4.test',
            ],
            'expression' => [
                'generateMethod5.test',
            ],
            'public accessor in another class' => [
                'generateMethod6.test',
            ],
            'public accessor on interface' => [
                'generateMethod7.test',
            ],
            'public accessor on interface with namespace' => [
                'generateMethod8.test',
            ],
            'imports classes' => [
                'generateMethod9.test',
            ],
            'static private method' => [
                'generateMethod10.test',
            ],
            'static public method' => [
                'generateMethod11.test',
            ],
        ];
    }

    public function testGenerateOnNonClassInterfaceException()
    {
        $this->expectException(TransformException::class);
        $this->expectExceptionMessage('Can only generate methods on classes');
        $source = <<<'EOT'
<?php 
trait Hello
{
}

class Goodbye
{
    /**
     * @var Hello
     */
    private $hello;

    public function greet()
    {
        $this->hello->asd();
    }
}
EOT
        ;

        $this->generateMethod($source, 152, 'test_name');
    }

    private function generateMethod(string $source, int $start, $name)
    {
        $reflector = $this->reflectorFor($source);
        $factory = new WorseBuilderFactory($reflector);
        $generateMethod = new WorseGenerateMethod($reflector, $factory, $this->updater());
        return $generateMethod->generateMethod(SourceCode::fromString($source), $start, $name);
    }
}
