<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\FixerRunner\Application;

use ECSPrefix202610\Nette\Utils\FileSystem;
use PhpCsFixer\Differ\DifferInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;
use Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle;
use Symplify\EasyCodingStandard\Contract\Application\FileProcessorInterface;
use Symplify\EasyCodingStandard\Error\FileDiffFactory;
use Symplify\EasyCodingStandard\Exception\ShouldNotHappenException;
use Symplify\EasyCodingStandard\FixerRunner\Parser\FileToTokensParser;
use Symplify\EasyCodingStandard\Parallel\ValueObject\Bridge;
use Symplify\EasyCodingStandard\Skipper\Skipper\Skipper;
use Symplify\EasyCodingStandard\ValueObject\Configuration;
use Symplify\EasyCodingStandard\ValueObject\Error\FileDiff;
use Throwable;
/**
 * @see \Symplify\EasyCodingStandard\Tests\Error\ErrorCollector\FixerFileProcessorTest
 */
final class FixerFileProcessor implements FileProcessorInterface
{
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\FixerRunner\Parser\FileToTokensParser
     */
    private $fileToTokensParser;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Skipper\Skipper\Skipper
     */
    private $skipper;
    /**
     * @readonly
     * @var \PhpCsFixer\Differ\DifferInterface
     */
    private $differ;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle
     */
    private $easyCodingStandardStyle;
    /**
     * @readonly
     * @var \Symplify\EasyCodingStandard\Error\FileDiffFactory
     */
    private $fileDiffFactory;
    /**
     * @var FixerInterface[]
     * @readonly
     */
    private $fixers;
    /**
     * @readonly
     * @var bool
     */
    private $isDebug;
    /**
     * @param FixerInterface[] $fixers
     */
    public function __construct(FileToTokensParser $fileToTokensParser, Skipper $skipper, DifferInterface $differ, EasyCodingStandardStyle $easyCodingStandardStyle, FileDiffFactory $fileDiffFactory, array $fixers)
    {
        $this->fileToTokensParser = $fileToTokensParser;
        $this->skipper = $skipper;
        $this->differ = $differ;
        $this->easyCodingStandardStyle = $easyCodingStandardStyle;
        $this->fileDiffFactory = $fileDiffFactory;
        $this->fixers = $this->sortFixers($fixers);
        $this->isDebug = $easyCodingStandardStyle->isDebug();
    }
    /**
     * @return FixerInterface[]
     */
    public function getCheckers(): array
    {
        return $this->fixers;
    }
    /**
     * @return array{file_diffs?: FileDiff[]}
     */
    public function processFile(string $filePath, Configuration $configuration): array
    {
        $tokens = $this->fileToTokensParser->parseFromFilePath($filePath);
        $appliedFixers = [];
        foreach ($this->fixers as $fixer) {
            if ($this->processTokensByFixer($filePath, $tokens, $fixer)) {
                $appliedFixers[] = get_class($fixer);
            }
        }
        if ($appliedFixers === []) {
            return [];
        }
        $fileContents = FileSystem::read($filePath);
        $diff = $this->differ->diff($fileContents, $tokens->generateCode());
        // some fixer with feature overlap can null each other
        if ($diff === '') {
            return [];
        }
        $fileDiffs = [];
        // file has changed
        $fileDiffs[] = $this->fileDiffFactory->createFromDiffAndAppliedCheckers($filePath, $diff, $appliedFixers);
        $tokenGeneratedCode = $tokens->generateCode();
        if ($configuration->isFixer()) {
            FileSystem::write($filePath, $tokenGeneratedCode, null);
        }
        Tokens::clearCache();
        return [Bridge::FILE_DIFFS => $fileDiffs];
    }
    public function processFileToString(string $filePath): string
    {
        $tokens = $this->fileToTokensParser->parseFromFilePath($filePath);
        $appliedFixers = [];
        foreach ($this->fixers as $fixer) {
            if ($this->processTokensByFixer($filePath, $tokens, $fixer)) {
                $appliedFixers[] = get_class($fixer);
            }
        }
        $contents = FileSystem::read($filePath);
        if ($appliedFixers === []) {
            return $contents;
        }
        $diff = $this->differ->diff($contents, $tokens->generateCode());
        // some fixer with feature overlap can null each other
        if ($diff === '') {
            return $contents;
        }
        return $tokens->generateCode();
    }
    /**
     * @param FixerInterface[] $fixers
     * @return FixerInterface[]
     */
    private function sortFixers(array $fixers): array
    {
        usort($fixers, static function (FixerInterface $firstFixer, FixerInterface $secondFixer): int {
            return $secondFixer->getPriority() <=> $firstFixer->getPriority();
        });
        return $fixers;
    }
    /**
     * @param Tokens<Token> $tokens
     * @return bool If fixer applied
     */
    private function processTokensByFixer(string $filePath, Tokens $tokens, FixerInterface $fixer): bool
    {
        if ($this->shouldSkip($filePath, $fixer, $tokens)) {
            return \false;
        }
        // show current fixer in --debug / -vvv
        if ($this->isDebug) {
            $this->easyCodingStandardStyle->writeln('     [fixer] ' . get_class($fixer));
        }
        try {
            $fixer->fix(new SplFileInfo($filePath), $tokens);
        } catch (Throwable $throwable) {
            throw new ShouldNotHappenException(sprintf('Fixing of "%s" file by "%s" failed: %s in file %s on line %d', $filePath, get_class($fixer), $throwable->getMessage(), $throwable->getFile(), $throwable->getLine()), $throwable->getCode(), $throwable);
        }
        if (!$tokens->isChanged()) {
            return \false;
        }
        $tokens->clearEmptyTokens();
        $tokens->clearChanged();
        return \true;
    }
    /**
     * @param Tokens<Token> $tokens
     */
    private function shouldSkip(string $filePath, FixerInterface $fixer, Tokens $tokens): bool
    {
        if ($this->skipper->shouldSkipElementAndFilePath($fixer, $filePath)) {
            return \true;
        }
        if (!$fixer->supports(new SplFileInfo($filePath))) {
            return \true;
        }
        return !$fixer->isCandidate($tokens);
    }
}
