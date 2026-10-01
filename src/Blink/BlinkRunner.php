<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Blink;

use ECSPrefix202610\Nette\Utils\FileSystem;
use ECSPrefix202610\Nette\Utils\Json;
use Symplify\EasyCodingStandard\Exception\ShouldNotHappenException;
/**
 * Experimental --blink mode: hands the run over to the "ecs-go" Go binary instead
 * of the PHP engine. The resolved ecs.php config (paths, rules, skips) is written
 * to a temp JSON file and passed to ecs-go via --ecs-config, so ecs-go maps the
 * ECS rules onto its own fixers. See docs/blink.md for the current limitations.
 *
 * @see \Symplify\EasyCodingStandard\Tests\Blink\BlinkRunnerTest
 */
final class BlinkRunner
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Blink\EcsGoBinaryLocator
     */
    private $ecsGoBinaryLocator;
    public function __construct(\Symplify\EasyCodingStandard\Blink\EcsGoBinaryLocator $ecsGoBinaryLocator)
    {
        $this->ecsGoBinaryLocator = $ecsGoBinaryLocator;
    }
    /**
     * @param array{paths: string[], rules: array<mixed>, skips: array<mixed>} $configData
     */
    public function run(array $configData, bool $isFixMode): int
    {
        $binary = $this->ecsGoBinaryLocator->locate();
        $configPath = $this->writeConfig($configData);
        try {
            $arguments = $this->createArguments($binary, $configPath, $isFixMode);
            // string command, as the array form needs PHP 7.4 and the release is downgraded to 7.2
            $command = implode(' ', array_map(\Closure::fromCallable('escapeshellarg'), $arguments));
            // inherit the terminal, so ecs-go detects a TTY and colors its output
            $process = proc_open($command, [\STDIN, \STDOUT, \STDERR], $pipes);
            if (!is_resource($process)) {
                throw new ShouldNotHappenException(sprintf('Unable to start "%s"', $binary));
            }
            return proc_close($process);
        } finally {
            FileSystem::delete($configPath);
        }
    }
    /**
     * @return string[]
     */
    public function createArguments(string $binary, string $configPath, bool $isFixMode): array
    {
        $arguments = [$binary, '--ecs-config', $configPath];
        // ecs-go reports by default; --fix rewrites in place, matching the
        // check-vs-fix split of ECS itself
        if ($isFixMode) {
            $arguments[] = '--fix';
        }
        return $arguments;
    }
    /**
     * @param array{paths: string[], rules: array<mixed>, skips: array<mixed>} $configData
     */
    private function writeConfig(array $configData): string
    {
        $configPath = tempnam(sys_get_temp_dir(), 'ecs-blink-') . '.json';
        file_put_contents($configPath, Json::encode($configData, Json::PRETTY));
        return $configPath;
    }
}
