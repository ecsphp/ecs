<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Turbo;

/**
 * Resolves the "ecs-go" Go binary that powers the experimental --turbo mode.
 *
 * @see \Symplify\EasyCodingStandard\Tests\Turbo\EcsGoBinaryLocatorTest
 */
final class EcsGoBinaryLocator
{
    /**
     * @var string
     */
    private const ENV_OVERRIDE = 'ECS_TURBO_BIN';
    public function locate(): string
    {
        $envBinary = getenv(self::ENV_OVERRIDE);
        if (is_string($envBinary) && $envBinary !== '' && is_file($envBinary)) {
            return $envBinary;
        }
        $vendorBinary = getcwd() . '/vendor/bin/ecs-go';
        if (is_file($vendorBinary)) {
            return $vendorBinary;
        }
        // fall back to the binary on PATH
        return 'ecs-go';
    }
}
