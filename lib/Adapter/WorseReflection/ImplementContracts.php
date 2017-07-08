<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection;

use Phpactor\CodeTransform\Domain\Transformer;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\SourceCode as WorseSourceCode;
use Phpactor\WorseReflection\Reflection\ReflectionClass;
use Phpactor\CodeTransform\Adapter\TolerantParser\TextEdit;

class ImplementContracts implements Transformer
{
    /**
     * @var Reflector
     */
    private $reflector;

    public function __construct(Reflector $reflector, Editor $editor = null)
    {
        $this->reflector = $reflector;
    }

    public function transform(SourceCode $source): SourceCode
    {
        $classes = $this->reflector->reflectClassesIn(WorseSourceCode::fromString((string) $source));
        $edits = [];

        foreach ($classes->concrete() as $class) {
            $pos = $class->memberListPosition()->end();

            $missingMethods = $this->missingClassMethods($class);

            if (0 === count($missingMethods)) {
                continue;
            }

            $last = $class->methods()->belongingTo($class->name())->last();
            if ($last) {
                $pos = $last->position()->end() + 1;
                $edits[] = new TextEdit($pos, 0, PHP_EOL);
            }

            $index = 0;
            foreach ($missingMethods as $missingMethod) {
                $methodStr = [];

                if (!empty($missingMethod->docblock()->formatted())) {
                    $methodStr[] = <<<'EOT'
    /**
     * {@inheritDoc}
     */

EOT
                    ;
                }

                $header = trim((string) $missingMethod->header());

                $header = preg_replace('{^abstract }', '', $header);

                $methodStr[] = '    ' . $header;

                $methodStr[] = PHP_EOL;
                $methodStr[] = '    {';
                $methodStr[] = PHP_EOL;
                $methodStr[] = '    }';
                $methodStr[] = PHP_EOL;

                if (++$index < count($missingMethods)) {
                    $methodStr[] = PHP_EOL;
                }

                $edits[] = new TextEdit($pos, 0, implode('', $methodStr));
            }
        }

        $source = SourceCode::fromString(TextEdit::applyEdits($edits, (string) $source));

        return $source;
    }

    private function missingClassMethods(ReflectionClass $class): array
    {
        $methods = [];
        foreach ($class->interfaces() as $interface) {
            foreach ($interface->methods() as $method) {
                if (!$class->methods()->has($method->name())) {
                    $methods[] = $method;
                }
            }
        }

        foreach ($class->methods() as $method) {
            if ($method->class()->name() != $class->name()) {
                $methods[] = $method;
            }
        }

        return $methods;
    }
}

