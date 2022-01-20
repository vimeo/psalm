# Upgrading from Psalm 4 to Psalm 5
## Changed
 - [BC] The parameter `$php_version` of `Psalm\Type\Atomic::create()` renamed
   to `$analysis_php_version_id` and changed from `array|null` to `int|null`.
   Previously it accepted PHP version as `array{major_version, minor_version}`
   while now it accepts version ID, similar to how [`PHP_VERSION_ID` is
   calculated](https://www.php.net/manual/en/reserved.constants.php#constant.php-version-id).

 - [BC] The parameter `$php_version` of `Psalm\Type::parseString()` renamed to
   `$analysis_php_version_id` and changed from `array|null` to `int|null`.
   Previously it accepted PHP version as `array{major_version, minor_version}`
   while now it accepts version ID.

 - [BC] Parameter 0 of `canBeFullyExpressedInPhp()` of the classes listed below
   changed name from `php_major_version` to `analysis_php_version_id`.
   Previously it accepted major PHP version as int (e.g. `7`), while now it
   accepts version ID. Classes affected:
    - `Psalm\Type\Atomic`
    - `Psalm\Type\Atomic\Scalar`
    - `Psalm\Type\Atomic\TArray`
    - `Psalm\Type\Atomic\TArrayKey`
    - `Psalm\Type\Atomic\TCallable`
    - `Psalm\Type\Atomic\TCallableObject`
    - `Psalm\Type\Atomic\TCallableString`
    - `Psalm\Type\Atomic\TClassConstant`
    - `Psalm\Type\Atomic\TClassString`
    - `Psalm\Type\Atomic\TClassStringMap`
    - `Psalm\Type\Atomic\TClosedResource`
    - `Psalm\Type\Atomic\TClosure`
    - `Psalm\Type\Atomic\TConditional`
    - `Psalm\Type\Atomic\TDependentGetClass`
    - `Psalm\Type\Atomic\TDependentGetDebugType`
    - `Psalm\Type\Atomic\TDependentGetType`
    - `Psalm\Type\Atomic\TDependentListKey`
    - `Psalm\Type\Atomic\TEnumCase`
    - `Psalm\Type\Atomic\TFalse`
    - `Psalm\Type\Atomic\TGenericObject`
    - `Psalm\Type\Atomic\TIntMask`
    - `Psalm\Type\Atomic\TIntMaskOf`
    - `Psalm\Type\Atomic\TIntRange`
    - `Psalm\Type\Atomic\TIterable`
    - `Psalm\Type\Atomic\TKeyedArray`
    - `Psalm\Type\Atomic\TKeyOfClassConstant`
    - `Psalm\Type\Atomic\TList`
    - `Psalm\Type\Atomic\TLiteralClassString`
    - `Psalm\Type\Atomic\TLowercaseString`
    - `Psalm\Type\Atomic\TMixed`
    - `Psalm\Type\Atomic\TNamedObject`
    - `Psalm\Type\Atomic\TNever`
    - `Psalm\Type\Atomic\TNonEmptyLowercaseString`
    - `Psalm\Type\Atomic\TNonspecificLiteralInt`
    - `Psalm\Type\Atomic\TNonspecificLiteralString`
    - `Psalm\Type\Atomic\TNull`
    - `Psalm\Type\Atomic\TNumeric`
    - `Psalm\Type\Atomic\TNumericString`
    - `Psalm\Type\Atomic\TObject`
    - `Psalm\Type\Atomic\TObjectWithProperties`
    - `Psalm\Type\Atomic\TPositiveInt`
    - `Psalm\Type\Atomic\TResource`
    - `Psalm\Type\Atomic\TScalar`
    - `Psalm\Type\Atomic\TTemplateIndexedAccess`
    - `Psalm\Type\Atomic\TTemplateParam`
    - `Psalm\Type\Atomic\TTraitString`
    - `Psalm\Type\Atomic\TTrue`
    - `Psalm\Type\Atomic\TTypeAlias`
    - `Psalm\Type\Atomic\TValueOfClassConstant`
    - `Psalm\Type\Atomic\TVoid`
    - `Psalm\Type\Union`

 - [BC] Parameter 3 of `toPhpString()` of methods listed below changed name
   from `php_major_version` to `analysis_php_version_id`. Previously it
   accepted major PHP version as int (e.g. `7`), while now it accepts version
   ID. Classes affected:
    - `Psalm\Type\Atomic`
    - `Psalm\Type\Atomic\CallableTrait`
    - `Psalm\Type\Atomic\TAnonymousClassInstance`
    - `Psalm\Type\Atomic\TArray`
    - `Psalm\Type\Atomic\TArrayKey`
    - `Psalm\Type\Atomic\TBool`
    - `Psalm\Type\Atomic\TCallable`
    - `Psalm\Type\Atomic\TCallableObject`
    - `Psalm\Type\Atomic\TClassConstant`
    - `Psalm\Type\Atomic\TClassString`
    - `Psalm\Type\Atomic\TClassStringMap`
    - `Psalm\Type\Atomic\TClosedResource`
    - `Psalm\Type\Atomic\TConditional`
    - `Psalm\Type\Atomic\TEmpty`
    - `Psalm\Type\Atomic\TEnumCase`
    - `Psalm\Type\Atomic\TFloat`
    - `Psalm\Type\Atomic\TGenericObject`
    - `Psalm\Type\Atomic\TInt`
    - `Psalm\Type\Atomic\TIterable`
    - `Psalm\Type\Atomic\TKeyedArray`
    - `Psalm\Type\Atomic\TKeyOfClassConstant`
    - `Psalm\Type\Atomic\TList`
    - `Psalm\Type\Atomic\TLiteralClassString`
    - `Psalm\Type\Atomic\TMixed`
    - `Psalm\Type\Atomic\TNamedObject`
    - `Psalm\Type\Atomic\TNever`
    - `Psalm\Type\Atomic\TNull`
    - `Psalm\Type\Atomic\TNumeric`
    - `Psalm\Type\Atomic\TObject`
    - `Psalm\Type\Atomic\TObjectWithProperties`
    - `Psalm\Type\Atomic\TResource`
    - `Psalm\Type\Atomic\TScalar`
    - `Psalm\Type\Atomic\TString`
    - `Psalm\Type\Atomic\TTemplateIndexedAccess`
    - `Psalm\Type\Atomic\TTemplateParam`
    - `Psalm\Type\Atomic\TTraitString`
    - `Psalm\Type\Atomic\TTypeAlias`
    - `Psalm\Type\Atomic\TValueOfClassConstant`
    - `Psalm\Type\Atomic\TVoid`
    - `Psalm\Type\Union`
 - While not a BC break per se, all classes / interfaces / traits / enums under
   `Psalm\Internal` namespace are now marked `@internal`.
 - [BC] Parameter 1 of `Psalm\Type\Atomic\TNamedObject::__construct()` changed name from `was_static` to `is_static`
 - [BC] Parameter 1 of `Psalm\Type\Atomic\TAnonymousClassInstance::__construct()` changed name from `was_static` to `is_static`
 - [BC] Parameter 5 of `Psalm\Type::getStringFromFQCLN()` changed name from `was_static` to `is_static`
 - [BC] Property `Psalm\Type\Atomic\TNamedObject::$was_static` was renamed to `$is_static`
 - [BC] Method `Psalm\Type\Union::isFormerStaticObject()` was renamed to `isStaticObject()`
 - [BC] Method `Psalm\Type\Union::hasFormerStaticObject()` was renamed to `hasStaticObject()`
 - [BC] Function assertions (from `@psalm-assert Foo $bar`) have been converted from strings to specific `Assertion` objects.

## Removed
 - [BC] Property `Psalm\Codebase::$php_major_version` was removed, use
   `Psalm\Codebase::$analysis_php_version_id`.
 - [BC] Property `Psalm\Codebase::$php_minor_version` was removed, use
   `Psalm\Codebase::$analysis_php_version_id`.
 - [BC] Class `Psalm\Type\Atomic\TEmpty` was removed
 - [BC] Method `Psalm\Type\Union::isEmpty()` was removed
 - [BC] Property `Psalm\Config::$allow_phpstorm_generics` was removed
 - [BC] Property `Psalm\Config::$exit_functions` was removed
 - [BC] Property `Psalm\Config::$forbid_echo` was removed
 - [BC] Method `Psalm\Type::getEmpty()` was removed
 - [BC] Legacy hook interfaces have been removed:
   -  `Psalm\Plugin\Hook\MethodReturnTypeProviderInterface`
   -  `Psalm\Plugin\Hook\BeforeFileAnalysisInterface`
   -  `Psalm\Plugin\Hook\AfterFileAnalysisInterface`
   -  `Psalm\Plugin\Hook\AfterMethodCallAnalysisInterface`
   -  `Psalm\Plugin\Hook\AfterClassLikeVisitInterface`
   -  `Psalm\Plugin\Hook\StringInterpreterInterface`
   -  `Psalm\Plugin\Hook\AfterExpressionAnalysisInterface`
   -  `Psalm\Plugin\Hook\AfterEveryFunctionCallAnalysisInterface`
   -  `Psalm\Plugin\Hook\PropertyExistenceProviderInterface`
   -  `Psalm\Plugin\Hook\AfterFunctionLikeAnalysisInterface`
   -  `Psalm\Plugin\Hook\FunctionParamsProviderInterface`
   -  `Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface`
   -  `Psalm\Plugin\Hook\FunctionExistenceProviderInterface`
   -  `Psalm\Plugin\Hook\AfterAnalysisInterface`
   -  `Psalm\Plugin\Hook\MethodVisibilityProviderInterface`
   -  `Psalm\Plugin\Hook\MethodParamsProviderInterface`
   -  `Psalm\Plugin\Hook\AfterClassLikeExistenceCheckInterface`
   -  `Psalm\Plugin\Hook\PropertyTypeProviderInterface`
   -  `Psalm\Plugin\Hook\AfterFunctionCallAnalysisInterface`
   -  `Psalm\Plugin\Hook\MethodExistenceProviderInterface`
   -  `Psalm\Plugin\Hook\AfterCodebasePopulatedInterface`
   -  `Psalm\Plugin\Hook\AfterClassLikeAnalysisInterface`
   -  `Psalm\Plugin\Hook\PropertyVisibilityProviderInterface`
   -  `Psalm\Plugin\Hook\AfterStatementAnalysisInterface`
 - [BC] Method `Psalm\Issue\CodeIssue::getLocation()` was removed
 - [BC] Method `Psalm\Issue\CodeIssue::getFileName()` was removed
 - [BC] Method `Psalm\Issue\CodeIssue::getMessage()` was removed
 - [BC] Method `Psalm\DocComment::parse()` was removed
 - [BC] Class `Psalm\Type\Atomic\THtmlEscapedString` has been removed
