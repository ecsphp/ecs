<?php

declare (strict_types=1);
namespace Symplify\CodingStandard\Fixer\ConstantNotation;

use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;
use Symplify\CodingStandard\Fixer\AbstractSymplifyFixer;
/**
 * @see \Symplify\CodingStandard\Tests\Fixer\ConstantNotation\RemoveSlashFromNativeConstantFixer\RemoveSlashFromNativeConstantFixerTest
 */
final class RemoveSlashFromNativeConstantFixer extends AbstractSymplifyFixer
{
    /**
     * @var string
     */
    private const ERROR_MESSAGE = 'Remove leading backslash from "true", "false" and "null".';
    /**
     * @var string[]
     */
    private const KEYWORDS = ['true', 'false', 'null'];
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(self::ERROR_MESSAGE, [new CodeSample("<?php\nreturn \\true;\n")]);
    }
    /**
     * @param Tokens<Token> $tokens
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(\T_NS_SEPARATOR);
    }
    /**
     * @param Tokens<Token> $tokens
     */
    public function fix(SplFileInfo $fileInfo, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(\T_NS_SEPARATOR)) {
                continue;
            }
            $nextIndex = $tokens->getNextMeaningfulToken($index);
            if ($nextIndex === null) {
                continue;
            }
            if (!$tokens[$nextIndex]->isGivenKind(\T_STRING)) {
                continue;
            }
            if (!in_array(strtolower($tokens[$nextIndex]->getContent()), self::KEYWORDS, \true)) {
                continue;
            }
            // the keyword must be a standalone name, not a namespace segment like "Foo\true" or "\true\Bar"
            $prevIndex = $tokens->getPrevMeaningfulToken($index);
            if ($prevIndex !== null && $tokens[$prevIndex]->isGivenKind([\T_STRING, \T_NS_SEPARATOR])) {
                continue;
            }
            $afterKeywordIndex = $tokens->getNextMeaningfulToken($nextIndex);
            if ($afterKeywordIndex !== null && $tokens[$afterKeywordIndex]->isGivenKind(\T_NS_SEPARATOR)) {
                continue;
            }
            $tokens->clearAt($index);
        }
    }
}
