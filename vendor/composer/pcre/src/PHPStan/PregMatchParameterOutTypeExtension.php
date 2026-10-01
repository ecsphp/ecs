<?php

declare (strict_types=1);
namespace ECSPrefix202610\Composer\Pcre\PHPStan;

use ECSPrefix202610\Composer\Pcre\Preg;
use ECSPrefix202610\PhpParser\Node\Expr\StaticCall;
use ECSPrefix202610\PHPStan\Analyser\Scope;
use ECSPrefix202610\PHPStan\Reflection\MethodReflection;
use ECSPrefix202610\PHPStan\Reflection\ParameterReflection;
use ECSPrefix202610\PHPStan\TrinaryLogic;
use ECSPrefix202610\PHPStan\Type\Php\RegexArrayShapeMatcher;
use ECSPrefix202610\PHPStan\Type\StaticMethodParameterOutTypeExtension;
use ECSPrefix202610\PHPStan\Type\Type;
final class PregMatchParameterOutTypeExtension implements StaticMethodParameterOutTypeExtension
{
    /**
     * @var RegexArrayShapeMatcher
     */
    private $regexShapeMatcher;
    public function __construct(RegexArrayShapeMatcher $regexShapeMatcher)
    {
        $this->regexShapeMatcher = $regexShapeMatcher;
    }
    public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        return $methodReflection->getDeclaringClass()->getName() === Preg::class && in_array($methodReflection->getName(), ['match', 'isMatch', 'matchStrictGroups', 'isMatchStrictGroups', 'matchAll', 'isMatchAll', 'matchAllStrictGroups', 'isMatchAllStrictGroups'], \true) && $parameter->getName() === 'matches';
    }
    public function getParameterOutTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type
    {
        $args = $methodCall->getArgs();
        $patternArg = $args[0] ?? null;
        $matchesArg = $args[2] ?? null;
        $flagsArg = $args[3] ?? null;
        if ($patternArg === null || $matchesArg === null) {
            return null;
        }
        $flagsType = PregMatchFlags::getType($flagsArg, $scope);
        if ($flagsType === null) {
            return null;
        }
        if (stripos($methodReflection->getName(), 'matchAll') !== \false) {
            return $this->regexShapeMatcher->matchAllExpr($patternArg->value, $flagsType, TrinaryLogic::createMaybe(), $scope);
        }
        return $this->regexShapeMatcher->matchExpr($patternArg->value, $flagsType, TrinaryLogic::createMaybe(), $scope);
    }
}
