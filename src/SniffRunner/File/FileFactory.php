<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\SniffRunner\File;

use ECSPrefix202610\Nette\Utils\FileSystem;
use PHP_CodeSniffer\Fixer;
use Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle;
use Symplify\EasyCodingStandard\Skipper\Skipper\Skipper;
use Symplify\EasyCodingStandard\SniffRunner\DataCollector\SniffMetadataCollector;
use Symplify\EasyCodingStandard\SniffRunner\ValueObject\File;
/**
 * @see \Symplify\EasyCodingStandard\Tests\SniffRunner\File\FileFactoryTest
 */
final class FileFactory
{
    /**
     * @readonly
     * @var \PHP_CodeSniffer\Fixer
     */
    private $fixer;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Skipper\Skipper\Skipper
     */
    private $skipper;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\SniffRunner\DataCollector\SniffMetadataCollector
     */
    private $sniffMetadataCollector;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle
     */
    private $easyCodingStandardStyle;
    public function __construct(Fixer $fixer, Skipper $skipper, SniffMetadataCollector $sniffMetadataCollector, EasyCodingStandardStyle $easyCodingStandardStyle)
    {
        $this->fixer = $fixer;
        $this->skipper = $skipper;
        $this->sniffMetadataCollector = $sniffMetadataCollector;
        $this->easyCodingStandardStyle = $easyCodingStandardStyle;
    }
    public function createFromFile(string $filePath): File
    {
        $fileContents = FileSystem::read($filePath);
        return new File($filePath, $fileContents, $this->fixer, $this->skipper, $this->sniffMetadataCollector, $this->easyCodingStandardStyle);
    }
}
