<?php

namespace Phpactor\CodeTransform\Domain\Helper;

use FileSystemIterator;

class TemplatePathsResolver
{
    /**
     * @var string
     */
    private $phpVersion;

    public function __construct(string $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }

    public function resolve(iterable $paths): iterable
    {
        $resolvedPaths = [];

        foreach ($paths as $path) {
            if (!file_exists($path)) {
                continue;
            }

            $phpDirectoriesIterator = new FilterPhpVersionDirectoryIterator(
                new FileSystemIterator($path),
                (int) $this->phpVersion
            );
            $phpDirectories = array_keys(iterator_to_array($phpDirectoriesIterator));
            rsort($phpDirectories, SORT_NATURAL);

            $resolvedPaths = array_merge($resolvedPaths, $phpDirectories);
            $resolvedPaths[] = $path;
        }

        return $resolvedPaths;
    }
}
