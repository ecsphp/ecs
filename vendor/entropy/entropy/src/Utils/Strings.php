<?php

declare (strict_types=1);
namespace ECSPrefix202610\Entropy\Utils;

use ECSPrefix202610\Entropy\Attribute\RelatedTest;
use ECSPrefix202610\Entropy\Tests\Utils\StringsTest;
/**
 * @api to be used outside
 * @see \Entropy\Tests\Utils\StringsTest
 */
final class Strings
{
    public static function webalize(string $text): string
    {
        $text = (string) preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
        $text = trim($text, '-');
        return strtolower($text);
    }
}
