<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix202610\Symfony\Component\Process\Messenger;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class RunProcessMessage
{
    /**
     * @readonly
     * @var mixed[]
     */
    public $command;
    /**
     * @readonly
     * @var string|null
     */
    public $cwd;
    /**
     * @readonly
     * @var mixed[]|null
     */
    public $env;
    /**
     * @readonly
     * @var mixed
     */
    public $input = null;
    /**
     * @readonly
     * @var float|null
     */
    public $timeout = 60.0;
    /**
     * @var string|null
     */
    public $commandLine;
    /**
     * @param mixed $input
     */
    public function __construct(array $command, ?string $cwd = null, ?array $env = null, $input = null, ?float $timeout = 60.0)
    {
        $this->command = $command;
        $this->cwd = $cwd;
        $this->env = $env;
        $this->input = $input;
        $this->timeout = $timeout;
    }
    public function __toString(): string
    {
        return $this->commandLine ?? implode(' ', $this->command);
    }
    /**
     * Create a process message instance that will instantiate a Process using the fromShellCommandline method.
     *
     * @see Process::fromShellCommandline
     * @param mixed $input
     */
    public static function fromShellCommandline(string $command, ?string $cwd = null, ?array $env = null, $input = null, ?float $timeout = 60): self
    {
        $message = new self([], $cwd, $env, $input, $timeout);
        $message->commandLine = $command;
        return $message;
    }
}
