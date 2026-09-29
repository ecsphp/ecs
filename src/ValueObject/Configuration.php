<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\ValueObject;

use Symplify\EasyCodingStandard\Console\Output\ConsoleOutputFormatter;
final class Configuration
{
    /**
     * @readonly
     * @var bool
     */
    private $isFixer = \false;
    /**
     * @readonly
     * @var bool
     */
    private $shouldClearCache = \false;
    /**
     * @readonly
     * @var bool
     */
    private $showProgressBar = \true;
    /**
     * @readonly
     * @var bool
     */
    private $showErrorTable = \true;
    /**
     * @var string[]
     * @readonly
     */
    private $sources = [];
    /**
     * @readonly
     * @var string
     */
    private $outputFormat = ConsoleOutputFormatter::NAME;
    /**
     * @readonly
     * @var bool
     */
    private $isParallel = \false;
    /**
     * @readonly
     * @var string|null
     */
    private $config;
    /**
     * @readonly
     * @var string|null
     */
    private $parallelPort = null;
    /**
     * @readonly
     * @var string|null
     */
    private $parallelIdentifier = null;
    /**
     * @readonly
     * @var string|null
     */
    private $memoryLimit = null;
    /**
     * @readonly
     * @var bool
     */
    private $showDiffs = \true;
    /**
     * @readonly
     * @var bool
     */
    private $isDirty = \false;
    /**
     * @param string[] $sources
     */
    public function __construct(bool $isFixer = \false, bool $shouldClearCache = \false, bool $showProgressBar = \true, bool $showErrorTable = \true, array $sources = [], string $outputFormat = ConsoleOutputFormatter::NAME, bool $isParallel = \false, ?string $config = null, ?string $parallelPort = null, ?string $parallelIdentifier = null, ?string $memoryLimit = null, bool $showDiffs = \true, bool $isDirty = \false)
    {
        $this->isFixer = $isFixer;
        $this->shouldClearCache = $shouldClearCache;
        $this->showProgressBar = $showProgressBar;
        $this->showErrorTable = $showErrorTable;
        $this->sources = $sources;
        $this->outputFormat = $outputFormat;
        $this->isParallel = $isParallel;
        $this->config = $config;
        $this->parallelPort = $parallelPort;
        $this->parallelIdentifier = $parallelIdentifier;
        $this->memoryLimit = $memoryLimit;
        $this->showDiffs = $showDiffs;
        $this->isDirty = $isDirty;
    }
    public function isFixer(): bool
    {
        return $this->isFixer;
    }
    public function shouldClearCache(): bool
    {
        return $this->shouldClearCache;
    }
    public function shouldShowProgressBar(): bool
    {
        return $this->showProgressBar;
    }
    public function shouldShowErrorTable(): bool
    {
        return $this->showErrorTable;
    }
    public function shouldShowDiffs(): bool
    {
        return $this->showDiffs;
    }
    public function isDirty(): bool
    {
        return $this->isDirty;
    }
    /**
     * @return string[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }
    public function getOutputFormat(): string
    {
        return $this->outputFormat;
    }
    public function isParallel(): bool
    {
        return $this->isParallel;
    }
    public function getConfig(): ?string
    {
        return $this->config;
    }
    public function getParallelPort(): ?string
    {
        return $this->parallelPort;
    }
    public function getParallelIdentifier(): ?string
    {
        return $this->parallelIdentifier;
    }
    public function getMemoryLimit(): ?string
    {
        return $this->memoryLimit;
    }
}
