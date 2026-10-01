<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Caching;

use ECSPrefix202610\Nette\Utils\Json;
use Symplify\EasyCodingStandard\Application\Version\StaticVersionResolver;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\DependencyInjection\SimpleParameterProvider;
use Symplify\EasyCodingStandard\Exception\Configuration\FileNotFoundException;
use ECSPrefix202610\Webmozart\Assert\Assert;
/**
 * @see \Symplify\EasyCodingStandard\Tests\ChangedFilesDetector\FileHashComputer\FileHashComputerTest
 */
final class FileHashComputer
{
    public function computeConfig(string $filePath): string
    {
        $callable = require $filePath;
        Assert::isCallable($callable);
        $ecsConfig = new ECSConfig();
        $callable($ecsConfig);
        // hash the registered checkers and their configuration
        $fileHash = sha1(Json::encode($ecsConfig->getCheckerConfiguration()));
        return sha1($fileHash . SimpleParameterProvider::hash() . StaticVersionResolver::PACKAGE_VERSION);
    }
    public function compute(string $filePath): string
    {
        $fileHash = md5_file($filePath);
        if (!$fileHash) {
            throw new FileNotFoundException(sprintf('File "%s" was not found', $fileHash));
        }
        return $fileHash;
    }
}
