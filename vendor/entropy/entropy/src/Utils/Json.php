<?php

declare (strict_types=1);
namespace ECSPrefix202610\Entropy\Utils;

use ECSPrefix202610\Entropy\Attribute\RelatedTest;
use ECSPrefix202610\Entropy\Tests\Utils\JsonTest;
use ECSPrefix202610\Entropy\Validation\Assert;
/**
 * @api to be used outside
 * @see \Entropy\Tests\Utils\JsonTest
 */
final class Json
{
    /**
     * @param mixed $data
     */
    public static function encode($data): string
    {
        $encoded = json_encode($data, \JSON_PRETTY_PRINT);
        if (json_last_error() !== \JSON_ERROR_NONE) {
            throw new \Exception(json_last_error_msg());
        }
        Assert::string($encoded);
        return $encoded . \PHP_EOL;
    }
    /**
     * @return array<string, mixed>
     */
    public static function decode(string $json): array
    {
        $decoded = json_decode($json, \true, 512, 0);
        if (json_last_error() !== \JSON_ERROR_NONE) {
            throw new \Exception(json_last_error_msg());
        }
        Assert::isArray($decoded);
        return $decoded;
    }
}
