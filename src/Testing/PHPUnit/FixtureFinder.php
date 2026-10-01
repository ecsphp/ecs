<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Testing\PHPUnit;

use Iterator;
use ECSPrefix202610\Symfony\Component\Finder\Finder;
/**
 * @see \Symplify\EasyCodingStandard\Tests\Testing\PHPUnit\FixtureFinderTest
 */
final class FixtureFinder
{
    /**
     * @api used in tests
     * @return Iterator<array<string>>
     */
    public static function yieldDataProviderFiles(string $directory, string $suffix = '*.php.inc'): Iterator
    {
        $finder = Finder::create()->in($directory)->files()->name($suffix);
        $fileInfos = iterator_to_array($finder);
        $filePaths = array_keys($fileInfos);
        foreach ($filePaths as $filePath) {
            yield [$filePath];
        }
    }
}
