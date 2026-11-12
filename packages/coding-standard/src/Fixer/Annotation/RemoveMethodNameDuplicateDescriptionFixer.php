<?php

declare (strict_types=1);
namespace Symplify\CodingStandard\Fixer\Annotation;

use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;
use Symplify\CodingStandard\Fixer\AbstractSymplifyFixer;
use Symplify\CodingStandard\Fixer\Naming\MethodNameResolver;
use Symplify\CodingStandard\TokenRunner\Traverser\TokenReverser;
use Symplify\CodingStandard\Utils\Regex;
/**
 * @see \Symplify\CodingStandard\Tests\Fixer\Annotation\RemoveMethodNameDuplicateDescriptionFixer\RemoveMethodNameDuplicateDescriptionFixerTest
 */
final class RemoveMethodNameDuplicateDescriptionFixer extends AbstractSymplifyFixer
{
    /**
     * @readonly
     * @var \Symplify\CodingStandard\TokenRunner\Traverser\TokenReverser
     */
    private $tokenReverser;
    /**
     * @var string
     */
    private const ERROR_MESSAGE = 'Remove docblock descriptions which duplicate their method name';
    /**
     * @readonly
     * @var \Symplify\CodingStandard\Fixer\Naming\MethodNameResolver
     */
    private $methodNameResolver;
    public function __construct(TokenReverser $tokenReverser)
    {
        $this->tokenReverser = $tokenReverser;
        $this->methodNameResolver = new MethodNameResolver();
    }
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(self::ERROR_MESSAGE, []);
    }
    /**
     * @param Tokens<Token> $tokens
     */
    public function isCandidate(Tokens $tokens): bool
    {
        if (!$tokens->isTokenKindFound(\T_FUNCTION)) {
            return \false;
        }
        return $tokens->isAnyTokenKindsFound([\T_DOC_COMMENT, \T_COMMENT]);
    }
    /**
     * @param Tokens<Token> $tokens
     */
    public function fix(SplFileInfo $fileInfo, Tokens $tokens): void
    {
        $reversedTokens = $this->tokenReverser->reverse($tokens);
        foreach ($reversedTokens as $index => $token) {
            if (!$token->isGivenKind([\T_DOC_COMMENT, \T_COMMENT])) {
                continue;
            }
            $methodName = $this->methodNameResolver->resolve($tokens, $index);
            if ($methodName === null) {
                continue;
            }
            // skip if not setter or getter
            $originalDocContent = $token->getContent();
            $hasChanged = \false;
            $docblockLines = explode("\n", $originalDocContent);
            foreach ($docblockLines as $key => $docblockLine) {
                // drop articles, so "Get the API helper" still duplicates getApiHelper()
                $docblockLine = Regex::replace($docblockLine, '#\b(?:a|an|the)\b#i', '');
                // drop 3rd person verb "s", so "Contacts a user" still duplicates contactUser(),
                // but keep the original too, so "has" still duplicates has()
                $thirdPersonlessDocblockLine = Regex::replace($docblockLine, '#^([\s*]*\w+?)s\b#', '$1');
                if (!$this->isDuplicateLine($docblockLine, $methodName) && !$this->isDuplicateLine($thirdPersonlessDocblockLine, $methodName)) {
                    continue;
                }
                $hasChanged = \true;
                unset($docblockLines[$key]);
            }
            if (!$hasChanged) {
                continue;
            }
            $tokens[$index] = new Token([\T_DOC_COMMENT, implode("\n", $docblockLines)]);
        }
    }
    private function isDuplicateLine(string $docblockLine, string $methodName): bool
    {
        $spacelessDocblockLine = Regex::replace($docblockLine, '#[\s\n]+#', '');
        // ignore trailing sentence punctuation, e.g. "Set name." duplicates setName()
        $spacelessDocblockLine = rtrim($spacelessDocblockLine, '.!');
        return $this->isDuplicateDescription($spacelessDocblockLine, $methodName);
    }
    private function isDuplicateDescription(string $spacelessDocblockLine, string $methodName): bool
    {
        $description = strtolower(ltrim($spacelessDocblockLine, '*'));
        $methodName = strtolower($methodName);
        if ($description === $methodName) {
            return \true;
        }
        // getter/setter verb mix-up, e.g. "Get results" description on setResults()
        $descriptionNoun = $this->resolveGetterSetterNoun($description);
        return $descriptionNoun !== null && $descriptionNoun === $this->resolveGetterSetterNoun($methodName);
    }
    private function resolveGetterSetterNoun(string $value): ?string
    {
        foreach (['get', 'set'] as $prefix) {
            if (strncmp($value, $prefix, strlen($prefix)) === 0 && strlen($value) > strlen($prefix)) {
                return (string) substr($value, strlen($prefix));
            }
        }
        return null;
    }
}
