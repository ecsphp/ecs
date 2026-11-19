<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Blink;

use Symplify\EasyCodingStandard\Blink\Exception\EcsGoBinaryNotFoundException;
/**
 * Fetches a blink binary from its GitHub release and verifies it against the release checksums.txt
 *
 * @see \Symplify\EasyCodingStandard\Tests\Blink\BlinkBinaryDownloaderTest
 */
final class BlinkBinaryDownloader
{
    /**
     * @readonly
     * @var string
     */
    private $releaseBaseUrl = 'https://github.com/ecsphp/ecs-src/releases/download';
    /**
     * @var int
     */
    private const TIMEOUT_SECONDS = 5;
    public function __construct(string $releaseBaseUrl = 'https://github.com/ecsphp/ecs-src/releases/download')
    {
        $this->releaseBaseUrl = $releaseBaseUrl;
    }
    public function download(string $releaseTag, string $assetName, string $targetFile): void
    {
        $releaseUrl = $this->releaseBaseUrl . '/' . $releaseTag;
        $expectedChecksum = $this->resolveExpectedChecksum($this->fetch($releaseUrl . '/checksums.txt'), $assetName);
        $binaryContent = $this->fetch($releaseUrl . '/' . $assetName);
        if (hash('sha256', $binaryContent) !== $expectedChecksum) {
            throw new EcsGoBinaryNotFoundException(sprintf('Checksum of the downloaded "%s" from "%s" does not match.', $assetName, $releaseUrl));
        }
        // write next to the target, then rename, so a parallel run never sees a half-written binary
        $temporaryFile = $targetFile . '.' . getmypid() . '.tmp';
        if (@file_put_contents($temporaryFile, $binaryContent) === \false) {
            throw new EcsGoBinaryNotFoundException(sprintf('Unable to write the blink binary to "%s".', $temporaryFile));
        }
        chmod($temporaryFile, 0755);
        rename($temporaryFile, $targetFile);
    }
    private function fetch(string $url): string
    {
        $streamContext = stream_context_create(['http' => ['header' => 'User-Agent: ecs', 'timeout' => self::TIMEOUT_SECONDS]]);
        $content = @file_get_contents($url, \false, $streamContext);
        if ($content === \false) {
            throw new EcsGoBinaryNotFoundException(sprintf('Unable to download "%s".', $url));
        }
        return $content;
    }
    private function resolveExpectedChecksum(string $checksumsContent, string $assetName): string
    {
        foreach (explode("\n", $checksumsContent) as $line) {
            $parts = preg_split('#\s+\*?#', trim($line));
            if ($parts !== \false && count($parts) === 2 && $parts[1] === $assetName) {
                return strtolower($parts[0]);
            }
        }
        throw new EcsGoBinaryNotFoundException(sprintf('No checksum for "%s" found.', $assetName));
    }
}
