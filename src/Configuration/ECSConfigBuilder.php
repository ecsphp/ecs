<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Configuration;

use ECSPrefix202610\Entropy\Console\Output\OutputColorizer;
use ECSPrefix202610\Entropy\Console\Output\OutputPrinter;
use PHP_CodeSniffer\Sniffs\Sniff;
use PhpCsFixer\Fixer\FixerInterface;
use ECSPrefix202610\Symfony\Component\Finder\Finder;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\Config\Level\ArrayLevel;
use Symplify\EasyCodingStandard\Config\Level\ControlStructuresLevel;
use Symplify\EasyCodingStandard\Config\Level\DocblockLevel;
use Symplify\EasyCodingStandard\Config\Level\SpacesLevel;
use Symplify\EasyCodingStandard\Configuration\Levels\LevelRulesResolver;
use Symplify\EasyCodingStandard\Exception\Configuration\InitializationException;
use Symplify\EasyCodingStandard\Exception\Configuration\SuperfluousConfigurationException;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;
/**
 * @api
 */
final class ECSConfigBuilder
{
    /**
     * @var string[]
     */
    private $paths = [];
    /**
     * @var string[]
     */
    private $sets = [];
    /**
     * @var array<mixed>
     */
    private $skip = [];
    /**
     * @var array<class-string<Sniff|FixerInterface>>
     */
    private $rules = [];
    /**
     * @var array<class-string<(FixerInterface|Sniff)>, mixed>
     */
    private $rulesWithConfiguration = [];
    /**
     * @var string[]
     */
    private $fileExtensions = [];
    /**
     * @var string|null
     */
    private $cacheDirectory;
    /**
     * @var string|null
     */
    private $cacheNamespace;
    /**
     * @var Option::INDENTATION_*
     */
    private $indentation;
    /**
     * @var string|null
     */
    private $lineEnding;
    /**
     * @var bool|null
     */
    private $parallel;
    /**
     * @var int
     */
    private $parallelTimeoutSeconds = 120;
    /**
     * @var int
     */
    private $parallelMaxNumberOfProcess = 32;
    /**
     * @var int
     */
    private $parallelJobSize = 16;
    /**
     * To make sure each common set and its corresponding level are not
     * duplicated, as both contain the same rules.
     * @var bool|null
     */
    private $isArrayLevelUsed;
    /**
     * @var bool|null
     */
    private $isControlStructuresLevelUsed;
    /**
     * @var bool|null
     */
    private $isDocblockLevelUsed;
    /**
     * @var bool|null
     */
    private $isSpacesLevelUsed;
    public function __invoke(ECSConfig $ecsConfig): void
    {
        $this->assertLevelAndSetNotMixed($this->isArrayLevelUsed, SetList::ARRAY, 'array', 'withArrayLevel');
        $this->assertLevelAndSetNotMixed($this->isControlStructuresLevelUsed, SetList::CONTROL_STRUCTURES, 'control structures', 'withControlStructuresLevel');
        $this->assertLevelAndSetNotMixed($this->isDocblockLevelUsed, SetList::DOCBLOCK, 'docblock', 'withDocblockLevel');
        $this->assertLevelAndSetNotMixed($this->isSpacesLevelUsed, SetList::SPACES, 'spaces', 'withSpacesLevel');
        if ($this->sets !== []) {
            $ecsConfig->sets($this->sets);
        }
        if ($this->paths !== []) {
            $ecsConfig->paths($this->paths);
        }
        if ($this->skip !== []) {
            $ecsConfig->skip($this->skip);
        }
        if ($this->rules !== []) {
            $ecsConfig->rules($this->rules);
        }
        if ($this->rulesWithConfiguration !== []) {
            $ecsConfig->rulesWithConfiguration($this->rulesWithConfiguration);
        }
        if ($this->fileExtensions !== []) {
            $ecsConfig->fileExtensions($this->fileExtensions);
        }
        if ($this->cacheDirectory !== null) {
            $ecsConfig->cacheDirectory($this->cacheDirectory);
        }
        if ($this->cacheNamespace !== null) {
            $ecsConfig->cacheNamespace($this->cacheNamespace);
        }
        if ($this->indentation !== null) {
            $ecsConfig->indentation($this->indentation);
        }
        if ($this->lineEnding !== null) {
            $ecsConfig->lineEnding($this->lineEnding);
        }
        if ($this->parallel !== null) {
            if ($this->parallel) {
                $ecsConfig->parallel($this->parallelTimeoutSeconds, $this->parallelMaxNumberOfProcess, $this->parallelJobSize);
            } else {
                $ecsConfig->disableParallel();
            }
        }
    }
    /**
     * @param string[] $paths
     */
    public function withPaths(array $paths): self
    {
        $this->paths = $paths;
        return $this;
    }
    /**
     * @param array<mixed> $skip
     */
    public function withSkip(array $skip): self
    {
        $this->skip = $skip;
        return $this;
    }
    /**
     * Include PHP files from the root directory,
     * typically ecs.php, rector.php etc.
     */
    public function withRootFiles(): self
    {
        $rootPhpFilesFinder = (new Finder())->files()->in(getcwd())->depth(0)->name('*.php');
        foreach ($rootPhpFilesFinder as $rootPhpFileFinder) {
            $this->paths[] = $rootPhpFileFinder->getRealPath();
        }
        return $this;
    }
    public function withPreparedSets(
        /** @see SetList::PSR_12 */
        bool $psr12 = \false,
        /** @see SetList::PER_CS */
        bool $perCs = \false,
        /** @see SetList::COMMON */
        bool $common = \false,
        /** @see SetList::LARAVEL */
        bool $laravel = \false,
        // common sets
        /** @see SetList::ARRAY */
        bool $arrays = \false,
        /** @see SetList::COMMENTS */
        bool $comments = \false,
        /** @see SetList::DOCBLOCK */
        bool $docblocks = \false,
        /** @see SetList::SPACES */
        bool $spaces = \false,
        /** @see SetList::NAMESPACES */
        bool $namespaces = \false,
        /** @see SetList::CONTROL_STRUCTURES */
        bool $controlStructures = \false,
        /** @see SetList::CASING */
        bool $casing = \false,
        /** @see SetList::CLEANUP */
        bool $cleanup = \false,
        /** @see SetList::CLEAN_CODE */
        bool $cleanCode = \false,
        /** @see SetList::STANDALONE_LINE */
        bool $standaloneLine = \false
    ): self
    {
        if (func_get_args() === []) {
            throw new InitializationException('Pick at least one set in "->withPreparedSets()" in your ecs.php using named arguments, e.g. "->withPreparedSets(spaces: true)"');
        }
        if ($psr12) {
            $this->sets[] = SetList::PSR_12;
        }
        if ($perCs) {
            $this->sets[] = SetList::PER_CS;
        }
        if ($common) {
            // include all "common" sets
            $this->sets[] = SetList::COMMON;
            if (($alreadyIncludedSets = array_keys(array_filter(['arrays' => $arrays, 'spaces' => $spaces, 'namespaces' => $namespaces, 'docblocks' => $docblocks, 'controlStructures' => $controlStructures, 'comments' => $comments, 'casing' => $casing, 'cleanup' => $cleanup]))) !== []) {
                throw new SuperfluousConfigurationException(sprintf('The following sets are already included in the "common" set: %s. Please remove them.', implode(', ', $alreadyIncludedSets)));
            }
        } else {
            if ($arrays) {
                $this->sets[] = SetList::ARRAY;
            }
            if ($spaces) {
                $this->sets[] = SetList::SPACES;
            }
            if ($namespaces) {
                $this->sets[] = SetList::NAMESPACES;
            }
            if ($docblocks) {
                $this->sets[] = SetList::DOCBLOCK;
            }
            if ($controlStructures) {
                $this->sets[] = SetList::CONTROL_STRUCTURES;
            }
            if ($comments) {
                $this->sets[] = SetList::COMMENTS;
            }
            if ($casing) {
                $this->sets[] = SetList::CASING;
            }
            if ($cleanup) {
                $this->sets[] = SetList::CLEANUP;
            }
        }
        if ($cleanCode) {
            $this->sets[] = SetList::CLEAN_CODE;
        }
        if ($standaloneLine) {
            $this->sets[] = SetList::STANDALONE_LINE;
        }
        if ($laravel) {
            $this->sets[] = SetList::LARAVEL;
        }
        return $this;
    }
    /**
     * @deprecated Loading PHP-CS-Fixer sets is deprecated. Use ->withPreparedSets() or ->withSets() with the prepared sets instead.
     */
    public function withPhpCsFixerSets(bool ...$sets): self
    {
        $outputPrinter = new OutputPrinter(new OutputColorizer());
        $outputPrinter->warning('The "withPhpCsFixerSets()" method is deprecated. Use ->withPreparedSets() or ->withSets() with prepared sets instead.');
        return $this;
    }
    /**
     * @param string[] $sets
     */
    public function withSets(array $sets): self
    {
        $this->sets = array_merge($this->sets, $sets);
        return $this;
    }
    /**
     * @param array<class-string<Sniff|FixerInterface>> $rules
     */
    public function withRules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }
    /**
     * @param string[] $fileExtensions
     */
    public function withFileExtensions(array $fileExtensions): self
    {
        $this->fileExtensions = $fileExtensions;
        return $this;
    }
    public function withCache(?string $directory = null, ?string $namespace = null): self
    {
        $this->cacheDirectory = $directory;
        $this->cacheNamespace = $namespace;
        return $this;
    }
    /**
     * @deprecated EditorConfig support is deprecated, as it leaks project-wide settings into ECS. Configure the matching PHP-CS-Fixer rules explicitly instead.
     */
    public function withEditorConfig(): self
    {
        $outputPrinter = new OutputPrinter(new OutputColorizer());
        $outputPrinter->warning('The "withEditorConfig()" method is deprecated, as it leaks project-wide settings into ECS. Configure the matching PHP-CS-Fixer rules explicitly instead.');
        return $this;
    }
    /**
     * @param Option::INDENTATION_*|null $indentation
     */
    public function withSpacing(?string $indentation = null, ?string $lineEnding = null): self
    {
        $this->indentation = $indentation;
        $this->lineEnding = $lineEnding;
        return $this;
    }
    /**
     * @param class-string<(FixerInterface|Sniff)> $checkerClass
     * @param mixed[] $configuration
     */
    public function withConfiguredRule(string $checkerClass, array $configuration): self
    {
        $this->rulesWithConfiguration[$checkerClass] = $configuration;
        return $this;
    }
    public function withParallel(?int $timeoutSeconds = null, ?int $maxNumberOfProcess = null, ?int $jobSize = null): self
    {
        $this->parallel = \true;
        if (is_int($timeoutSeconds)) {
            $this->parallelTimeoutSeconds = $timeoutSeconds;
        }
        if (is_int($maxNumberOfProcess)) {
            $this->parallelMaxNumberOfProcess = $maxNumberOfProcess;
        }
        if (is_int($jobSize)) {
            $this->parallelJobSize = $jobSize;
        }
        return $this;
    }
    public function withoutParallel(): self
    {
        $this->parallel = \false;
        return $this;
    }
    /**
     * @deprecated Real path reporting is deprecated. Use the JSON output format ("--output-format json"), which always reports absolute paths.
     */
    public function withRealPathReporting(): self
    {
        $outputPrinter = new OutputPrinter(new OutputColorizer());
        $outputPrinter->warning('The "withRealPathReporting()" method is deprecated. Use the JSON output format ("--output-format json") to get absolute paths.');
        return $this;
    }
    /**
     * Raise your spacing coverage from the safest rules
     * to more affecting ones, one level at a time.
     */
    public function withSpacesLevel(int $level): self
    {
        $this->isSpacesLevelUsed = \true;
        $this->applyLevel($level, SpacesLevel::RULES, SpacesLevel::RULE_CONFIGURATIONS, __METHOD__);
        return $this;
    }
    /**
     * Raise your array coverage from the safest rules
     * to more affecting ones, one level at a time.
     */
    public function withArrayLevel(int $level): self
    {
        $this->isArrayLevelUsed = \true;
        $this->applyLevel($level, ArrayLevel::RULES, ArrayLevel::RULE_CONFIGURATIONS, __METHOD__);
        return $this;
    }
    /**
     * Raise your control structures coverage from the safest rules
     * to more affecting ones, one level at a time.
     */
    public function withControlStructuresLevel(int $level): self
    {
        $this->isControlStructuresLevelUsed = \true;
        $this->applyLevel($level, ControlStructuresLevel::RULES, ControlStructuresLevel::RULE_CONFIGURATIONS, __METHOD__);
        return $this;
    }
    /**
     * Raise your docblock coverage from the safest rules
     * to more affecting ones, one level at a time.
     */
    public function withDocblockLevel(int $level): self
    {
        $this->isDocblockLevelUsed = \true;
        $this->applyLevel($level, DocblockLevel::RULES, DocblockLevel::RULE_CONFIGURATIONS, __METHOD__);
        return $this;
    }
    /**
     * @param array<class-string<Sniff|FixerInterface>> $rules
     * @param array<class-string<Sniff|FixerInterface>, mixed[]> $ruleConfigurations
     */
    private function applyLevel(int $level, array $rules, array $ruleConfigurations, string $method): void
    {
        $levelRules = LevelRulesResolver::resolve($level, $rules, $method);
        foreach ($levelRules as $levelRule) {
            if (isset($ruleConfigurations[$levelRule])) {
                $this->rulesWithConfiguration[$levelRule] = $ruleConfigurations[$levelRule];
                continue;
            }
            $this->rules[] = $levelRule;
        }
    }
    private function assertLevelAndSetNotMixed(?bool $isLevelUsed, string $setConst, string $setLabel, string $methodName): void
    {
        if ($isLevelUsed !== \true) {
            return;
        }
        if (in_array($setConst, $this->sets, \true)) {
            throw new SuperfluousConfigurationException(sprintf('Your config already enables the "%s" set.%sRemove "->%s()" as it only duplicates it, or remove the "%s" set.', $setLabel, \PHP_EOL, $methodName, $setLabel));
        }
        if (in_array(SetList::COMMON, $this->sets, \true)) {
            throw new SuperfluousConfigurationException(sprintf('Your config already enables the "common" set, which includes the "%s" set.%sRemove "->%s()" as it only duplicates it, or remove the "common" set.', $setLabel, \PHP_EOL, $methodName));
        }
    }
}
