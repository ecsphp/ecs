<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Configuration;

use Symplify\EasyCodingStandard\Console\Output\OutputFormatterCollector;
use Symplify\EasyCodingStandard\DependencyInjection\SimpleParameterProvider;
use Symplify\EasyCodingStandard\Exception\Configuration\SourceNotFoundException;
use Symplify\EasyCodingStandard\ValueObject\Configuration;
use Symplify\EasyCodingStandard\ValueObject\Option;
final class ConfigurationFactory
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Console\Output\OutputFormatterCollector
     */
    private $outputFormatterCollector;
    public function __construct(OutputFormatterCollector $outputFormatterCollector)
    {
        $this->outputFormatterCollector = $outputFormatterCollector;
    }
    /**
     * Needs to run in the start of the life cycle, since the rest of workflow uses it.
     *
     * @param string[] $paths
     */
    public function create(array $paths, bool $isFixer, bool $shouldClearCache, bool $noProgressBar, bool $noErrorTable, bool $noDiffs, string $outputFormat, ?string $config, string $parallelPort, string $parallelIdentifier, ?string $memoryLimit, bool $debug, bool $isDirty): Configuration
    {
        $paths = $this->resolvePaths($paths);
        $showProgressBar = $this->canShowProgressBar($debug, $outputFormat, $noProgressBar);
        $showErrorTable = !$noErrorTable;
        $showDiffs = !$noDiffs;
        $isParallel = SimpleParameterProvider::getBoolParameter(Option::PARALLEL);
        return new Configuration($isFixer, $shouldClearCache, $showProgressBar, $showErrorTable, $paths, $outputFormat, $isParallel, $config, $parallelPort, $parallelIdentifier, $memoryLimit, $showDiffs, $isDirty);
    }
    private function canShowProgressBar(bool $debug, string $outputFormat, bool $noProgressBar): bool
    {
        // --debug option shows more
        if ($debug) {
            return \false;
        }
        $outputFormatter = $this->outputFormatterCollector->getByName($outputFormat);
        if (!$outputFormatter->hasSupportForProgressBars()) {
            return \false;
        }
        return !$noProgressBar;
    }
    /**
     * @param string[] $paths
     */
    private function ensurePathsExists(array $paths): void
    {
        foreach ($paths as $path) {
            if (file_exists($path)) {
                continue;
            }
            throw new SourceNotFoundException(sprintf('Source "%s" does not exist.', $path));
        }
    }
    /**
     * @param string[] $paths
     * @return string[]
     */
    private function resolvePaths(array $paths): array
    {
        if ($paths === []) {
            // if not paths are provided from CLI, use the config ones
            $paths = SimpleParameterProvider::getArrayParameter(Option::PATHS);
        }
        $this->ensurePathsExists($paths);
        return $this->normalizePaths($paths);
    }
    /**
     * @param string[] $paths
     * @return string[]
     */
    private function normalizePaths(array $paths): array
    {
        foreach ($paths as $key => $path) {
            $paths[$key] = rtrim($path, \DIRECTORY_SEPARATOR);
        }
        return $paths;
    }
}
