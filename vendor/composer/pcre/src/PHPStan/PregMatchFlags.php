<?php

declare (strict_types=1);
namespace ECSPrefix202610\Composer\Pcre\PHPStan;

use ECSPrefix202610\PHPStan\Analyser\Scope;
use ECSPrefix202610\PHPStan\Type\ArrayType;
use ECSPrefix202610\PHPStan\Type\Constant\ConstantArrayType;
use ECSPrefix202610\PHPStan\Type\Constant\ConstantIntegerType;
use ECSPrefix202610\PHPStan\Type\IntersectionType;
use ECSPrefix202610\PHPStan\Type\TypeCombinator;
use ECSPrefix202610\PHPStan\Type\Type;
use ECSPrefix202610\PhpParser\Node\Arg;
use ECSPrefix202610\PHPStan\Type\Php\RegexArrayShapeMatcher;
use ECSPrefix202610\PHPStan\Type\TypeTraverser;
use ECSPrefix202610\PHPStan\Type\UnionType;
final class PregMatchFlags
{
    public static function getType(?Arg $flagsArg, Scope $scope): ?Type
    {
        if ($flagsArg === null) {
            return new ConstantIntegerType(\PREG_UNMATCHED_AS_NULL);
        }
        $flagsType = $scope->getType($flagsArg->value);
        $constantScalars = $flagsType->getConstantScalarValues();
        if ($constantScalars === []) {
            return null;
        }
        $internalFlagsTypes = [];
        foreach ($flagsType->getConstantScalarValues() as $constantScalarValue) {
            if (!is_int($constantScalarValue)) {
                return null;
            }
            $internalFlagsTypes[] = new ConstantIntegerType($constantScalarValue | \PREG_UNMATCHED_AS_NULL);
        }
        return TypeCombinator::union(...$internalFlagsTypes);
    }
    public static function removeNullFromMatches(Type $matchesType): Type
    {
        return TypeTraverser::map($matchesType, static function (Type $type, callable $traverse): Type {
            if ($type instanceof UnionType || $type instanceof IntersectionType) {
                return $traverse($type);
            }
            if ($type instanceof ConstantArrayType) {
                return new ConstantArrayType($type->getKeyTypes(), array_map(static function (Type $valueType) use ($traverse): Type {
                    return $traverse($valueType);
                }, $type->getValueTypes()), $type->getNextAutoIndexes(), [], $type->isList());
            }
            if ($type instanceof ArrayType) {
                return new ArrayType($type->getKeyType(), $traverse($type->getItemType()));
            }
            return TypeCombinator::removeNull($type);
        });
    }
}
