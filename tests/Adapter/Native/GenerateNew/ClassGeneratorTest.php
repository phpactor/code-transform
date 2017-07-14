<?php

namespace Phpactor\CodeTransform\Tests\Adapter\Native\GenerateNew;

use Phpactor\CodeTransform\Tests\Adapter\AdapterTestCase;
use Phpactor\CodeTransform\Domain\ClassName;
use Phpactor\CodeTransform\Adapter\Native\GenerateNew\ClassGenerator;

class ClassGeneratorTest extends AdapterTestCase
{
    /**
     * It should generate a class
     */
    public function testGenerateClass()
    {
        $className = ClassName::fromString('Acme\\Blog\\Post');
        $generator = new ClassGenerator($this->renderer());
        $code = $generator->generate($className);
        var_dump($code);die();;
    }
}
