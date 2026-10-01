<?php

declare (strict_types=1);
namespace ECSPrefix202610\Entropy\Console\ValueObject;

use ECSPrefix202610\Entropy\Validation\Assert;
final class ArgumentsAndOptions
{
    /**
     * @var Argument[]
     */
    private $arguments;
    /**
     * @var Option[]
     */
    private $options;
    /**
     * @param Argument[] $arguments
     * @param Option[] $options
     */
    public function __construct(array $arguments, array $options)
    {
        $this->arguments = $arguments;
        $this->options = $options;
        Assert::allIsInstanceOf($arguments, Argument::class);
        Assert::allIsInstanceOf($options, Option::class);
    }
    /**
     * @return Argument[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
    /**
     * @return Option[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
