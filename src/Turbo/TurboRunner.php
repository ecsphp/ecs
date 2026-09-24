<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Turbo;

use ECSPrefix202609\Nette\Utils\Json;
/**
 * Experimental --turbo mode: hands the run over to the "ecs-go" Go binary instead
 * of the PHP engine. The resolved ecs.php config (paths, rules, skips) is written
 * to a temp JSON file and passed to ecs-go via --ecs-config, so ecs-go maps the
 * ECS rules onto its own fixers. See docs/turbo.md for the current limitations.
 *
 * @see \Symplify\EasyCodingStandard\Tests\Turbo\TurboRunnerTest
 */
final class TurboRunner
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Turbo\EcsGoBinaryLocator
     */
    private $ecsGoBinaryLocator;
    public function __construct(\Symplify\EasyCodingStandard\Turbo\EcsGoBinaryLocator $ecsGoBinaryLocator)
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
            // symfony/process is in "replace"; passthru streams ecs-go's output straight through
            $command = implode(' ', array_map(\Closure::fromCallable('escapeshellarg'), $arguments));
            $exitCode = 0;
            passthru($command, $exitCode);
            return $exitCode;
        } finally {
            @unlink($configPath);
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
        $configPath = tempnam(sys_get_temp_dir(), 'ecs-turbo-') . '.json';
        file_put_contents($configPath, Json::encode($configData, Json::PRETTY));
        return $configPath;
    }
}
