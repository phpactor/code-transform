<?php

namespace Phpactor\CodeTransform\Adapter\WorseReflection;

use Phpactor\CodeTransform\Domain\Transformer;
use Phpactor\CodeTransform\Domain\SourceCode;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\SourceCode as WorseSourceCode;
use Phpactor\WorseReflection\Reflection\ReflectionClass;
use Microsoft\PhpParser\TextEdit;

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

        foreach ($classes as $class) {
            if (!$class instanceof ReflectionClass) {
                continue;
            }

            $pos = $class->memberListPosition()->end();
            if ($class->properties()->last()) {
                $pos = $class->properties()->last()->position()->end();
            }

            $missingMethods = $this->missingClassMethods($class);
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

                $methodStr[] = sprintf(
                    '    %s function %s',
                    (string) $missingMethod->visibility(),
                    (string) $missingMethod->name()
                );

                $paramStrs = [];
                foreach ($missingMethod->parameters() as $param) {
                    $paramStr = [];
                    if (false === $param->type()->isUnknown()) {
                        $paramStr[] = (string) $param->type();
                    }

                    $paramStr[] = '$' . $param->name();

                    if ($param->hasDefault()) {
                        $paramStr[] = '= ' . var_export($param->default(), true);
                    }

                    $paramStrs[] = implode(' ', $paramStr);
                }

                $methodStr[] = '(' . implode(', ', $paramStrs) . ')';

                if (false === $missingMethod->type()->isUnknown()) {
                    $methodStr[] = ': ' . (string) $missingMethod->type();
                }

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

        return $methods;
    }
}

