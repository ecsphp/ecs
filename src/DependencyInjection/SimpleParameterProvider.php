<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\DependencyInjection;

use ECSPrefix202609\Webmozart\Assert\Assert;
final class SimpleParameterProvider
{
    /**
     * @var array<string, mixed>
     */
    private static $parameters = [];
    public static function reset(): void
    {
        self::$parameters = [];
    }
    /**
     * @param mixed $value
     */
    public static function addParameter(string $key, $value): void
    {
        if (is_array($value)) {
            $mergedParameters = array_merge(self::$parameters[$key] ?? [], $value);
            self::$parameters[$key] = $mergedParameters;
        } else {
            self::$parameters[$key][] = $value;
        }
    }
    /**
     * @param mixed $value
     */
    public static function setParameter(string $key, $value): void
    {
        self::$parameters[$key] = $value;
    }
    /**
     * @return mixed[]
     */
    public static function getArrayParameter(string $key): array
    {
        $parameter = self::$parameters[$key] ?? [];
        Assert::isArray($parameter);
        $arrayIsListFunction = function (array $array): bool {
            if (function_exists('array_is_list')) {
                return array_is_list($array);
            }
            if ($array === []) {
                return \true;
            }
            $current_key = 0;
            foreach ($array as $key => $noop) {
                if ($key !== $current_key) {
                    return \false;
                }
                ++$current_key;
            }
            return \true;
        };
        if ($arrayIsListFunction($parameter)) {
            // remove duplicates
            return array_values(array_unique($parameter));
        }
        return $parameter;
    }
    public static function getStringParameter(string $key): string
    {
        return self::$parameters[$key];
    }
    public static function getIntParameter(string $key): int
    {
        return self::$parameters[$key];
    }
    public static function getBoolParameter(string $key): bool
    {
        return self::$parameters[$key];
    }
    /**
     * For cache invalidation
     */
    public static function hash(): string
    {
        return sha1(serialize(self::$parameters));
    }
}
