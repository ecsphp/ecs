<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Parallel\Application;

use ECSPrefix202610\Clue\React\NDJson\Decoder;
use ECSPrefix202610\Clue\React\NDJson\Encoder;
use ECSPrefix202610\Nette\Utils\Random;
use ECSPrefix202610\React\EventLoop\StreamSelectLoop;
use ECSPrefix202610\React\Socket\ConnectionInterface;
use ECSPrefix202610\React\Socket\TcpServer;
use Symplify\EasyCodingStandard\Console\ExitCode;
use Symplify\EasyCodingStandard\DependencyInjection\SimpleParameterProvider;
use Symplify\EasyCodingStandard\Parallel\CommandLine\WorkerCommandLineFactory;
use Symplify\EasyCodingStandard\Parallel\Enum\Action;
use Symplify\EasyCodingStandard\Parallel\Enum\Content;
use Symplify\EasyCodingStandard\Parallel\Enum\ReactCommand;
use Symplify\EasyCodingStandard\Parallel\Enum\ReactEvent;
use Symplify\EasyCodingStandard\Parallel\ValueObject\Bridge;
use Symplify\EasyCodingStandard\Parallel\ValueObject\ParallelProcess;
use Symplify\EasyCodingStandard\Parallel\ValueObject\ProcessPool;
use Symplify\EasyCodingStandard\Parallel\ValueObject\Schedule;
use Symplify\EasyCodingStandard\SniffRunner\ValueObject\Error\CodingStandardError;
use Symplify\EasyCodingStandard\ValueObject\Configuration;
use Symplify\EasyCodingStandard\ValueObject\Error\FileDiff;
use Symplify\EasyCodingStandard\ValueObject\Error\SystemError;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Throwable;
/**
 * Inspired from @see
 * https://github.com/phpstan/phpstan-src/commit/9124c66dcc55a222e21b1717ba5f60771f7dda92#diff-39c7a3b0cbb217bbfff96fbb454e6e5e60c74cf92fbb0f9d246b8bebbaad2bb0
 *
 * https://github.com/phpstan/phpstan-src/commit/b84acd2e3eadf66189a64fdbc6dd18ff76323f67#diff-7f625777f1ce5384046df08abffd6c911cfbb1cfc8fcb2bdeaf78f337689e3e2R150
 */
final class ParallelFileProcessor
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Parallel\CommandLine\WorkerCommandLineFactory
     */
    private $workerCommandLineFactory;
    /**
     * @var int
     */
    private const SYSTEM_ERROR_LIMIT = 50;
    public function __construct(WorkerCommandLineFactory $workerCommandLineFactory)
    {
        $this->workerCommandLineFactory = $workerCommandLineFactory;
    }
    /**
     * @api
     *
     * @param callable(int $stepCount): void $postFileCallback Used for progress bar jump
     * @return array{coding_standard_errors: CodingStandardError[], file_diffs: FileDiff[], system_errors: SystemError[]|string[], system_errors_count: int}
     */
    public function check(Schedule $schedule, string $mainScript, callable $postFileCallback, ?string $projectConfigFile, Configuration $configuration): array
    {
        $jobs = array_reverse($schedule->getJobs());
        $streamSelectLoop = new StreamSelectLoop();
        // basic properties setup
        $numberOfProcesses = $schedule->getNumberOfProcesses();
        // initial counters
        $codingStandardErrors = [];
        $fileDiffs = [];
        $systemErrors = [];
        $tcpServer = new TcpServer('127.0.0.1:0', $streamSelectLoop);
        $processPool = new ProcessPool($tcpServer);
        $tcpServer->on(ReactEvent::CONNECTION, function (ConnectionInterface $connection) use (&$jobs, $processPool): void {
            $inDecoder = new Decoder($connection, \true, 512, 0, 4 * 1024 * 1024);
            $outEncoder = new Encoder($connection);
            $inDecoder->on(ReactEvent::DATA, function (array $data) use (&$jobs, $inDecoder, $outEncoder, $processPool): void {
                $action = $data[ReactCommand::ACTION];
                if ($action !== Action::HELLO) {
                    return;
                }
                $processIdentifier = $data[Option::PARALLEL_IDENTIFIER];
                $parallelProcess = $processPool->getProcess($processIdentifier);
                $parallelProcess->bindConnection($inDecoder, $outEncoder);
                if ($jobs === []) {
                    $processPool->quitProcess($processIdentifier);
                    return;
                }
                $job = array_pop($jobs);
                $parallelProcess->request([ReactCommand::ACTION => Action::MAIN, Content::FILES => $job]);
            });
        });
        /** @var string $serverAddress */
        $serverAddress = $tcpServer->getAddress();
        /** @var int $serverPort */
        $serverPort = parse_url($serverAddress, \PHP_URL_PORT);
        $systemErrorsCount = 0;
        $reachedSystemErrorsCountLimit = \false;
        $handleErrorCallable = function (Throwable $throwable) use (&$systemErrors, &$systemErrorsCount, &$reachedSystemErrorsCountLimit, $processPool): void {
            $systemErrors[] = new SystemError($throwable->getLine(), $throwable->getMessage(), $throwable->getFile());
            ++$systemErrorsCount;
            $reachedSystemErrorsCountLimit = \true;
            $processPool->quitAll();
        };
        $timeoutInSeconds = SimpleParameterProvider::getIntParameter(Option::PARALLEL_TIMEOUT_IN_SECONDS);
        // options mirrored to each worker sub-process
        $workerOptionValues = [Option::FIX => $configuration->isFixer(), Option::CLEAR_CACHE => $configuration->shouldClearCache(), Option::NO_ERROR_TABLE => !$configuration->shouldShowErrorTable(), Option::NO_DIFFS => !$configuration->shouldShowDiffs(), Option::MEMORY_LIMIT => $configuration->getMemoryLimit()];
        for ($i = 0; $i < $numberOfProcesses; ++$i) {
            // nothing else to process, stop now
            if ($jobs === []) {
                break;
            }
            $processIdentifier = Random::generate();
            $workerCommandLine = $this->workerCommandLineFactory->create($mainScript, 'worker', $projectConfigFile, $workerOptionValues, $configuration->getSources(), $processIdentifier, $serverPort);
            $parallelProcess = new ParallelProcess($workerCommandLine, $streamSelectLoop, $timeoutInSeconds);
            $parallelProcess->start(
                // 1. callable on data
                function (array $json) use ($parallelProcess, &$systemErrors, &$fileDiffs, &$codingStandardErrors, &$jobs, $postFileCallback, &$systemErrorsCount, &$reachedInternalErrorsCountLimit, $processIdentifier, $processPool): void {
                    // decode arrays to objects
                    foreach ($json[Bridge::SYSTEM_ERRORS] as $jsonError) {
                        if (is_string($jsonError)) {
                            $systemErrors[] = 'System error: ' . $jsonError;
                            continue;
                        }
                        $systemErrors[] = SystemError::decode($jsonError);
                    }
                    foreach ($json[Bridge::FILE_DIFFS] as $jsonError) {
                        $fileDiffs[] = FileDiff::decode($jsonError);
                    }
                    foreach ($json[Bridge::CODING_STANDARD_ERRORS] as $jsonError) {
                        $codingStandardErrors[] = CodingStandardError::decode($jsonError);
                    }
                    $postFileCallback($json[Bridge::FILES_COUNT]);
                    $systemErrorsCount += $json[Bridge::SYSTEM_ERRORS_COUNT];
                    if ($systemErrorsCount >= self::SYSTEM_ERROR_LIMIT) {
                        $reachedInternalErrorsCountLimit = \true;
                        $processPool->quitAll();
                    }
                    if ($jobs === []) {
                        $processPool->quitProcess($processIdentifier);
                        return;
                    }
                    $job = array_pop($jobs);
                    $parallelProcess->request([ReactCommand::ACTION => Action::MAIN, Content::FILES => $job]);
                },
                // 2. callable on error
                $handleErrorCallable,
                // 3. callable on exit
                function ($exitCode, string $stdErr) use (&$systemErrors, $processIdentifier, $processPool): void {
                    $processPool->tryQuitProcess($processIdentifier);
                    if ($exitCode === ExitCode::SUCCESS) {
                        return;
                    }
                    if ($exitCode === null) {
                        return;
                    }
                    $systemErrors[] = 'Child process error: ' . $stdErr;
                }
            );
            $processPool->attachProcess($processIdentifier, $parallelProcess);
        }
        $streamSelectLoop->run();
        if ($reachedSystemErrorsCountLimit) {
            $systemErrors[] = sprintf('Reached system errors count limit of %d, exiting...', self::SYSTEM_ERROR_LIMIT);
        }
        return [Bridge::CODING_STANDARD_ERRORS => $codingStandardErrors, Bridge::FILE_DIFFS => $fileDiffs, Bridge::SYSTEM_ERRORS => $systemErrors, Bridge::SYSTEM_ERRORS_COUNT => count($systemErrors)];
    }
}
