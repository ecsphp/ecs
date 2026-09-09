<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\DependencyInjection;

use Closure;
use ECSPrefix202609\Entropy\Container\Container;
use PHP_CodeSniffer\Util\Tokens;
use PhpCsFixer\Differ\DifferInterface;
use PhpCsFixer\Differ\UnifiedDiffer;
use PhpCsFixer\WhitespacesFixerConfig;
use Symplify\EasyCodingStandard\Caching\Cache;
use Symplify\EasyCodingStandard\Caching\CacheFactory;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle;
use Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyleFactory;
use Symplify\EasyCodingStandard\FixerRunner\WhitespacesFixerConfigFactory;
use ECSPrefix202609\Webmozart\Assert\Assert;
final class ServiceContainerFactory
{
    /**
     * @param string[] $configFiles
     */
    public function create(array $configFiles = []): ECSConfig
    {
        \Symplify\EasyCodingStandard\DependencyInjection\SimpleParameterProvider::reset();
        $this->loadPHPCodeSnifferConstants();
        $ecsConfig = new ECSConfig();
        // console
        $ecsConfig->service(EasyCodingStandardStyle::class, static function (Container $container): EasyCodingStandardStyle {
            /** @var EasyCodingStandardStyleFactory $easyCodingStandardStyleFactory */
            $easyCodingStandardStyleFactory = $container->make(EasyCodingStandardStyleFactory::class);
            return $easyCodingStandardStyleFactory->create();
        });
        // console commands - autodiscovered, then collected by the default CommandRegistry contract lookup
        $ecsConfig->autodiscover(__DIR__ . '/../Console/Command');
        // whitespace
        $ecsConfig->service(WhitespacesFixerConfig::class, static function (): WhitespacesFixerConfig {
            $whitespacesFixerConfigFactory = new WhitespacesFixerConfigFactory();
            return $whitespacesFixerConfigFactory->create();
        });
        // caching
        $ecsConfig->service(Cache::class, static function (Container $container): Cache {
            /** @var CacheFactory $cacheFactory */
            $cacheFactory = $container->make(CacheFactory::class);
            return $cacheFactory->create();
        });
        // diffing
        $ecsConfig->service(DifferInterface::class, static function (): DifferInterface {
            return new UnifiedDiffer();
        });
        // output formatters - autodiscovered, then collected by contract for OutputFormatterCollector
        $ecsConfig->autodiscover(__DIR__ . '/../Console/Output');
        // load default config first
        $configFiles = array_merge([__DIR__ . '/../../config/config.php'], $configFiles);
        foreach ($configFiles as $configFile) {
            $configClosure = require $configFile;
            Assert::isCallable($configClosure);
            if ($configClosure instanceof Closure && !defined('PHPUNIT_COMPOSER_INSTALL')) {
                /** @var EasyCodingStandardStyle $easyCodingStandardStyle */
                $easyCodingStandardStyle = $ecsConfig->make(EasyCodingStandardStyle::class);
                $easyCodingStandardStyle->warning(sprintf('The "return function (ECSConfig $ecsConfig): void {}" config format is deprecated. Use "return ECSConfig::configure()" fluent API instead in "%s".', $configFile));
                // give the user a moment to notice the deprecation warning
                sleep(5);
            }
            $configClosure($ecsConfig);
        }
        return $ecsConfig;
    }
    /**
     * These are require for PHP_CodeSniffer to run
     */
    private function loadPHPCodeSnifferConstants(): void
    {
        if (!defined('PHP_CODESNIFFER_VERBOSITY')) {
            // initialize token with INT type, otherwise php-cs-fixer and php-parser breaks
            if (!defined('T_MATCH')) {
                define('T_MATCH', 5000);
            }
            if (!defined('T_READONLY')) {
                define('T_READONLY', 5010);
            }
            if (!defined('T_ENUM')) {
                define('T_ENUM', 5015);
            }
            if (!defined('T_NULLSAFE_OBJECT_OPERATOR')) {
                define('T_NULLSAFE_OBJECT_OPERATOR', 5020);
            }
            // for PHP_CodeSniffer
            define('PHP_CODESNIFFER_CBF', \false);
            define('PHP_CODESNIFFER_VERBOSITY', 0);
            new Tokens();
        }
    }
}
