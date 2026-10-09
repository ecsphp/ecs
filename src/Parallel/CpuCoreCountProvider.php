<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Parallel;

use ECSPrefix202610\Fidry\CpuCoreCounter\CpuCoreCounter;
final class CpuCoreCountProvider
{
    public function provide(): int
    {
        // reserve one core to avoid maxing out the CPU; getAvailableForParallelisation() also
        // respects cgroup/CFS quota (docker --cpus, KUBERNETES_CPU_LIMIT) instead of host cores
        return (new CpuCoreCounter())->getAvailableForParallelisation(1)->availableCpus;
    }
}
