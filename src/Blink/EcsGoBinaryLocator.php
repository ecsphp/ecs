<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Blink;

use Symplify\EasyCodingStandard\Blink\Exception\EcsGoBinaryNotFoundException;
/**
 * Resolves the "ecs-go" Go binary that powers the experimental --blink mode.
 *
 * @see \Symplify\EasyCodingStandard\Tests\Blink\EcsGoBinaryLocatorTest
 */
final class EcsGoBinaryLocator
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Blink\BlinkBinaryDownloader
     */
    private $blinkBinaryDownloader;
    /**
     * @readonly
     * @var string
     */
    private $binDirectory = __DIR__ . '/../../bin';
    /**
     * @var string
     */
    private const ENV_OVERRIDE = 'ECS_BLINK_BIN';
    public function __construct(\Symplify\EasyCodingStandard\Blink\BlinkBinaryDownloader $blinkBinaryDownloader, string $binDirectory = __DIR__ . '/../../bin')
    {
        $this->blinkBinaryDownloader = $blinkBinaryDownloader;
        // the release build writes the blink release tag to "blink.version", see .github/workflows/buid_release.yaml
        $this->binDirectory = $binDirectory;
    }
    public function locate(): string
    {
        $envBinary = getenv(self::ENV_OVERRIDE);
        if (is_string($envBinary) && $envBinary !== '' && is_file($envBinary)) {
            return $envBinary;
        }
        $releaseBinary = $this->resolveReleaseBinary();
        if ($releaseBinary !== null) {
            return $releaseBinary;
        }
        $pathBinary = $this->findOnPath('blink');
        if ($pathBinary !== null) {
            return $pathBinary;
        }
        throw new EcsGoBinaryNotFoundException(sprintf('The blink binary for --blink was not found in "%s" env or on PATH. Build it with "go build" in the blink/ directory and point "%s" to it.', self::ENV_OVERRIDE, self::ENV_OVERRIDE));
    }
    private function resolveReleaseBinary(): ?string
    {
        $versionFile = $this->binDirectory . '/blink.version';
        if (!is_file($versionFile)) {
            return null;
        }
        $releaseTag = trim((string) file_get_contents($versionFile));
        $platform = $this->resolvePlatform();
        // e.g. "bin/blink-0123456789ab-linux-amd64", versioned so an update never runs a stale binary
        $releaseBinary = $this->binDirectory . '/' . $releaseTag . '-' . $platform;
        if (!is_file($releaseBinary)) {
            $this->blinkBinaryDownloader->download($releaseTag, 'blink-' . $platform, $releaseBinary);
        }
        return $releaseBinary;
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
