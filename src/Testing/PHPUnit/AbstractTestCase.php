<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Testing\PHPUnit;

use PHPUnit\Framework\TestCase;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\DependencyInjection\ServiceContainerFactory;
use ECSPrefix202610\Webmozart\Assert\Assert;
abstract class AbstractTestCase extends TestCase
{
    /**
     * @var \Symplify\EasyCodingStandard\Config\ECSConfig|null
     */
    private $ecsConfig;
    protected function setUp(): void
    {
        $serviceContainerFactory = new ServiceContainerFactory();
        $this->ecsConfig = $serviceContainerFactory->create();
        $this->ecsConfig->boot();
    }
    /**
     * @param string[] $configs
     */
    protected function createContainerWithConfigs(array $configs): void
    {
        Assert::allString($configs);
        Assert::allFile($configs);
        $serviceContainerFactory = new ServiceContainerFactory();
        $this->ecsConfig = $serviceContainerFactory->create($configs);
        $this->ecsConfig->boot();
    }
    /**
     * @template TObject as object
     *
     * @param class-string<TObject> $class
     * @return TObject
     */
    protected function make(string $class): object
    {
        Assert::notNull($this->ecsConfig);
        return $this->ecsConfig->make($class);
    }
}
