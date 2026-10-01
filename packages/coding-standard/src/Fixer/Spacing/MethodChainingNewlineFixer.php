<?php

declare (strict_types=1);
namespace Symplify\CodingStandard\Fixer\Spacing;

use Override;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use SplFileInfo;
use Symplify\CodingStandard\Fixer\AbstractSymplifyFixer;
use Symplify\CodingStandard\TokenAnalyzer\ChainMethodCallAnalyzer;
use Symplify\CodingStandard\TokenRunner\Analyzer\FixerAnalyzer\BlockFinder;
use Symplify\CodingStandard\TokenRunner\ValueObject\BlockInfo;
/**
 * @see \Symplify\CodingStandard\Tests\Fixer\Spacing\MethodChainingNewlineFixer\MethodChainingNewlineFixerTest
 */
final class MethodChainingNewlineFixer extends AbstractSymplifyFixer implements DeprecatedFixerInterface
{
    /**
     * @readonly
     * @var \PhpCsFixer\WhitespacesFixerConfig
     */
    private $whitespacesFixerConfig;
    /**
     * @readonly
     * @var \Symplify\CodingStandard\TokenRunner\Analyzer\FixerAnalyzer\BlockFinder
     */
    private $blockFinder;
    /**
     * @readonly
     * @var \Symplify\CodingStandard\TokenAnalyzer\ChainMethodCallAnalyzer
     */
    private $chainMethodCallAnalyzer;
    /**
     * @var string
     */
    private const ERROR_MESSAGE = 'Each chain method call must be on own line';
    public function __construct(WhitespacesFixerConfig $whitespacesFixerConfig, BlockFinder $blockFinder, ChainMethodCallAnalyzer $chainMethodCallAnalyzer)
    {
        $this->whitespacesFixerConfig = $whitespacesFixerConfig;
        $this->blockFinder = $blockFinder;
        $this->chainMethodCallAnalyzer = $chainMethodCallAnalyzer;
    }
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(self::ERROR_MESSAGE, []);
    }
    /**
     * @return list<string>
     */
    public function getSuccessorsNames(): array
    {
        return [];
    }
    /**
     * Must run before
     *
     * @see \PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer::getPriority()
     */
    #[Override]
    public function getPriority(): int
    {
        return 39;
    }
    /**
     * @param Tokens<Token> $tokens
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([\T_OBJECT_OPERATOR]);
    }
    /**
     * @param Tokens<Token> $tokens
     */
    public function fix(SplFileInfo $fileInfo, Tokens $tokens): void
    {
        // function arguments, function call parameters, lambda use()
        for ($index = 1, $count = count($tokens); $index < $count; ++$index) {
            $currentToken = $tokens[$index];
            if (!$currentToken->isGivenKind(\T_OBJECT_OPERATOR)) {
                continue;
            }
            if (!$this->shouldPrefixNewline($tokens, $index)) {
                continue;
            }
            $tokens->ensureWhitespaceAtIndex($index, 0, $this->whitespacesFixerConfig->getLineEnding());
            ++$index;
        }
    }
    /**
     * @param Tokens<Token> $tokens
     */
    private function shouldPrefixNewline(Tokens $tokens, int $objectOperatorIndex): bool
    {
        for ($i = $objectOperatorIndex; $i >= 0; --$i) {
            /** @var Token $currentToken */
            $currentToken = $tokens[$i];
            if ($currentToken->equals(')')) {
                return $this->shouldBracketPrefix($tokens, $i, $objectOperatorIndex);
            }
            if ($currentToken->isGivenKind([\T_NEW, \T_VARIABLE])) {
                return \false;
            }
            if ($currentToken->getContent() === '(') {
                return \false;
            }
        }
        return \false;
    }
    /**
     * @param Tokens<Token> $tokens
     */
    private function isDoubleBracket(Tokens $tokens, int $position): bool
    {
        /** @var int $nextTokenPosition */
        $nextTokenPosition = $tokens->getNextNonWhitespace($position);
        /** @var Token $nextToken */
        $nextToken = $tokens[$nextTokenPosition];
        return $nextToken->getContent() === ')';
    }
    /**
     * Matches e.g.: - app([ ])->some()
     *
     * @param Tokens<Token> $tokens
     */
    private function isPrecededByOpenedCallInAnotherBracket(Tokens $tokens, int $position): bool
    {
        $blockInfo = $this->blockFinder->findInTokensByEdge($tokens, $position);
        if (!$blockInfo instanceof BlockInfo) {
            return \false;
        }
        return $tokens->isPartialCodeMultiline($blockInfo->getStart(), $blockInfo->getEnd());
    }
    /**
     * @param Tokens<Token> $tokens
     */
    private function shouldBracketPrefix(Tokens $tokens, int $position, int $objectOperatorIndex): bool
    {
        if ($this->isDoubleBracket($tokens, $position)) {
            return \false;
        }
        if ($this->chainMethodCallAnalyzer->isPartOfBooleanOrComparison($tokens, $objectOperatorIndex)) {
            return \false;
        }
        if ($this->chainMethodCallAnalyzer->isInsideControlCondition($tokens, $objectOperatorIndex)) {
            return \false;
        }
        if ($this->chainMethodCallAnalyzer->isShortNoArgTrailingMethod($tokens, $objectOperatorIndex)) {
            return \false;
        }
        if ($this->chainMethodCallAnalyzer->isPartOfMethodCallOrArray($tokens, $position)) {
            return \false;
        }
        if ($this->chainMethodCallAnalyzer->isPrecededByFuncCall($tokens, $position)) {
            return \false;
        }
        if ($this->isPrecededByOpenedCallInAnotherBracket($tokens, $position)) {
            return \false;
        }
        // all good, there is a newline
        return !$tokens->isPartialCodeMultiline($position, $objectOperatorIndex);
    }
}
