<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\Skipper\SkipCriteriaResolver;

use Symplify\EasyCodingStandard\DependencyInjection\SimpleParameterProvider;
use Symplify\EasyCodingStandard\ValueObject\Option;
final class SkippedMessagesResolver
{
    /**
     * @var array<string, string[]|null>
     */
    private $skippedMessages = [];
    /**
     * @return array<string, string[]|null>
     */
    public function resolve(): array
    {
        if ($this->skippedMessages !== []) {
            return $this->skippedMessages;
        }
        $skip = SimpleParameterProvider::getArrayParameter(Option::SKIP);
        foreach ($skip as $key => $value) {
            // e.g. [SomeClass::class] → shift values to [SomeClass::class => null]
            if (is_int($key)) {
                $key = $value;
                $value = null;
            }
            if (!is_string($key)) {
                continue;
            }
            if (substr_count($key, ' ') === 0) {
                continue;
            }
            $this->skippedMessages[$key] = $value;
        }
        return $this->skippedMessages;
    }
}
