<?php

declare (strict_types=1);
namespace ECSPrefix202609\PhpParser\Node\Expr;

use ECSPrefix202609\PhpParser\Node;
use ECSPrefix202609\PhpParser\Node\Arg;
use ECSPrefix202609\PhpParser\Node\ArgPlaceholder;
use ECSPrefix202609\PhpParser\Node\Expr;
use ECSPrefix202609\PhpParser\Node\Identifier;
use ECSPrefix202609\PhpParser\Node\VariadicPlaceholder;
class NullsafeMethodCall extends CallLike
{
    /** @var Expr Variable holding object */
    public $var;
    /** @var Identifier|Expr Method name */
    public $name;
    /** @var array<Arg|VariadicPlaceholder|ArgPlaceholder> Arguments */
    public $args;
    /**
     * Constructs a nullsafe method call node.
     *
     * @param Expr $var Variable holding object
     * @param string|Identifier|Expr $name Method name
     * @param array<Arg|VariadicPlaceholder|ArgPlaceholder> $args Arguments
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(Expr $var, $name, array $args = [], array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->var = $var;
        $this->name = \is_string($name) ? new Identifier($name) : $name;
        $this->args = $args;
    }
    public function getSubNodeNames(): array
    {
        return ['var', 'name', 'args'];
    }
    public function getType(): string
    {
        return 'Expr_NullsafeMethodCall';
    }
    public function getRawArgs(): array
    {
        return $this->args;
    }
}
