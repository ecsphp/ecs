<?php

declare (strict_types=1);
namespace Symplify\CodingStandard\Fixer\Commenting;

use Override;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use Symplify\CodingStandard\Utils\Regex;
/**
 * @see \Symplify\CodingStandard\Tests\Fixer\Commenting\RemoveParamDescriptionDuplicateNameFixer\RemoveParamDescriptionDuplicateNameFixerTest
 */
final class RemoveParamDescriptionDuplicateNameFixer extends \Symplify\CodingStandard\Fixer\Commenting\AbstractDocBlockFixer
{
    /**
     * @var string
     */
    private const ERROR_MESSAGE = 'Remove a @param description that only duplicates the parameter name';
    /**
     * @see https://regex101.com/r/lYFbmr/1
     * @var string
     */
    private const PARAM_DESCRIPTION_REGEX = '#(?<keep>@(?:psalm-|phpstan-)?param\s+.+?\s+\$(?<name>\w+))[ \t]+(?<description>\S[^\r\n]*?)(?<trail>[ \t]*(?:\*/)?[ \t]*)$#m';
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(self::ERROR_MESSAGE, []);
    }
    // run before NoSuperfluousPhpdocTagsFixer (priority 6) so it strips the description
    // first, letting the now-bare @param tag be dropped as superfluous
    #[Override]
    public function getPriority(): int
    {
        return 7;
    }
    /**
     * @param Tokens<Token> $tokens
     */
    protected function processDocContent(string $docContent, Tokens $tokens, int $position): string
    {
        return Regex::replace($docContent, self::PARAM_DESCRIPTION_REGEX, static function (array $match): string {
            if (!self::isDuplicateName((string) $match['description'], (string) $match['name'])) {
                return (string) $match[0];
            }
            return $match['keep'] . $match['trail'];
        });
    }
    private static function isDuplicateName(string $description, string $name): bool
    {
        $normalizedDescription = strtolower(Regex::replace(rtrim($description, '.!'), '#\s+#', ''));
        return $normalizedDescription === strtolower($name);
    }
}
