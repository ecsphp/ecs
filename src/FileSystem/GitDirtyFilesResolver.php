<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\FileSystem;

final class GitDirtyFilesResolver
{
    /**
     * Keep only files with uncommitted changes (modified, staged or untracked).
     *
     * @param string[] $filePaths
     * @return string[]
     */
    public function filterDirty(array $filePaths): array
    {
        $dirtyFilePaths = $this->resolve();
        if ($dirtyFilePaths === []) {
            return [];
        }
        return array_values(array_filter($filePaths, static function (string $filePath) use ($dirtyFilePaths): bool {
            return in_array(realpath($filePath) ?: $filePath, $dirtyFilePaths, \true);
        }));
    }
    /**
     * Parse "git status --porcelain" lines into relative file paths.
     *
     * @param string[] $statusLines
     * @return string[]
     */
    public function resolveRelativePaths(array $statusLines): array
    {
        $relativePaths = [];
        foreach ($statusLines as $statusLine) {
            if ($statusLine === '') {
                continue;
            }
            // porcelain line is "XY <path>"; a rename shows "R  <old> -> <new>"
            $path = (string) substr($statusLine, 3);
            $arrowPosition = strpos($path, ' -> ');
            if ($arrowPosition !== \false) {
                $path = (string) substr($path, $arrowPosition + 4);
            }
            $relativePaths[] = trim($path, '"');
        }
        return $relativePaths;
    }
    /**
     * @return string[] absolute paths of dirty files
     */
    private function resolve(): array
    {
        exec('git rev-parse --show-toplevel 2>/dev/null', $rootLines, $rootExitCode);
        if ($rootExitCode !== 0 || $rootLines === []) {
            return [];
        }
        $gitRoot = trim((string) $rootLines[0]);
        exec('git status --porcelain --untracked-files=all 2>/dev/null', $statusLines, $statusExitCode);
        if ($statusExitCode !== 0) {
            return [];
        }
        $absoluteFilePaths = [];
        foreach ($this->resolveRelativePaths($statusLines) as $relativePath) {
            $absoluteFilePath = realpath($gitRoot . \DIRECTORY_SEPARATOR . $relativePath);
            if ($absoluteFilePath === \false) {
                continue;
            }
            $absoluteFilePaths[] = $absoluteFilePath;
        }
        return $absoluteFilePaths;
    }
}
