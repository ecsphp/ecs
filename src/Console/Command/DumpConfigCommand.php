<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Console\Command;

use ECSPrefix202609\Entropy\Console\Contract\CommandInterface;
use ECSPrefix202609\Nette\Utils\Json;
use Symplify\EasyCodingStandard\Configuration\ConfigurationFactory;
use Symplify\EasyCodingStandard\Console\ExitCode;
use Symplify\EasyCodingStandard\Console\Output\ConsoleOutputFormatter;
use Symplify\EasyCodingStandard\Turbo\TurboConfigDumper;
/**
 * Dumps the resolved ecs.php configuration - paths, rules and skips - as JSON,
 * for the ecs-go turbo runner to consume. See docs/turbo.md.
 */
final class DumpConfigCommand implements CommandInterface
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Configuration\ConfigurationFactory
     */
    private $configurationFactory;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Turbo\TurboConfigDumper
     */
    private $turboConfigDumper;
    public function __construct(ConfigurationFactory $configurationFactory, TurboConfigDumper $turboConfigDumper)
    {
        $this->configurationFactory = $configurationFactory;
        $this->turboConfigDumper = $turboConfigDumper;
    }
    public function getName(): string
    {
        return 'dump-config';
    }
    public function getDescription(): string
    {
        return 'Dump the resolved configuration (paths, rules, skips) as JSON for the ecs-go turbo runner';
    }
    /**
     * @param string $config   Path to config file
     * @param string ...$paths The path(s) to dump the configuration for.
     *
     * @option $config
     *
     * @api invoked via reflection by the Entropy console application
     *
     * @return ExitCode::*
     */
    public function run(string $config = '', string ...$paths): int
    {
        $configuration = $this->configurationFactory->create(array_values($paths), \false, \false, \false, \false, \false, ConsoleOutputFormatter::NAME, $config !== '' ? $config : null, '', '', null, \false);
        $data = $this->turboConfigDumper->dump($configuration->getSources());
        echo Json::encode($data, Json::PRETTY) . \PHP_EOL;
        return ExitCode::SUCCESS;
    }
}
