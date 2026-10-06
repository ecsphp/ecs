<?php

declare (strict_types=1);
namespace Symplify\CodingStandard\TokenAnalyzer;

use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
final class ChainMethodCallAnalyzer
{
    /**
     * @readonly
     * @var \Symplify\CodingStandard\TokenAnalyzer\NewlineAnalyzer
     */
    private $newlineAnalyzer;
    /**
     * @var int
     */
    private $bracketNesting = 0;
    public function __construct(\Symplify\CodingStandard\TokenAnalyzer\NewlineAnalyzer $newlineAnalyzer)
    {
        $this->newlineAnalyzer = $newlineAnalyzer;
    }
    /**
     * Matches e.g: return app()->some(), app()->some(), (clone app)->some()
     *
     * @param Tokens<Token> $tokens
     */
    public function isPrecededByFuncCall(Tokens $tokens, int $position): bool
    {
        for ($i = $position; $i >= 0; --$i) {
            /** @var Token $currentToken */
            $currentToken = $tokens[$i];
            if ($currentToken->getContent() === 'clone') {
                return \true;
            }
            if ($currentToken->getContent() === '(') {
                return $this->newlineAnalyzer->doesContentBeforeBracketRequireNewline($tokens, $i);
            }
            if ($this->newlineAnalyzer->isNewlineToken($currentToken)) {
                return \false;
            }
        }
        return \false;
    }
    /**
     * Matches e.g. someMethod($this->some()->method()), [$this->some()->method()]
     *
     * @param Tokens<Token> $tokens
     */
    public function isPartOfMethodCallOrArray(Tokens $tokens, int $position): bool
    {
        $this->bracketNesting = 0;
        for ($i = $position; $i >= 0; --$i) {
            /** @var Token $currentToken */
            $currentToken = $tokens[$i];
            // break
            if ($this->newlineAnalyzer->isNewlineToken($currentToken)) {
                return \false;
            }
            if ($this->isBreakingChar($currentToken)) {
                return \true;
            }
            if ($this->shouldBreakOnBracket($currentToken)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * Matches e.g: $x->foo()->yes() || ..., $x->foo()->getName() === Bar::class
     *
     * @param Tokens<Token> $tokens
     */
    public function isPartOfBooleanOrComparison(Tokens $tokens, int $position): bool
    {
        return $this->hasOperatorInDirection($tokens, $position, -1) || $this->hasOperatorInDirection($tokens, $position, 1);
    }
    /**
     * @param Tokens<Token> $tokens
     */
    private function hasOperatorInDirection(Tokens $tokens, int $position, int $step): bool
    {
        $bracketNesting = 0;
        for ($i = $position + $step; isset($tokens[$i]); $i += $step) {
            $currentToken = $tokens[$i];
            $content = $currentToken->getContent();
            // entering a nested bracket
            if ($step < 0 && ($content === ')' || $content === ']') || $step > 0 && ($content === '(' || $content === '[')) {
                ++$bracketNesting;
                continue;
            }
            // leaving a nested bracket, or hitting the enclosing one = statement boundary
            if ($step < 0 && ($content === '(' || $content === '[') || $step > 0 && ($content === ')' || $content === ']')) {
                if ($bracketNesting === 0) {
                    return \false;
                }
                --$bracketNesting;
                continue;
            }
            if ($bracketNesting !== 0) {
                continue;
            }
            if (in_array($content, [';', '{', '}'], \true)) {
                return \false;
            }
            if ($this->isBooleanOrComparisonToken($currentToken)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * Matches e.g: if ($x->isInteger()->yes()), while ($x->next()->valid())
     *
     * @param Tokens<Token> $tokens
     */
    public function isInsideControlCondition(Tokens $tokens, int $position): bool
    {
        $bracketNesting = 0;
        for ($i = $position; $i >= 0; --$i) {
            /** @var Token $currentToken */
            $currentToken = $tokens[$i];
            $content = $currentToken->getContent();
            if ($content === ')' || $content === ']') {
                ++$bracketNesting;
                continue;
            }
            if ($content === '(' || $content === '[') {
                if ($bracketNesting !== 0) {
                    --$bracketNesting;
                    continue;
                }
                if ($content === '[') {
                    return \false;
                }
                $beforeIndex = $tokens->getPrevMeaningfulToken($i);
                if ($beforeIndex === null) {
                    return \false;
                }
                return $tokens[$beforeIndex]->isGivenKind([\T_IF, \T_ELSEIF, \T_WHILE, \T_SWITCH]);
            }
            if ($bracketNesting === 0 && in_array($content, [';', '{', '}'], \true)) {
                return \false;
            }
        }
        return \false;
    }
    /**
     * Matches e.g: ->yes(), ->no(), ->maybe() - short predicate-like accessors
     *
     * @param Tokens<Token> $tokens
     */
    public function isShortNoArgTrailingMethod(Tokens $tokens, int $objectOperatorIndex): bool
    {
        $methodNameIndex = $tokens->getNextMeaningfulToken($objectOperatorIndex);
        if ($methodNameIndex === null) {
            return \false;
        }
        /** @var Token $methodNameToken */
        $methodNameToken = $tokens[$methodNameIndex];
        if (!$methodNameToken->isGivenKind(\T_STRING)) {
            return \false;
        }
        if (strlen($methodNameToken->getContent()) > 5) {
            return \false;
        }
        $openIndex = $tokens->getNextMeaningfulToken($methodNameIndex);
        if ($openIndex === null || $tokens[$openIndex]->getContent() !== '(') {
            return \false;
        }
        $closeIndex = $tokens->getNextMeaningfulToken($openIndex);
        return $closeIndex !== null && $tokens[$closeIndex]->getContent() === ')';
    }
    private function isBooleanOrComparisonToken(Token $token): bool
    {
        if ($token->isGivenKind([\T_BOOLEAN_AND, \T_BOOLEAN_OR, \T_LOGICAL_AND, \T_LOGICAL_OR, \T_LOGICAL_XOR, \T_IS_EQUAL, \T_IS_NOT_EQUAL, \T_IS_IDENTICAL, \T_IS_NOT_IDENTICAL, \T_IS_SMALLER_OR_EQUAL, \T_IS_GREATER_OR_EQUAL, \T_SPACESHIP])) {
            return \true;
        }
        return $token->getContent() === '<' || $token->getContent() === '>';
    }
    private function isBreakingChar(Token $currentToken): bool
    {
        if ($currentToken->isGivenKind([CT::T_ARRAY_SQUARE_BRACE_OPEN, \T_ARRAY, \T_DOUBLE_COLON])) {
            return \true;
        }
        if ($currentToken->getContent() === '[') {
            return \true;
        }
        return $currentToken->getContent() === '.';
    }
    private function shouldBreakOnBracket(Token $token): bool
    {
        if ($token->getContent() === ')') {
            --$this->bracketNesting;
            return \false;
        }
        if ($token->getContent() === '(') {
            if ($this->bracketNesting !== 0) {
                ++$this->bracketNesting;
                return \false;
            }
            return \true;
        }
        return \false;
    }
}
