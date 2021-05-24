<?php

namespace Phpactor\CodeTransform\Tests\Benchmark\Adapter\WorseReflection\Util;

use Generator;
use Microsoft\PhpParser\Parser;
use Phpactor\CodeTransform\Adapter\WorseReflection\Helper\WorseUnresolvableClassNameFinder;
use Phpactor\TextDocument\TextDocumentBuilder;
use Phpactor\WorseReflection\ReflectorBuilder;

final class WorseUnresolvableClassNameFinderBench
{
    /**
     * @var WorseUnresolvableClassNameFinder
     */
    private $finder;

    public function __construct()
    {
        $this->finder = new WorseUnresolvableClassNameFinder(
            ReflectorBuilder::create()->build(),
            new Parser()
        );
    }

    /**
     * @ParamProviders("provideFind")
     */
    public function benchFind(array $params): void
    {
        $this->finder->find(TextDocumentBuilder::create($params['text'])->build());
    }

    public function provideFind(): Generator
    {
        foreach (['class', 'func'] as $type) {
            foreach (glob(__DIR__ . '/' . $type . '/*') as $path) {
                yield basename(dirname($path)) .'/' . basename($path) => [
                    'text' => file_get_contents($path)
                ];
            }
        }
    }
}
