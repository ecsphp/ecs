<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Application;

use ParseError;
use Symplify\EasyCodingStandard\Caching\ChangedFilesDetector;
use Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle;
use Symplify\EasyCodingStandard\DependencyInjection\SimpleParameterProvider;
use Symplify\EasyCodingStandard\Exception\ShouldNotHappenException;
use Symplify\EasyCodingStandard\FileSystem\FileFilter;
use Symplify\EasyCodingStandard\FileSystem\GitDirtyFilesResolver;
use Symplify\EasyCodingStandard\FileSystem\StaticRelativeFilePathHelper;
use Symplify\EasyCodingStandard\Finder\SourceFinder;
use Symplify\EasyCodingStandard\Parallel\Application\ParallelFileProcessor;
use Symplify\EasyCodingStandard\Parallel\CpuCoreCountProvider;
use Symplify\EasyCodingStandard\Parallel\ScheduleFactory;
use Symplify\EasyCodingStandard\Parallel\ValueObject\Bridge;
use Symplify\EasyCodingStandard\SniffRunner\ValueObject\Error\CodingStandardError;
use Symplify\EasyCodingStandard\Utils\ParametersMerger;
use Symplify\EasyCodingStandard\ValueObject\Configuration;
use Symplify\EasyCodingStandard\ValueObject\Error\FileDiff;
use Symplify\EasyCodingStandard\ValueObject\Error\SystemError;
use Symplify\EasyCodingStandard\ValueObject\Option;
final class EasyCodingStandardApplication
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle
     */
    private $easyCodingStandardStyle;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Finder\SourceFinder
     */
    private $sourceFinder;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Caching\ChangedFilesDetector
     */
    private $changedFilesDetector;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\FileSystem\FileFilter
     */
    private $fileFilter;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\FileSystem\GitDirtyFilesResolver
     */
    private $gitDirtyFilesResolver;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Application\SingleFileProcessor
     */
    private $singleFileProcessor;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Parallel\ScheduleFactory
     */
    private $scheduleFactory;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Parallel\Application\ParallelFileProcessor
     */
    private $parallelFileProcessor;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Parallel\CpuCoreCountProvider
     */
    private $cpuCoreCountProvider;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Utils\ParametersMerger
     */
    private $parametersMerger;
    /**
     * @var string
     */
    private const ARGV = 'argv';
    public function __construct(EasyCodingStandardStyle $easyCodingStandardStyle, SourceFinder $sourceFinder, ChangedFilesDetector $changedFilesDetector, FileFilter $fileFilter, GitDirtyFilesResolver $gitDirtyFilesResolver, \Symplify\EasyCodingStandard\Application\SingleFileProcessor $singleFileProcessor, ScheduleFactory $scheduleFactory, ParallelFileProcessor $parallelFileProcessor, CpuCoreCountProvider $cpuCoreCountProvider, ParametersMerger $parametersMerger)
    {
        $this->easyCodingStandardStyle = $easyCodingStandardStyle;
        $this->sourceFinder = $sourceFinder;
        $this->changedFilesDetector = $changedFilesDetector;
        $this->fileFilter = $fileFilter;
        $this->gitDirtyFilesResolver = $gitDirtyFilesResolver;
        $this->singleFileProcessor = $singleFileProcessor;
        $this->scheduleFactory = $scheduleFactory;
        $this->parallelFileProcessor = $parallelFileProcessor;
        $this->cpuCoreCountProvider = $cpuCoreCountProvider;
        $this->parametersMerger = $parametersMerger;
    }
    /**
     * @return array{coding_standard_errors?: CodingStandardError[], file_diffs?: FileDiff[], system_errors?: SystemError[]|string[], system_errors_count?: int, files_count?: int}
     */
    public function run(Configuration $configuration): array
    {
        // 1. find files in sources
        $filePaths = $this->sourceFinder->find($configuration->getSources());
        // keep only files with uncommitted git changes
        if ($configuration->isDirty()) {
            $filePaths = $this->gitDirtyFilesResolver->filterDirty($filePaths);
        }
        // 2. clear cache
        if ($configuration->shouldClearCache()) {
            $this->changedFilesDetector->clearCache();
        } else {
            $filePaths = $this->fileFilter->filterOnlyChangedFiles($filePaths);
        }
        // no files found
        $filesCount = count($filePaths);
        if ($filesCount === 0) {
            return [Bridge::FILES_COUNT => 0];
        }
        if ($configuration->isParallel()) {
            $schedule = $this->scheduleFactory->create($this->cpuCoreCountProvider->provide(), SimpleParameterProvider::getIntParameter(Option::PARALLEL_JOB_SIZE), SimpleParameterProvider::getIntParameter(Option::PARALLEL_MAX_NUMBER_OF_PROCESSES), $filePaths);
            // for progress bar
            $isProgressBarStarted = \false;
            $postFileCallback = function (int $stepCount) use (&$isProgressBarStarted, $filePaths, $configuration): void {
                if (!$configuration->shouldShowProgressBar()) {
                    return;
                }
                if (!$isProgressBarStarted) {
                    $fileCount = count($filePaths);
                    $this->easyCodingStandardStyle->progressStart($fileCount);
                    $isProgressBarStarted = \true;
                }
                $this->easyCodingStandardStyle->progressAdvance($stepCount);
                // running in parallel here → nothing else to do
            };
            $mainScript = $this->resolveCalledEcsBinary();
            if ($mainScript === null) {
                throw new ShouldNotHappenException('[parallel] Main script was not found');
            }
            // mimics see https://github.com/phpstan/phpstan-src/commit/9124c66dcc55a222e21b1717ba5f60771f7dda92#diff-387b8f04e0db7a06678eb52ce0c0d0aff73e0d7d8fc5df834d0a5fbec198e5daR139
            $errorsAndDiffs = $this->parallelFileProcessor->check($schedule, $mainScript, $postFileCallback, $configuration->getConfig(), $configuration);
        } else {
            // process found files by each processors
            $errorsAndDiffs = $this->processFoundFiles($filePaths, $configuration);
        }
        $errorsAndDiffs[Bridge::FILES_COUNT] = $filesCount;
        return $errorsAndDiffs;
    }
    /**
     * @param string[] $filePaths
     * @return array{coding_standard_errors: CodingStandardError[], file_diffs: FileDiff[], system_errors: SystemError[], system_errors_count: int}
     */
    private function processFoundFiles(array $filePaths, Configuration $configuration): array
    {
        $fileCount = count($filePaths);
        // 3. start progress bar
        $this->outputProgressBarAndDebugInfo($fileCount, $configuration);
        $errorsAndDiffs = [];
        foreach ($filePaths as $filePath) {
            if ($this->easyCodingStandardStyle->isDebug()) {
                $relativeFilePath = StaticRelativeFilePathHelper::resolveFromCwd($filePath);
                $this->easyCodingStandardStyle->writeln(' [file] ' . $relativeFilePath);
            }
            try {
                $currentErrorsAndDiffs = $this->singleFileProcessor->processFilePath($filePath, $configuration);
                if ($currentErrorsAndDiffs !== []) {
                    $errorsAndDiffs = $this->parametersMerger->merge($errorsAndDiffs, $currentErrorsAndDiffs);
                }
            } catch (ParseError $parseError) {
                $this->changedFilesDetector->invalidateFilePath($filePath);
                $relativeFilePath = StaticRelativeFilePathHelper::resolveFromCwd($filePath);
                $errorsAndDiffs[Bridge::SYSTEM_ERRORS][] = new SystemError($parseError->getLine(), $parseError->getMessage(), $relativeFilePath);
            }
            if ($configuration->shouldShowProgressBar()) {
                $this->easyCodingStandardStyle->progressAdvance();
            }
        }
        return $errorsAndDiffs;
    }
    private function outputProgressBarAndDebugInfo(int $fileInfoCount, Configuration $configuration): void
    {
        if (!$configuration->shouldShowProgressBar()) {
            return;
        }
        $this->easyCodingStandardStyle->progressStart($fileInfoCount);
    }
    /**
     * Path to called "ecs" binary file, e.g. "vendor/bin/ecs" returns "vendor/bin/ecs" This is needed to re-call the
     * ecs binary in sub-process in the same location.
     */
    private function resolveCalledEcsBinary(): ?string
    {
        if (!isset($_SERVER[self::ARGV][0])) {
            return null;
        }
        $potentialEcsBinaryPath = $_SERVER[self::ARGV][0];
        if (!file_exists($potentialEcsBinaryPath)) {
            return null;
        }
        return $potentialEcsBinaryPath;
    }
}
