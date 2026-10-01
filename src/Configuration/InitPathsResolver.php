<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Configuration;

use ECSPrefix202610\Symfony\Component\Finder\Finder;
use ECSPrefix202610\Symfony\Component\Finder\SplFileInfo;
final class InitPathsResolver
{
    /**
     * @return string[]
     */
    public function resolve(string $projectDirectory): array
    {
        $rootDirectoryFinder = Finder::create()->directories()->depth(0)->notPath('#(vendor|var|stubs|temp|templates|tmp|e2e|bin|build|database|storage|migrations)#')->in($projectDirectory)->sortByName();
        /** @var SplFileInfo[] $rootDirectoryFileInfos */
        $rootDirectoryFileInfos = iterator_to_array($rootDirectoryFinder);
        $projectDirectories = [];
        foreach ($rootDirectoryFileInfos as $rootDirectoryFileInfo) {
            if (!$this->hasDirectoryFileInfoPhpFiles($rootDirectoryFileInfo)) {
                continue;
            }
            $projectDirectories[] = $rootDirectoryFileInfo->getRelativePathname();
        }
        return $projectDirectories;
    }
    private function hasDirectoryFileInfoPhpFiles(SplFileInfo $rootDirectoryFileInfo): bool
    {
        // is directory with PHP files?
        $phpFilesFinder = Finder::create()->files()->in($rootDirectoryFileInfo->getPathname())->name('*.php');
        return count($phpFilesFinder) !== 0;
    }
}
