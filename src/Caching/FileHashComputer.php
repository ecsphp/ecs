<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Caching;

use ECSPrefix202611\Nette\Utils\Json;
use ReflectionClass;
use Symplify\EasyCodingStandard\Application\Version\StaticVersionResolver;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\DependencyInjection\SimpleParameterProvider;
use Symplify\EasyCodingStandard\Exception\Configuration\FileNotFoundException;
use ECSPrefix202611\Webmozart\Assert\Assert;
/**
 * @see \Symplify\EasyCodingStandard\Tests\ChangedFilesDetector\FileHashComputer\FileHashComputerTest
 */
final class FileHashComputer
{
    /**
     * @var mixed[]
     */
    private const BUILT_IN_NAMESPACES = ['PhpCsFixer\\', 'PHP_CodeSniffer\\', 'Symplify\\'];
    public function computeConfig(string $filePath): string
    {
        $callable = require $filePath;
        Assert::isCallable($callable);
        $ecsConfig = new ECSConfig();
        $callable($ecsConfig);
        // hash the registered checkers and their configuration
        $checkerConfiguration = $ecsConfig->getCheckerConfiguration();
        $fileHash = sha1(Json::encode($checkerConfiguration));
        // hash the code of local checkers, as it can change while their name and configuration stay the same
        $localCheckersHash = $this->hashLocalCheckerFiles(array_keys($checkerConfiguration));
        return sha1($fileHash . $localCheckersHash . SimpleParameterProvider::hash() . StaticVersionResolver::PACKAGE_VERSION);
    }
    public function compute(string $filePath): string
    {
        $fileHash = md5_file($filePath);
        if (!$fileHash) {
            throw new FileNotFoundException(sprintf('File "%s" was not found', $fileHash));
        }
        return $fileHash;
    }
    /**
     * @param array<class-string> $checkerClasses
     */
    private function hashLocalCheckerFiles(array $checkerClasses): string
    {
        $fileHashes = [];
        foreach ($checkerClasses as $checkerClass) {
            // a local checker can extend a local parent class
            for ($class = $checkerClass; $class !== \false; $class = get_parent_class($class)) {
                if ($this->isBuiltIn($class)) {
                    break;
                }
                $fileName = (new ReflectionClass($class))->getFileName();
                if ($fileName === \false || strpos($fileName, \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR) !== \false) {
                    break;
                }
                $fileHashes[$fileName] = $this->compute($fileName);
            }
        }
        return sha1(implode('', $fileHashes));
    }
    private function isBuiltIn(string $class): bool
    {
        $found = \false;
        foreach (self::BUILT_IN_NAMESPACES as $namespace) {
            if (strncmp($class, $namespace, strlen($namespace)) === 0) {
                $found = \true;
                break;
            }
        }
        return $found;
    }
}
