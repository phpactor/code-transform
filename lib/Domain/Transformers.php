<?php

namespace Phpactor\CodeTransform\Domain;

final class Transformers implements \IteratorAggregate
{
    private $transformers = [];

    public function __construct(array $transformers)
    {
        foreach ($transformers as $name => $transformer) {
            $this->add($name, $transformer);
        }
    }

    public static function fromArray(array $transformers)
    {
        return new self($transformers);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->transformers);
    }

    public function applyTo(SourceCode $code)
    {
        foreach ($this as $transformer) {
            $code = $transformer->transform($code);
        }

        return $code;
    }

    public function get(string $name)
    {
        if (!isset($this->transformers[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Transformer "%s" not known, known transformers: "%s"',
                $name, implode('", "', array_keys($this->transformers))
            ));
        }

        return $this->transformers[$name];
    }

    public function in(array $transformerNames)
    {
        $transformers = [];

        foreach ($transformerNames as $transformerName) {
            $transformers[] = $this->get($transformerName);
        }

        return new self($transformers);
    }

    private function add(string $name, Transformer $transformer)
    {
        $this->transformers[$name] = $transformer;
    }
}
