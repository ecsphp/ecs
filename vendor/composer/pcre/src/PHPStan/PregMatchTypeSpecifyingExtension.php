<?php

declare (strict_types=1);
namespace ECSPrefix202610\Composer\Pcre\PHPStan;

use ECSPrefix202610\Composer\Pcre\Preg;
use ECSPrefix202610\PhpParser\Node\Expr\StaticCall;
use ECSPrefix202610\PHPStan\Analyser\Scope;
use ECSPrefix202610\PHPStan\Analyser\SpecifiedTypes;
use ECSPrefix202610\PHPStan\Analyser\TypeSpecifier;
use ECSPrefix202610\PHPStan\Analyser\TypeSpecifierAwareExtension;
use ECSPrefix202610\PHPStan\Analyser\TypeSpecifierContext;
use ECSPrefix202610\PHPStan\Reflection\MethodReflection;
use ECSPrefix202610\PHPStan\TrinaryLogic;
use ECSPrefix202610\PHPStan\Type\Constant\ConstantArrayType;
use ECSPrefix202610\PHPStan\Type\Php\RegexArrayShapeMatcher;
use ECSPrefix202610\PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use ECSPrefix202610\PHPStan\Type\TypeCombinator;
use ECSPrefix202610\PHPStan\Type\Type;
final class PregMatchTypeSpecifyingExtension implements StaticMethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    /**
     * @var TypeSpecifier
     */
    private $typeSpecifier;
    /**
     * @var RegexArrayShapeMatcher
     */
    private $regexShapeMatcher;
    public function __construct(RegexArrayShapeMatcher $regexShapeMatcher)
    {
        $this->regexShapeMatcher = $regexShapeMatcher;
    }
    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }
    public function getClass(): string
    {
        return Preg::class;
    }
    public function isStaticMethodSupported(MethodReflection $methodReflection, StaticCall $node, TypeSpecifierContext $context): bool
    {
        return in_array($methodReflection->getName(), ['match', 'isMatch', 'matchStrictGroups', 'isMatchStrictGroups', 'matchAll', 'isMatchAll', 'matchAllStrictGroups', 'isMatchAllStrictGroups'], \true) && !$context->null();
    }
    public function specifyTypes(MethodReflection $methodReflection, StaticCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $args = $node->getArgs();
        $patternArg = $args[0] ?? null;
        $subjectArg = $args[1] ?? null;
        $matchesArg = $args[2] ?? null;
        $flagsArg = $args[3] ?? null;
        $subjectTypes = new SpecifiedTypes();
        if ($patternArg === null) {
            return $subjectTypes;
        }
        if ($subjectArg !== null && $context->true() && $scope->getType($subjectArg->value)->isString()->yes()) {
            $subjectType = $this->regexShapeMatcher->matchSubjectExpr($patternArg->value, $scope);
            if ($subjectType !== null) {
                $subjectTypes = $this->typeSpecifier->create($subjectArg->value, $subjectType, $context, $scope)->setRootExpr($node);
            }
        }
        if ($matchesArg === null) {
            return $subjectTypes;
        }
        $flagsType = PregMatchFlags::getType($flagsArg, $scope);
        if ($flagsType === null) {
            return $subjectTypes;
        }
        if (stripos($methodReflection->getName(), 'matchAll') !== \false) {
            $matchedType = $this->regexShapeMatcher->matchAllExpr($patternArg->value, $flagsType, TrinaryLogic::createFromBoolean($context->true()), $scope);
        } else {
            $matchedType = $this->regexShapeMatcher->matchExpr($patternArg->value, $flagsType, TrinaryLogic::createFromBoolean($context->true()), $scope);
        }
        if ($matchedType === null) {
            return $subjectTypes;
        }
        if (in_array($methodReflection->getName(), ['matchStrictGroups', 'isMatchStrictGroups', 'matchAllStrictGroups', 'isMatchAllStrictGroups'], \true)) {
            $matchedType = PregMatchFlags::removeNullFromMatches($matchedType);
        }
        $overwrite = \false;
        if ($context->false()) {
            $overwrite = \true;
            $context = $context->negate();
        }
        $specifiedTypes = $this->typeSpecifier->create($matchesArg->value, $matchedType, $context, $scope)->setRootExpr($node);
        return $subjectTypes->unionWith($overwrite ? $specifiedTypes->setAlwaysOverwriteTypes() : $specifiedTypes);
    }
}
