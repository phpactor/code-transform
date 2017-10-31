<?php

namespace Phpactor\CodeTransform\Domain;

abstract class AbstractCollection implements \IteratorAggregate
{
    private $elements = [];

    public function __construct(array $elements)
    {
        foreach ($elements as $name => $element) {
            $type = $this->type();
            if (false === $element instanceof $type) {
                throw new \InvalidArgumentException(sprintf(
                    'Collection element must be instanceof "%s"',
                    $type
                ));
            }
            $this->elements[$name] = $element;
        }
    }

    public static function fromArray(array $elements)
    {
        return new static($elements);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->elements);
    }

    public function names()
    {
        return array_keys($this->elements);
    }

    public function get(string $name)
    {
        if (!isset($this->elements[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Generator "%s" not known, known elements: "%s"',
                $name,
                implode('", "', array_keys($this->elements))
            ));
        }

        return $this->elements[$name];
    }

    abstract protected function type(): string;
}
