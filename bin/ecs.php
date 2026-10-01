<?php

declare (strict_types=1);
namespace ECSPrefix202610;

// decoupled in own "*.php" file, so ECS, Rector and PHPStan works out of the box here
use ECSPrefix202610\Composer\InstalledVersions;
use ECSPrefix202610\Composer\XdebugHandler\XdebugHandler;
use ECSPrefix202610\Entropy\Console\ConsoleApplication;
use ECSPrefix202610\Entropy\Console\Output\OutputColorizer;
use ECSPrefix202610\Entropy\Console\Output\OutputPrinter;
use PHP_CodeSniffer\Util\Tokens;
use Symplify\EasyCodingStandard\Application\Version\StaticVersionResolver;
use Symplify\EasyCodingStandard\Console\ExitCode;
use Symplify\EasyCodingStandard\DependencyInjection\EasyCodingStandardContainerFactory;
use Symplify\EasyCodingStandard\DependencyInjection\ServiceContainerFactory;
// performance boost
\gc_disable();
\define('__ECS_RUNNING__', \true);
// 1. autoload
// load react/proc functions
if (\file_exists(__DIR__ . '/../preload.php')) {
    require_once __DIR__ . '/../preload.php';
}
$autoloadIncluder = new ECSAutoloadIncluder();
$autoloadIncluder->includeCwdVendorAutoloadIfExists();
$autoloadIncluder->loadIfNotLoadedYet(__DIR__ . '/../vendor/scoper-autoload.php');
$autoloadIncluder->autoloadProjectAutoloaderFile('/../../autoload.php');
$autoloadIncluder->includeDependencyOrRepositoryVendorAutoloadIfExists();
$autoloadIncluder->includePhpCodeSnifferAutoload();
/**
 * Inspired by https://github.com/rectorphp/rector/pull/2373/files#diff-0fc04a2bb7928cac4ae339d5a8bf67f3
 */
final class ECSAutoloadIncluder
{
    /**
     * @var string[]
     */
    private const POSSIBLE_AUTOLOAD_PATHS = [
        // after split package
        __DIR__ . '/../vendor',
        // dependency
        __DIR__ . '/../../..',
        // monorepo
        __DIR__ . '/../../../vendor',
    ];
    /**
     * @var string[]
     */
    private $alreadyLoadedAutoloadFiles = [];
    public function includeCwdVendorAutoloadIfExists(): void
    {
        $cwdVendorAutoload = \getcwd() . '/vendor/autoload.php';
        if (!\is_file($cwdVendorAutoload)) {
            return;
        }
        $this->loadIfNotLoadedYet($cwdVendorAutoload);
    }
    public function includeDependencyOrRepositoryVendorAutoloadIfExists(): void
    {
        // ECS' vendor is already loaded
        if (\class_exists(ServiceContainerFactory::class)) {
            return;
        }
        $devVendorAutoload = __DIR__ . '/../vendor/autoload.php';
        if (!\is_file($devVendorAutoload)) {
            return;
        }
        $this->loadIfNotLoadedYet($devVendorAutoload);
    }
    public function autoloadProjectAutoloaderFile(string $file): void
    {
        $path = \dirname(__DIR__) . $file;
        if (!\is_file($path)) {
            return;
        }
        $this->loadIfNotLoadedYet($path);
    }
    public function includePhpCodeSnifferAutoload(): void
    {
        // 1. autoload
        foreach (self::POSSIBLE_AUTOLOAD_PATHS as $possibleAutoloadPath) {
            $possiblePhpCodeSnifferAutoloadPath = $possibleAutoloadPath . '/squizlabs/php_codesniffer/autoload.php';
            if (!\is_file($possiblePhpCodeSnifferAutoloadPath)) {
                continue;
            }
            require_once $possiblePhpCodeSnifferAutoloadPath;
        }
        // initialize token with INT type, otherwise php-cs-fixer and php-parser breaks
        if (!\defined('T_MATCH')) {
            \define('T_MATCH', 5000);
        }
        if (!\defined('T_READONLY')) {
            \define('T_READONLY', 5010);
        }
        if (!\defined('T_ENUM')) {
            \define('T_ENUM', 5015);
        }
        if (!\defined('T_NULLSAFE_OBJECT_OPERATOR')) {
            \define('T_NULLSAFE_OBJECT_OPERATOR', 5020);
        }
        // for PHP_CodeSniffer
        \define('PHP_CODESNIFFER_CBF', \false);
        \define('PHP_CODESNIFFER_VERBOSITY', 0);
        new Tokens();
    }
    public function loadIfNotLoadedYet(string $file): void
    {
        if (!\file_exists($file)) {
            return;
        }
        if (\in_array($file, $this->alreadyLoadedAutoloadFiles, \true)) {
            return;
        }
        /** @var non-empty-string|false $realPath */
        $realPath = \realpath($file);
        if (!\is_string($realPath)) {
            return;
        }
        $this->alreadyLoadedAutoloadFiles[] = $realPath;
        require_once $file;
    }
}
/**
 * Inspired by https://github.com/rectorphp/rector/pull/2373/files#diff-0fc04a2bb7928cac4ae339d5a8bf67f3
 */
\class_alias('ECSPrefix202610\ECSAutoloadIncluder', 'ECSAutoloadIncluder', \false);
$rawArgv = $_SERVER['argv'] ?? [];
// @fixes https://github.com/rectorphp/rector/issues/2205
$isXdebugAllowed = \in_array('--xdebug', $rawArgv, \true);
if (!$isXdebugAllowed && !\defined('PHPUNIT_COMPOSER_INSTALL')) {
    $xdebugHandler = new XdebugHandler('ecs');
    $xdebugHandler->check();
    unset($xdebugHandler);
}
try {
    $ecsContainerFactory = new EasyCodingStandardContainerFactory();
    $container = $ecsContainerFactory->createFromArgv($rawArgv);
} catch (\Throwable $throwable) {
    $outputPrinter = new OutputPrinter(new OutputColorizer());
    $outputPrinter->error($throwable->getMessage());
    $outputPrinter->writeln($throwable->getTraceAsString());
    exit(ExitCode::FAILURE);
}
// print version and exit
if (\in_array('--version', $rawArgv, \true) || \in_array('-V', $rawArgv, \true)) {
    echo \sprintf('EasyCodingStandard %s', StaticVersionResolver::PACKAGE_VERSION) . \PHP_EOL;
    echo \sprintf('+ PHP_CodeSniffer %s', InstalledVersions::getPrettyVersion('squizlabs/php_codesniffer')) . \PHP_EOL;
    echo \sprintf('+ PHP-CS-Fixer %s', InstalledVersions::getPrettyVersion('friendsofphp/php-cs-fixer')) . \PHP_EOL;
    exit(ExitCode::SUCCESS);
}
/** @var ConsoleApplication $application */
$application = $container->make(ConsoleApplication::class);
$statusCode = $application->run(ecs_normalize_argv($rawArgv));
exit($statusCode);
/**
 * Strip global/decoration flags Symfony Console handled implicitly, and normalize
 * the "-c" config shortcut to "--config", so the Entropy input parser does not
 * treat them as unknown command options.
 *
 * When the first argument is an option (e.g. "ecs --config=ecs.php"), inject the
 * default "check" command, so the Entropy input parser does not mistake the leading
 * "--config=<file>" for an unknown boolean flag and fail with "Unknown option".
 *
 * @param string[] $argv
 * @return string[]
 */
function ecs_normalize_argv(array $argv): array
{
    $decorationFlags = ['--ansi', '--no-ansi', '--quiet', '-q', '--no-interaction', '-n', '-v', '-vv', '-vvv', '--xdebug'];
    if (\in_array('--no-ansi', $argv, \true)) {
        \putenv('NO_COLOR=1');
    }
    $normalized = [];
    foreach ($argv as $arg) {
        if (\in_array($arg, $decorationFlags, \true)) {
            continue;
        }
        if ($arg === '-c') {
            $normalized[] = '--config';
            continue;
        }
        if (\strncmp($arg, '-c=', \strlen('-c=')) === 0) {
            $normalized[] = '--config=' . \substr($arg, 3);
            continue;
        }
        $normalized[] = $arg;
    }
    // $normalized[0] is the script name; the first real argument sits at index 1.
    // Keep "-h"/"--help" untouched, so it still prints the global command list.
    $helpFlags = ['-h', '--help'];
    if (isset($normalized[1]) && \strncmp($normalized[1], '-', \strlen('-')) === 0 && !\in_array($normalized[1], $helpFlags, \true)) {
        \array_splice($normalized, 1, 0, 'check');
    }
    return $normalized;
}
