<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\FixerRunner\Parser;

use ECSPrefix202610\Nette\Utils\FileSystem;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
final class FileToTokensParser
{
    /**
     * @return Tokens<Token>
     */
    public function parseFromFilePath(string $filePath): Tokens
    {
        $fileContents = FileSystem::read($filePath);
        return Tokens::fromCode($fileContents);
    }
}
