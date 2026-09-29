<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Parallel;

use ECSPrefix202609\Fidry\CpuCoreCounter\CpuCoreCounter;
use ECSPrefix202609\Fidry\CpuCoreCounter\NumberOfCpuCoreNotFound;
final class CpuCoreCountProvider
{
    /**
     * @var int
     */
    private const DEFAULT_CORE_COUNT = 2;
    public function provide(): int
    {
        try {
            $coreCount = (new CpuCoreCounter())->getCount();
        } catch (NumberOfCpuCoreNotFound $exception) {
            return self::DEFAULT_CORE_COUNT;
        }
        // leave one core free, to avoid maxing out the CPU
        return max(1, $coreCount - 1);
    }
}
