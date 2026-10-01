<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Console\Command;

use ECSPrefix202610\Clue\React\NDJson\Decoder;
use ECSPrefix202610\Clue\React\NDJson\Encoder;
use ECSPrefix202610\Entropy\Console\Contract\CommandInterface;
use ECSPrefix202610\Entropy\Console\Contract\HiddenCommandInterface;
use ECSPrefix202610\React\EventLoop\StreamSelectLoop;
use ECSPrefix202610\React\Socket\ConnectionInterface;
use ECSPrefix202610\React\Socket\TcpConnector;
use Symplify\EasyCodingStandard\Configuration\ConfigurationFactory;
use Symplify\EasyCodingStandard\Console\ExitCode;
use Symplify\EasyCodingStandard\Console\Output\ConsoleOutputFormatter;
use Symplify\EasyCodingStandard\MemoryLimitter;
use Symplify\EasyCodingStandard\Parallel\Enum\Action;
use Symplify\EasyCodingStandard\Parallel\Enum\ReactCommand;
use Symplify\EasyCodingStandard\Parallel\WorkerRunner;
/**
 * Inspired at: https://github.com/phpstan/phpstan-src/commit/9124c66dcc55a222e21b1717ba5f60771f7dda92
 * https://github.com/phpstan/phpstan-src/blob/c471c7b050e0929daf432288770de673b394a983/src/Command/WorkerCommand.php
 *
 * ↓↓↓
 * https://github.com/phpstan/phpstan-src/commit/b84acd2e3eadf66189a64fdbc6dd18ff76323f67#diff-7f625777f1ce5384046df08abffd6c911cfbb1cfc8fcb2bdeaf78f337689e3e2
 */
final class WorkerCommand implements CommandInterface, HiddenCommandInterface
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Parallel\WorkerRunner
     */
    private $workerRunner;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\MemoryLimitter
     */
    private $memoryLimitter;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Configuration\ConfigurationFactory
     */
    private $configurationFactory;
    public function __construct(WorkerRunner $workerRunner, MemoryLimitter $memoryLimitter, ConfigurationFactory $configurationFactory)
    {
        $this->workerRunner = $workerRunner;
        $this->memoryLimitter = $memoryLimitter;
        $this->configurationFactory = $configurationFactory;
    }
    public function getName(): string
    {
        return 'worker';
    }
    public function getDescription(): string
    {
        return '[INTERNAL] Support for parallel process';
    }
    /**
     * @param string $config       Path to config file
     * @param string $outputFormat Select output format
     * @param string $memoryLimit  Memory limit for check
     * @param string $port         [INTERNAL] parallel TCP port
     * @param string $identifier   [INTERNAL] parallel identifier
     * @param string ...$paths     The path(s) to be checked.
     *
     * @option $config
     * @option $outputFormat
     * @option $memoryLimit
     * @option $port
     * @option $identifier
     *
     * @api invoked via reflection by the Entropy console application
     *
     * @return ExitCode::*
     */
    public function run(bool $fix = \false, bool $clearCache = \false, bool $noProgressBar = \false, bool $noErrorTable = \false, bool $noDiffs = \false, bool $debug = \false, string $config = '', string $outputFormat = ConsoleOutputFormatter::NAME, string $memoryLimit = '', string $port = '', string $identifier = '', string ...$paths): int
    {
        $configuration = $this->configurationFactory->create(array_values($paths), $fix, $clearCache, $noProgressBar, $noErrorTable, $noDiffs, $outputFormat, $config !== '' ? $config : null, $port, $identifier, $memoryLimit !== '' ? $memoryLimit : null, $debug, \false);
        $this->memoryLimitter->adjust($configuration);
        $streamSelectLoop = new StreamSelectLoop();
        $parallelIdentifier = $configuration->getParallelIdentifier();
        $tcpConnector = new TcpConnector($streamSelectLoop);
        $promise = $tcpConnector->connect('127.0.0.1:' . $configuration->getParallelPort());
        $promise->then(function (ConnectionInterface $connection) use ($parallelIdentifier, $configuration): void {
            $inDecoder = new Decoder($connection, \true, 512, \JSON_INVALID_UTF8_IGNORE);
            $outEncoder = new Encoder($connection, \JSON_INVALID_UTF8_IGNORE);
            // handshake?
            $outEncoder->write([ReactCommand::ACTION => Action::HELLO, ReactCommand::IDENTIFIER => $parallelIdentifier]);
            $this->workerRunner->run($outEncoder, $inDecoder, $configuration);
        });
        $streamSelectLoop->run();
        return ExitCode::SUCCESS;
    }
}
