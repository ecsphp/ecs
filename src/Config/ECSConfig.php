<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Config;

use ECSPrefix202610\Entropy\Console\Output\OutputColorizer;
use ECSPrefix202610\Entropy\Console\Output\OutputPrinter;
use ECSPrefix202610\Entropy\Container\Container;
use Override;
use PHP_CodeSniffer\Sniffs\Sniff;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\WhitespacesFixerConfig;
use ReflectionProperty;
use Symplify\EasyCodingStandard\Configuration\ECSConfigBuilder;
use Symplify\EasyCodingStandard\DependencyInjection\CompilerPass\ConflictingCheckersCompilerPass;
use Symplify\EasyCodingStandard\DependencyInjection\CompilerPass\RemoveExcludedCheckersCompilerPass;
use Symplify\EasyCodingStandard\DependencyInjection\CompilerPass\RemoveMutualCheckersCompilerPass;
use Symplify\EasyCodingStandard\DependencyInjection\SimpleParameterProvider;
use Symplify\EasyCodingStandard\ValueObject\Option;
use ECSPrefix202610\Webmozart\Assert\Assert;
use ECSPrefix202610\Webmozart\Assert\InvalidArgumentException;
/**
 * @api
 */
final class ECSConfig extends Container
{
    /**
     * Registered checkers, mapped to their configuration (empty array = no configuration).
     *
     * @var array<class-string<Sniff|FixerInterface>, mixed[]>
     */
    private $checkerConfiguration = [];
    /**
     * Checkers removed by compiler passes (skip, mutual exclusion, …).
     *
     * @var array<class-string<Sniff|FixerInterface>, true>
     */
    private $removedCheckers = [];
    /**
     * Configured checker instances, built exactly once and shared, even when a
     * class is declared in both a set and explicitly.
     *
     * @var array<class-string<Sniff|FixerInterface>, Sniff|FixerInterface>
     */
    private $builtCheckers = [];
    /**
     * Registration order, with duplicates preserved: a checker declared both in a
     * set and explicitly is listed twice (both share the last-wins configuration).
     *
     * @var list<class-string<Sniff|FixerInterface>>
     */
    private $checkerRegistrationOrder = [];
    public static function configure(): ECSConfigBuilder
    {
        return new ECSConfigBuilder();
    }
    /**
     * @param string[] $paths
     */
    public function paths(array $paths): void
    {
        Assert::allString($paths);
        SimpleParameterProvider::setParameter(Option::PATHS, $paths);
    }
    /**
     * @param list<string>|array<class-string<Sniff|FixerInterface>, list<string>|null> $skips
     */
    public function skip(array $skips): void
    {
        SimpleParameterProvider::addParameter(Option::SKIP, $skips);
    }
    /**
     * @param string[] $sets
     */
    public function sets(array $sets): void
    {
        Assert::allString($sets);
        Assert::allFileExists($sets);
        foreach ($sets as $set) {
            $this->import($set);
        }
    }
    /**
     * @param class-string<Sniff|FixerInterface> $checkerClass
     */
    public function rule(string $checkerClass): void
    {
        $this->assertCheckerClass($checkerClass);
        $this->registerChecker($checkerClass, []);
    }
    /**
     * @param array<class-string<Sniff|FixerInterface>> $checkerClasses
     */
    public function rules(array $checkerClasses): void
    {
        $this->ensureCheckerClassesAreUnique($checkerClasses);
        foreach ($checkerClasses as $checkerClass) {
            $this->rule($checkerClass);
        }
    }
    /**
     * @param class-string<Sniff|FixerInterface> $checkerClass
     * @param mixed[] $configuration
     */
    public function ruleWithConfiguration(string $checkerClass, array $configuration): void
    {
        $this->assertCheckerClass($checkerClass);
        if (is_a($checkerClass, FixerInterface::class, \true)) {
            Assert::isAOf($checkerClass, ConfigurableFixerInterface::class);
        }
        $this->registerChecker($checkerClass, $configuration);
    }
    /**
     * @param array<class-string<Sniff|FixerInterface>, mixed[]> $rulesWithConfiguration
     */
    public function rulesWithConfiguration(array $rulesWithConfiguration): void
    {
        Assert::allIsArray($rulesWithConfiguration);
        foreach ($rulesWithConfiguration as $checkerClass => $configuration) {
            $this->ruleWithConfiguration($checkerClass, $configuration);
        }
    }
    /**
     * @param Option::INDENTATION_* $indentation
     */
    public function indentation(string $indentation): void
    {
        SimpleParameterProvider::setParameter(Option::INDENTATION, $indentation);
    }
    public function lineEnding(string $lineEnding): void
    {
        SimpleParameterProvider::setParameter(Option::LINE_ENDING, $lineEnding);
    }
    public function cacheDirectory(string $cacheDirectory): void
    {
        SimpleParameterProvider::setParameter(Option::CACHE_DIRECTORY, $cacheDirectory);
    }
    public function cacheNamespace(string $cacheNamespace): void
    {
        SimpleParameterProvider::setParameter(Option::CACHE_NAMESPACE, $cacheNamespace);
    }
    /**
     * @param string[] $fileExtensions
     */
    public function fileExtensions(array $fileExtensions): void
    {
        Assert::allString($fileExtensions);
        SimpleParameterProvider::addParameter(Option::FILE_EXTENSIONS, $fileExtensions);
    }
    public function parallel(int $seconds = 120, int $maxNumberOfProcess = 32, int $jobSize = 16): void
    {
        SimpleParameterProvider::setParameter(Option::PARALLEL, \true);
        SimpleParameterProvider::setParameter(Option::PARALLEL_TIMEOUT_IN_SECONDS, $seconds);
        SimpleParameterProvider::setParameter(Option::PARALLEL_MAX_NUMBER_OF_PROCESSES, $maxNumberOfProcess);
        SimpleParameterProvider::setParameter(Option::PARALLEL_JOB_SIZE, $jobSize);
    }
    /**
     * @api
     */
    public function disableParallel(): void
    {
        SimpleParameterProvider::setParameter(Option::PARALLEL, \false);
    }
    /**
     * @api
     * @deprecated Real path reporting is deprecated. Use the JSON output format ("--output-format json"), which always reports absolute paths.
     */
    public function reportingRealPath(): void
    {
        $outputPrinter = new OutputPrinter(new OutputColorizer());
        $outputPrinter->warning('The "reportingRealPath()" method is deprecated. Use the JSON output format ("--output-format json") to get absolute paths.');
    }
    /**
     * @deprecated Loading PHP-CS-Fixer sets is deprecated. Use ->rule()/->ruleWithConfiguration() or prepared sets instead.
     * @param string[] $setNames
     */
    public function dynamicSets(array $setNames): void
    {
        $outputPrinter = new OutputPrinter(new OutputColorizer());
        $outputPrinter->warning('The "dynamicSets()" method is deprecated. Use ->rule()/->ruleWithConfiguration() or prepared sets instead.');
        trigger_error('The "dynamicSets()" method is deprecated. Use ->rule()/->ruleWithConfiguration() or prepared sets instead.', \E_USER_DEPRECATED);
    }
    public function import(string $setFilePath): void
    {
        $self = $this;
        $closureFilePath = require $setFilePath;
        Assert::isCallable($closureFilePath);
        $closureFilePath($self);
    }
    public function boot(): void
    {
        $removeExcludedCheckersCompilerPass = new RemoveExcludedCheckersCompilerPass();
        $removeExcludedCheckersCompilerPass->process($this);
        $removeMutualCheckersCompilerPass = new RemoveMutualCheckersCompilerPass();
        $removeMutualCheckersCompilerPass->process($this);
        $conflictingCheckersCompilerPass = new ConflictingCheckersCompilerPass();
        $conflictingCheckersCompilerPass->process($this);
    }
    /**
     * Checkers are returned fully configured; every other class is built by the parent container.
     *
     * @template TType as object
     *
     * @param class-string<TType> $class
     * @return TType
     */
    #[Override]
    public function make(string $class): object
    {
        if (isset($this->checkerConfiguration[$class])) {
            // a configured-checker key is always a Sniff|FixerInterface class-string
            /** @var class-string<Sniff|FixerInterface> $checkerClass */
            $checkerClass = $class;
            /** @var TType $checker */
            $checker = $this->buildConfiguredChecker($checkerClass);
            return $checker;
        }
        return parent::make($class);
    }
    /**
     * Checkers are returned in registration order with duplicates preserved (a class
     * declared both in a set and explicitly appears twice, sharing one instance);
     * every other service is resolved by the parent container.
     *
     * @template TType as object
     *
     * @param class-string<TType> $contractClass
     * @return array<TType>
     */
    #[Override]
    public function findByContract(string $contractClass): array
    {
        // genuine (non-checker) services from the parent container
        $instances = [];
        foreach (parent::findByContract($contractClass) as $class => $instance) {
            if (isset($this->checkerConfiguration[$class])) {
                // checkers are handled below, in registration order with duplicates
                continue;
            }
            $instances[] = $instance;
        }
        // checkers, in registration order, keeping duplicates and honouring removals
        $checkerInstances = [];
        foreach ($this->checkerRegistrationOrder as $checkerClass) {
            if (isset($this->removedCheckers[$checkerClass])) {
                continue;
            }
            // avoid building checkers that cannot match the requested contract
            if (!is_a($checkerClass, $contractClass, \true)) {
                continue;
            }
            $checkerInstances[] = $this->buildConfiguredChecker($checkerClass);
        }
        $matchingCheckers = array_filter($checkerInstances, static function (object $instance) use ($contractClass): bool {
            return $instance instanceof $contractClass;
        });
        return array_merge($instances, array_values($matchingCheckers));
    }
    /**
     * Registered checker classes, minus those removed by compiler passes.
     *
     * @return array<class-string<Sniff|FixerInterface>>
     */
    public function getCheckerClasses(): array
    {
        return array_values(array_filter(array_keys($this->checkerConfiguration), function (string $checkerClass): bool {
            return !isset($this->removedCheckers[$checkerClass]);
        }));
    }
    /**
     * Registered checkers and their configuration, used for cache invalidation.
     *
     * @return array<class-string<Sniff|FixerInterface>, mixed[]>
     */
    public function getCheckerConfiguration(): array
    {
        $checkerConfiguration = [];
        foreach ($this->checkerConfiguration as $checkerClass => $configuration) {
            if (isset($this->removedCheckers[$checkerClass])) {
                continue;
            }
            $checkerConfiguration[$checkerClass] = $configuration;
        }
        return $checkerConfiguration;
    }
    /**
     * @param class-string<Sniff|FixerInterface> $checkerClass
     */
    public function removeChecker(string $checkerClass): void
    {
        $this->removedCheckers[$checkerClass] = \true;
    }
    /**
     * @param class-string<Sniff|FixerInterface> $checkerClass
     * @param mixed[] $configuration
     */
    private function registerChecker(string $checkerClass, array $configuration): void
    {
        // last registration wins for configuration, mirroring the previous container override behaviour
        $this->checkerConfiguration[$checkerClass] = $configuration;
        $this->checkerRegistrationOrder[] = $checkerClass;
        unset($this->removedCheckers[$checkerClass]);
    }
    /**
     * @param class-string<Sniff|FixerInterface> $checkerClass
     */
    private function buildConfiguredChecker(string $checkerClass): object
    {
        if (isset($this->builtCheckers[$checkerClass])) {
            return $this->builtCheckers[$checkerClass];
        }
        // parent::make() autowires the raw checker by reflection; this class' make() override would recurse here
        $checker = parent::make($checkerClass);
        if ($checker instanceof WhitespacesAwareFixerInterface) {
            $checker->setWhitespacesConfig($this->make(WhitespacesFixerConfig::class));
        }
        $configuration = $this->checkerConfiguration[$checkerClass];
        if ($checker instanceof ConfigurableFixerInterface) {
            $checker->configure($configuration);
        } elseif ($checker instanceof Sniff) {
            foreach ($configuration as $propertyName => $value) {
                Assert::propertyExists($checker, $propertyName);
                (new ReflectionProperty($checker, $propertyName))->setValue($checker, $value);
            }
        }
        $this->builtCheckers[$checkerClass] = $checker;
        return $checker;
    }
    /**
     * @param class-string $checkerClass
     */
    private function assertCheckerClass(string $checkerClass): void
    {
        Assert::classExists($checkerClass);
        Assert::isAnyOf($checkerClass, [Sniff::class, FixerInterface::class]);
    }
    /**
     * @param string[] $checkerClasses
     */
    private function ensureCheckerClassesAreUnique(array $checkerClasses): void
    {
        // ensure all rules are registered exactly once
        $checkerClassToCount = array_count_values($checkerClasses);
        $duplicatedCheckerClassToCount = array_filter($checkerClassToCount, static function (int $count): bool {
            return $count > 1;
        });
        if ($duplicatedCheckerClassToCount === []) {
            return;
        }
        $duplicatedCheckerClasses = array_flip($duplicatedCheckerClassToCount);
        $errorMessage = sprintf('There are duplicated classes in $rectorConfig->rules(): "%s". Make them unique to avoid unexpected behavior.', implode('", "', $duplicatedCheckerClasses));
        throw new InvalidArgumentException($errorMessage);
    }
}
