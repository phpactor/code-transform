<?php

namespace Phpactor\CodeTransform\Domain;

final class Generators implements \IteratorAggregate
{
    private $generators = [];

    public function __construct(array $generators)
    {
        foreach ($generators as $name => $generator) {
            $this->add($name, $generator);
        }
    }

    public static function fromArray(array $generators)
    {
        return new self($generators);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->generators);
    }

    public function names()
    {
        return array_keys($this->generators);
    }

    public function get(string $name)
    {
        if (!isset($this->generators[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Generator "%s" not known, known generators: "%s"',
                $name, implode('", "', array_keys($this->generators))
            ));
        }

        return $this->generators[$name];
    }

    private function add(string $name, Generator $generator)
    {
        $this->generators[$name] = $generator;
    }
}
