<?php

declare (strict_types=1);
namespace ECSPrefix202610\Entropy\Console\ValueObject;

final class Argument
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string|null
     */
    private $description;
    /**
     * @var bool
     */
    private $acceptsMultipleValues;
    public function __construct(string $name, ?string $description = null, bool $acceptsMultipleValues = \false)
    {
        $this->name = $name;
        $this->description = $description;
        $this->acceptsMultipleValues = $acceptsMultipleValues;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function doesAcceptMultipleValues(): bool
    {
        return $this->acceptsMultipleValues;
    }
}
