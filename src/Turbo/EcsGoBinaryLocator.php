<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Turbo;

use Symplify\EasyCodingStandard\Turbo\Exception\EcsGoBinaryNotFoundException;
/**
 * Resolves the "ecs-go" Go binary that powers the experimental --blink mode.
 *
 * @see \Symplify\EasyCodingStandard\Tests\Turbo\EcsGoBinaryLocatorTest
 */
final class EcsGoBinaryLocator
{
    /**
     * @readonly
     * @var string
     */
    private $bundledDirectory = __DIR__ . '/../../bin';
    /**
     * @var string
     */
    private const ENV_OVERRIDE = 'ECS_TURBO_BIN';
    public function __construct(string $bundledDirectory = __DIR__ . '/../../bin')
    {
        // filled with per-platform blink binaries by the release build, see .github/workflows/buid_release.yaml
        $this->bundledDirectory = $bundledDirectory;
    }
    public function locate(): string
    {
        $envBinary = getenv(self::ENV_OVERRIDE);
        if (is_string($envBinary) && $envBinary !== '' && is_file($envBinary)) {
            return $envBinary;
        }
        $bundledBinary = $this->bundledDirectory . '/blink-' . $this->resolvePlatform();
        if (is_file($bundledBinary)) {
            // archive extraction can drop the executable bit
            if (!is_executable($bundledBinary)) {
                chmod($bundledBinary, 0755);
            }
            return $bundledBinary;
        }
        $vendorBinary = getcwd() . '/vendor/bin/blink';
        if (is_file($vendorBinary)) {
            return $vendorBinary;
        }
        $pathBinary = $this->findOnPath('blink');
        if ($pathBinary !== null) {
            return $pathBinary;
        }
        throw new EcsGoBinaryNotFoundException(sprintf('The blink binary for --blink was not found in "%s" env, "vendor/bin/blink" or on PATH. Build it with "go build" in the blink/ directory and point "%s" to it.', self::ENV_OVERRIDE, self::ENV_OVERRIDE));
    }
    private function resolvePlatform(): string
    {
        $machine = strtolower(php_uname('m'));
        $arch = in_array($machine, ['aarch64', 'arm64'], \true) ? 'arm64' : 'amd64';
        $suffix = \PHP_OS_FAMILY === 'Windows' ? '.exe' : '';
        return strtolower(\PHP_OS_FAMILY) . '-' . $arch . $suffix;
    }
    private function findOnPath(string $binaryName): ?string
    {
        $path = (string) getenv('PATH');
        foreach (explode(\PATH_SEPARATOR, $path) as $directory) {
            if ($directory === '') {
                continue;
            }
            $candidate = $directory . \DIRECTORY_SEPARATOR . $binaryName;
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }
        return null;
    }
}
