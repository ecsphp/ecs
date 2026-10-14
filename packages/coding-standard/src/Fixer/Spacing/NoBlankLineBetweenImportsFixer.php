<?php

declare (strict_types=1);
namespace Symplify\CodingStandard\Fixer\Spacing;

use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use PhpCsFixer\WhitespacesFixerConfig;
use SplFileInfo;
use Symplify\CodingStandard\Fixer\AbstractSymplifyFixer;
/**
 * @see \Symplify\CodingStandard\Tests\Fixer\Spacing\NoBlankLineBetweenImportsFixer\NoBlankLineBetweenImportsFixerTest
 */
final class NoBlankLineBetweenImportsFixer extends AbstractSymplifyFixer implements WhitespacesAwareFixerInterface
{
    /**
     * @var string
     */
    private const ERROR_MESSAGE = 'There must be no blank line between import "use" statements.';
    /**
     * @var \PhpCsFixer\WhitespacesFixerConfig
     */
    private $whitespacesFixerConfig;
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(self::ERROR_MESSAGE, [new CodeSample("<?php\nuse Foo\\Bar;\n\nuse Foo\\Baz;\n")]);
    }
    // run after OrderedImportsFixer, before BlankLineBetweenImportGroupsFixer
    public function getPriority(): int
    {
        return -35;
    }
    /**
     * @param Tokens<Token> $tokens
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_USE);
    }
    public function setWhitespacesConfig(WhitespacesFixerConfig $whitespacesFixerConfig): void
    {
        $this->whitespacesFixerConfig = $whitespacesFixerConfig;
    }
    /**
     * @param Tokens<Token> $tokens
     */
    public function fix(SplFileInfo $fileInfo, Tokens $tokens): void
    {
        $tokensAnalyzer = new TokensAnalyzer($tokens);
        $useIndexes = $tokensAnalyzer->getImportUseIndexes();
        $lineEnding = $this->whitespacesFixerConfig->getLineEnding();
        // start at the 2nd use, so the blank line after the namespace stays untouched
        for ($i = 1, $count = count($useIndexes); $i < $count; ++$i) {
            $useIndex = $useIndexes[$i];
            $previousIndex = $tokens->getPrevMeaningfulToken($useIndex);
            if ($previousIndex === null || !$tokens[$previousIndex]->equals(';')) {
                continue;
            }
            // keep the blank line between different import groups, e.g. class and "use function"
            if ($this->resolveImportType($tokens, $useIndex) !== $this->resolveImportType($tokens, $useIndexes[$i - 1])) {
                continue;
            }
            $whitespaceIndex = $useIndex - 1;
            if (!$tokens[$whitespaceIndex]->isWhitespace()) {
                continue;
            }
            if (substr_count($tokens[$whitespaceIndex]->getContent(), "\n") < 2) {
                continue;
            }
            $tokens[$whitespaceIndex] = new Token([\T_WHITESPACE, $lineEnding]);
        }
    }
    /**
     * @param Tokens<Token> $tokens
     */
    private function resolveImportType(Tokens $tokens, int $useIndex): string
    {
        $nextIndex = $tokens->getNextMeaningfulToken($useIndex);
        if ($nextIndex === null) {
            return 'class';
        }
        if ($tokens[$nextIndex]->isGivenKind(CT::T_FUNCTION_IMPORT)) {
            return 'function';
        }
        if ($tokens[$nextIndex]->isGivenKind(CT::T_CONST_IMPORT)) {
            return 'const';
        }
        return 'class';
    }
}
