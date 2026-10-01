<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Testing\PHPUnit;

use Iterator;
use ECSPrefix202610\Nette\Utils\FileSystem;
use ECSPrefix202610\Nette\Utils\Strings;
use Override;
use Symplify\EasyCodingStandard\Exception\ShouldNotHappenException;
use Symplify\EasyCodingStandard\FixerRunner\Application\FixerFileProcessor;
use Symplify\EasyCodingStandard\SniffRunner\Application\SniffFileProcessor;
use Symplify\EasyCodingStandard\Testing\Contract\ConfigAwareInterface;
use ECSPrefix202610\Webmozart\Assert\Assert;
// needed for scoped version to load unprefixed classes; does not have any effect inside the class
$scoperAutoloadFilepath = __DIR__ . '/../../../vendor/scoper-autoload.php';
if (file_exists($scoperAutoloadFilepath)) {
    require_once $scoperAutoloadFilepath;
}
/**
 * @api used for external testing
 */
abstract class AbstractCheckerTestCase extends \Symplify\EasyCodingStandard\Testing\PHPUnit\AbstractTestCase implements ConfigAwareInterface
{
    /**
     * @var string
     */
    private const SPLIT_LINE_REGEX = "#\\-\\-\\-\\-\\-\r?\n#";
    /**
     * @var string[]
     */
    private const POSSIBLE_CODE_SNIFFER_AUTOLOAD_PATHS = [
        __DIR__ . '/../../../../../vendor/squizlabs/php_codesniffer/autoload.php',
        __DIR__ . '/../../../../vendor/squizlabs/php_codesniffer/autoload.php',
        // if bin is located in /bin or nested vendor
        __DIR__ . '/../../../vendor/squizlabs/php_codesniffer/autoload.php',
    ];
    /**
     * @var \Symplify\EasyCodingStandard\FixerRunner\Application\FixerFileProcessor
     */
    private $fixerFileProcessor;
    /**
     * @var \Symplify\EasyCodingStandard\SniffRunner\Application\SniffFileProcessor
     */
    private $sniffFileProcessor;
    #[Override]
    protected function setUp(): void
    {
        // autoload php code sniffer before Kernel boot
        $this->autoloadCodeSniffer();
        $configs = $this->getValidatedConfigs();
        $this->createContainerWithConfigs($configs);
        $this->fixerFileProcessor = $this->make(FixerFileProcessor::class);
        $this->sniffFileProcessor = $this->make(SniffFileProcessor::class);
    }
    protected function doTestFile(string $filePath): void
    {
        $this->ensureSomeCheckersAreRegistered();
        $fileContents = FileSystem::read($filePath);
        // before and after case - we want to see a change
        if (strpos($fileContents, '-----') !== \false) {
            [$inputContents, $expectedContents] = Strings::split($fileContents, self::SPLIT_LINE_REGEX);
        } else {
            // no change, part before and after are the same
            $inputContents = $fileContents;
            $expectedContents = $fileContents;
        }
        $inputFilePath = sys_get_temp_dir() . '/ecs_tests/' . md5($inputContents) . '.php';
        FileSystem::write($inputFilePath, $inputContents, null);
        // 1. process php-cs-fixer
        if ($this->fixerFileProcessor->getCheckers() !== []) {
            $processedFileContent = $this->fixerFileProcessor->processFileToString($inputFilePath);
            $this->assertEquals($expectedContents, $processedFileContent);
            // 2. process php coce sniffer
        } elseif ($this->sniffFileProcessor->getCheckers() !== []) {
            $processedFileContent = $this->sniffFileProcessor->processFileToString($inputFilePath);
            $this->assertEquals($expectedContents, $processedFileContent);
        }
    }
    protected static function yieldFiles(string $directory, string $suffix = '*.php.inc'): Iterator
    {
        return \Symplify\EasyCodingStandard\Testing\PHPUnit\FixtureFinder::yieldDataProviderFiles($directory, $suffix);
    }
    private function autoloadCodeSniffer(): void
    {
        foreach (self::POSSIBLE_CODE_SNIFFER_AUTOLOAD_PATHS as $possibleCodeSnifferAutoloadPath) {
            if (!file_exists($possibleCodeSnifferAutoloadPath)) {
                continue;
            }
            require_once $possibleCodeSnifferAutoloadPath;
            return;
        }
    }
    private function ensureSomeCheckersAreRegistered(): void
    {
        $totalCheckersLoaded = count($this->sniffFileProcessor->getCheckers()) + count($this->fixerFileProcessor->getCheckers());
        if ($totalCheckersLoaded > 0) {
            return;
        }
        throw new ShouldNotHappenException('No fixers nor sniffers were found. Registers them in your config.');
    }
    /**
     * @return string[]
     */
    private function getValidatedConfigs(): array
    {
        $config = $this->provideConfig();
        Assert::fileExists($config);
        return [$config];
    }
}
