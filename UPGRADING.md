# Upgrading from Psalm 6 to Psalm 7

## Changed

- [BC] Taints are now *internally* represented by a bitmap (an integer), instead of an array of strings. Users can still use the usual string taint identifiers (including custom ones, which will be automatically registered by Psalm), but internally, the type of `Psalm\Type\TaintKind` taint types is now an integer.

- [BC] The maximum number of usable taint *types* (including both native taints and custom taints) is now equal to 32 on 32-bit systems and 64 on 64-bit systems: this should be enough for the vast majority of usecases, if more taint types are needed, consider merging some taint types or using some native taint types.  

- [BC] `Psalm\Plugin\EventHandler\AddTaintsInterface::addTaints` and `Psalm\Plugin\EventHandler\RemoveTaintsInterface::removeTaints` now must return an integer taint instead of an array of strings (see the new [taint documentation](https://psalm.dev/docs/security_analysis/custom_taint_sources/) for more info).  

- [BC] The type of the `$taints` parameter of `Psalm\Codebase::addTaintSource` and  `Psalm\Codebase::addTaintSink` was changed to an integer

- [BC] Type of property `Psalm\Storage\FunctionLikeParameter::$sinks` changed from `array|null` to `int`

- [BC] Type of property `Psalm\Storage\FunctionLikeStorage::$taint_source_types` changed from `array` to `int`

- [BC] Type of property `Psalm\Storage\FunctionLikeStorage::$added_taints` changed from `array` to `int`

- [BC] Type of property `Psalm\Storage\FunctionLikeStorage::$removed_taints` changed from `array` to `int`

- [BC] The `startScanningFiles`, `startAnalyzingFiles`, `startAlteringFiles` of `Psalm\Progress\Progress` and subclasses were removed and replaced with a new `startPhase` method, taking a `Psalm\Progress\Phase` enum case.

- [BC] The `start` method was removed, use `expand`, instead; the progress is reset to 0 when changing the current phase.  

- [BC] Method `doesTerminalSupportUtf8` of class `Psalm\Progress\Progress` became final

- [BC] Method debug() of class Psalm\Progress\Progress changed from concrete to abstract

- [BC] Method alterFileDone() of class Psalm\Progress\Progress changed from concrete to abstract

- [BC] Method expand() of class Psalm\Progress\Progress changed from concrete to abstract

- [BC] Method taskDone() of class Psalm\Progress\Progress changed from concrete to abstract

- [BC] Method finish() of class Psalm\Progress\Progress changed from concrete to abstract

- [BC] The return type of Psalm\Type::getListAtomic() changed from Psalm\Type\Atomic\TKeyedArray to the non-covariant Psalm\Type\Atomic\TKeyedArray|Psalm\Type\Atomic\TArray

- [BC] The return type of Psalm\Type::getListAtomic() changed from Psalm\Type\Atomic\TKeyedArray to Psalm\Type\Atomic\TKeyedArray|Psalm\Type\Atomic\TArray

- [BC] The return type of Psalm\Type::getNonEmptyListAtomic() changed from Psalm\Type\Atomic\TKeyedArray to the non-covariant Psalm\Type\Atomic\TKeyedArray|Psalm\Type\Atomic\TArray

- [BC] The return type of Psalm\Type::getNonEmptyListAtomic() changed from Psalm\Type\Atomic\TKeyedArray to Psalm\Type\Atomic\TKeyedArray|Psalm\Type\Atomic\TArray

- [BC] Class Psalm\Type\Atomic\TKeyedArray became final

- [BC] Class Psalm\Type\Atomic\TKeyedArray can only be created using the new `make` or `makeCallable` factory methods, the constructor was rendered private.  

- [BC] Class Psalm\Type\Atomic\TCallableKeyedArray has been deleted, and replaced with a new `is_callable` flag in Psalm\Type\Atomic\TKeyedArray

- [BC] Class Psalm\Type\Atomic\TCallableInterface has been deleted, use `\Psalm\Type\Atomic::isCallableType()` instead

## Removed

- [BC] Constant Psalm\Type\Atomic\TKeyedArray::NAME_ARRAY was removed

- [BC] Constant Psalm\Type\Atomic\TKeyedArray::NAME_LIST was removed

- [BC] Psalm\Type\Atomic\TKeyedArray#__construct() was made private

# Upgrading from Psalm 5 to Psalm 6
## Changed

- The minimum PHP version was raised to PHP 8.1.17.

- Dictionaries were refactored and are now automatically generated and validated with the new `bin/gen_callmap.sh` script, see [here &raquo;](https://psalm.dev/docs/contributing/editing_callmaps/) for the full documentation.

- [BC] The configuration settings `ignoreInternalFunctionFalseReturn` and `ignoreInternalFunctionNullReturn` are now defaulted to `false`

- [BC] Switched the internal representation of `list<T>` and `non-empty-list<T>` from the TList and TNonEmptyList classes to an unsealed list shape: the TList, TNonEmptyList and TCallableList classes were removed.
  Nothing will change for users: the `list<T>` and `non-empty-list<T>` syntax will remain supported and its semantics unchanged.
  Psalm 5 already deprecates the `TList`, `TNonEmptyList` and `TCallableList` classes: use `\Psalm\Type::getListAtomic`, `\Psalm\Type::getNonEmptyListAtomic` and `\Psalm\Type::getCallableListAtomic` to instantiate list atomics, or directly instantiate TKeyedArray objects with `is_list=true` where appropriate.

- [BC] The only optional boolean parameter of `TKeyedArray::getGenericArrayType` was removed, and was replaced with a string parameter with a different meaning.

- [BC] The `TDependentListKey` type was removed and replaced with an optional property of the `TIntRange` type.

- [BC] `TCallableArray` and `TCallableList` removed and replaced with `TCallableKeyedArray`.

- [BC] Class `Psalm\Issue\MixedInferredReturnType` was removed

- [BC] Value of constant `Psalm\Type\TaintKindGroup::ALL_INPUT` changed to reflect new `TaintKind::INPUT_EXTRACT`, `TaintKind::INPUT_SLEEP` and `TaintKind::INPUT_XPATH` have been added. Accordingly, default values for `$taint` parameters of `Psalm\Codebase::addTaintSource()` and `Psalm\Codebase::addTaintSink()` have been changed as well.

- [BC] Property `Config::$shepherd_host` was replaced with `Config::$shepherd_endpoint`

- [BC] Methods `Codebase::getSymbolLocation()` and `Codebase::getSymbolInformation()` were replaced with `Codebase::getSymbolLocationByReference()`

- [BC] Method `Psalm\Type\Atomic\TKeyedArray::getList()` was removed

- [BC] Method `Psalm\Storage\FunctionLikeStorage::getSignature()` was replaced with `FunctionLikeStorage::getCompletionSignature()`

- [BC] Property `Psalm\Storage\FunctionLikeStorage::$unused_docblock_params` was replaced with `FunctionLikeStorage::$unused_docblock_parameters`

- [BC] Method `Plugin\Shepherd::getCurlErrorMessage()` was removed

- [BC] Property `Config::$find_unused_code` changed default value from false to true

- [BC] Property `Config::$find_unused_baseline_entry` changed default value from false to true

- [BC] The return type of `Psalm\Internal\LanguageServer\ProtocolWriter#write() changed from `Amp\Promise` to `void` due to the switch to Amp v3

- [BC] All parameters, properties and return typehints are now strictly typed.

- [BC] `strict_types` is now applied to all files of the Psalm codebase.

- [BC] Properties `Psalm\Type\Atomic\TLiteralFloat::$value` and `Psalm\Type\Atomic\TLiteralInt::$value` became typed (`float` and `int` respectively)

- [BC] Property `Psalm\Storage\EnumCaseStorage::$value` changed from `int|string|null` to `TLiteralInt|TLiteralString|null`

- [BC] `Psalm\CodeLocation\Raw`, `Psalm\CodeLocation\ParseErrorLocation`, `Psalm\CodeLocation\DocblockTypeLocation`, `Psalm\Report\CountReport`, `Psalm\Type\Atomic\TNonEmptyArray` are now all final.

- [BC] `Psalm\Config` is now final.

- [BC] The return type of `Psalm\Plugin\ArgTypeInferer::infer` changed from `Union|false` to `Union|null`

- [BC] The `extra_types` property and `setIntersectionTypes` method of `Psalm\Type\Atomic\TTypeAlias` were removed.

- [BC] Methods `convertSeverity` and `calculateFingerprint` of `Psalm\Report\CodeClimateReport` were removed.

# Upgrading from Psalm 4 to Psalm 5
## Changed

- [BC] Shaped arrays can now be sealed: this brings many assertion improvements and bugfixes, see [the docs for more info](https://psalm.dev/docs/annotating_code/type_syntax/array_types/#sealed-object-like-arrays).

- [BC] All atomic types, `Psalm\Type\Union`, `Psalm\CodeLocation` and storages are fully immutable, use the new setter methods or the new constructors to change properties: these setter methods will return new instances without altering the original instance.  
  Full immutability fixes a whole class of bugs that occurred in multithreaded mode, you can now feel free to use `--threads=$(nproc)` ;)
  Full immutability also makes Psalm run faster, even in single-threaded mode, by removing all superfluous `clone`s!
  For this purpose, `__clone` was also made private, forbidding the cloning of atomics, unions and storages (an old and brittle pattern used to avoid side-effects caused by mutability).  

- [BC] `Psalm\Type\Union`s are now fully immutable, pre-existing in-place mutator methods were removed and moved into `Psalm\Type\MutableUnion`.  
  To modify a union type, usage of the new setter methods in `Psalm\Type\Union` is strongly recommended.  
  When many consecutive property sets are required, use `Psalm\Type\Union::setProperties` method to avoid creating a new instance for each set.  
  All setter methods will return a new instance of the type without altering the original instance.  
  If many property sets are required throughout multiple methods on a single Union instance, use `Psalm\Type\Union::getBuilder` to turn a `Psalm\Type\Union` into a `Psalm\Type\MutableUnion`: once you're done, use `Psalm\Type\MutableUnion::freeze` to get a new `Psalm\Type\Union`.  
  Methods removed from `Psalm\Type\Union` and moved into `Psalm\Type\MutableUnion`:

   - `replaceTypes`
   - `addType`
   - `removeType`
   - `substitute`
   - `replaceClassLike`

- [BC] `Psalm\Type\TypeNode::getChildNodes()` was removed, use `Psalm\Type\Union::getAtomicTypes()` to get the types of a union, and use `Psalm\Type\TypeVisitor` with the new `Psalm\Type\MutableTypeVisitor` class to iterate over a type tree.  

- [BC] `Psalm\Type\TypeVisitor` is now fully immutable, implementors MUST NOT alter type nodes during iteration: use `Psalm\Type\MutableTypeVisitor` if type node mutation is desired.  

- [BC] TPositiveInt has been removed and replaced by TIntRange

- [BC] Property `Psalm\Config::$cache_directory` is now internal. Use
  `Psalm\Config::getCacheDirectory()` instead.

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
   - `Psalm\Type\Atomic\TKeyOf`
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
   - `Psalm\Type\Atomic\TResource`
   - `Psalm\Type\Atomic\TScalar`
   - `Psalm\Type\Atomic\TTemplateIndexedAccess`
   - `Psalm\Type\Atomic\TTemplateParam`
   - `Psalm\Type\Atomic\TTraitString`
   - `Psalm\Type\Atomic\TTrue`
   - `Psalm\Type\Atomic\TTypeAlias`
   - `Psalm\Type\Atomic\TValueOf`
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
   - `Psalm\Type\Atomic\TKeyOf`
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
   - `Psalm\Type\Atomic\TValueOf`
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
- [BC] Property `Psalm\Storage\ClassLikeStorage::$invalid_dependencies` changed from `array<string>` to `array<string, true>`.
- [BC] Property `Psalm\Storage\ClassLikeStorage::$template_extended_count` was renamed to `$template_type_extends_count`, its type was changed from `int|null` to `array<string, int>|null`.
- [BC] Event classes became final and their constructors were marked `@internal`:
  - `Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent`
  - `Psalm\Plugin\EventHandler\Event\AfterAnalysisEvent`
  - `Psalm\Plugin\EventHandler\Event\AfterClassLikeAnalysisEvent`
  - `Psalm\Plugin\EventHandler\Event\AfterClassLikeExistenceCheckEvent`
  - `Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent`
  - `Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent`
  - `Psalm\Plugin\EventHandler\Event\AfterEveryFunctionCallAnalysisEvent`
  - `Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent`
  - `Psalm\Plugin\EventHandler\Event\AfterFileAnalysisEvent`
  - `Psalm\Plugin\EventHandler\Event\AfterFunctionCallAnalysisEvent`
  - `Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent`
  - `Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent`
  - `Psalm\Plugin\EventHandler\Event\AfterStatementAnalysisEvent`
  - `Psalm\Plugin\EventHandler\Event\BeforeFileAnalysisEvent`
  - `Psalm\Plugin\EventHandler\Event\FunctionExistenceProviderEvent`
  - `Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent`
  - `Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent`
  - `Psalm\Plugin\EventHandler\Event\MethodExistenceProviderEvent`
  - `Psalm\Plugin\EventHandler\Event\MethodParamsProviderEvent`
  - `Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent`
  - `Psalm\Plugin\EventHandler\Event\MethodVisibilityProviderEvent`
  - `Psalm\Plugin\EventHandler\Event\PropertyExistenceProviderEvent`
  - `Psalm\Plugin\EventHandler\Event\PropertyTypeProviderEvent`
  - `Psalm\Plugin\EventHandler\Event\PropertyVisibilityProviderEvent`
  - `Psalm\Plugin\EventHandler\Event\StringInterpreterEvent`
- [BC] `Atomic::__toString()` used to return a string representation of the type that was using double quotes (`"`) to quote literals. This is now using single quotes (`'`) to be more aligned with the rest of the codebase.
- [BC] `Atomic::__toString()` is now final
- [BC] `Atomic::__toString()` now returns a more detailed version of the type (it calls `getId()` under the hood)
- [BC] Atomic::getId() has now a first param $exact. Calling the method with false will return a less detailed version of the type in some cases (similarly to what `__toString` used to return)
- [BC] To remove a variable from the context, use `Context::remove()`. Calling
  `unset($context->vars_in_scope[$var_id])` can cause problems when using references.
- [BC] `TKeyOfClassConstant` has been renamed to `TKeyOf`.
- [BC] `TValueOfClassConstant` has been renamed to `TValueOf`.
- [BC] `TKeyOfTemplate` base class has been changed from `Scalar` to `Atomic`.
- [BC] Class `Psalm\FileManipulation` became final
- [BC] Class `Psalm\Context` became final
- [BC] `Psalm\Context#__construct()` was marked `@internal`
- [BC] Class `Psalm\PluginRegistrationSocket` became final
- [BC] Class `Psalm\PluginFileExtensionsSocket` became final
- [BC] Class `Psalm\Config\IssueHandler` became final
- [BC] `Psalm\Config\IssueHandler` was marked `@internal`
- [BC] Class `Psalm\Config\ProjectFileFilter` became final
- [BC] `Psalm\Config\ProjectFileFilter` was marked `@internal`
- [BC] Class `Psalm\Config\Creator` became final
- [BC] `Psalm\Config\Creator` was marked `@internal`
- [BC] Class `Psalm\Config\TaintAnalysisFileFilter` became final
- [BC] `Psalm\Config\TaintAnalysisFileFilter` was marked `@internal`
- [BC] Class `Psalm\Config\ErrorLevelFileFilter` became final
- [BC] `Psalm\Config\ErrorLevelFileFilter` was marked `@internal`
- [BC] Class `Psalm\FileBasedPluginAdapter` became final
- [BC] `Psalm\FileBasedPluginAdapter` was marked `@internal`
- [BC] Class `Psalm\Exception\InvalidMethodOverrideException` became final
- [BC] Class `Psalm\Exception\UnpopulatedClasslikeException` became final
- [BC] Class `Psalm\Exception\ConfigNotFoundException` became final
- [BC] Class `Psalm\Exception\TypeParseTreeException` became final
- [BC] Class `Psalm\Exception\ComplicatedExpressionException` became final
- [BC] Class `Psalm\Exception\ScopeAnalysisException` became final
- [BC] Class `Psalm\Exception\InvalidClasslikeOverrideException` became final
- [BC] Class `Psalm\Exception\CircularReferenceException` became final
- [BC] Class `Psalm\Exception\UnsupportedIssueToFixException` became final
- [BC] Class `Psalm\Exception\CodeException` became final
- [BC] Class `Psalm\Exception\RefactorException` became final
- [BC] Class `Psalm\Exception\UnpreparedAnalysisException` became final
- [BC] Class `Psalm\Exception\IncorrectDocblockException` became final
- [BC] Class `Psalm\Exception\UnanalyzedFileException` became final
- [BC] Class `Psalm\Exception\UnresolvableConstantException` became final
- [BC] Class `Psalm\Exception\FileIncludeException` became final
- [BC] Class `Psalm\Exception\ConfigCreationException` became final
- [BC] Class `Psalm\Aliases` became final
- [BC] `Psalm\Aliases#__construct()` was marked `@internal`
- [BC] Class `Psalm\Codebase` became final
- [BC] `Psalm\Codebase#__construct()` was marked `@internal`
- [BC] Class `Psalm\Progress\VoidProgress` became final
- [BC] Class `Psalm\Progress\DebugProgress` became final
- [BC] Class `Psalm\Report\JsonReport` became final
- [BC] Class `Psalm\Report\SonarqubeReport` became final
- [BC] Class `Psalm\Report\CodeClimateReport` became final
- [BC] Class `Psalm\Report\CheckstyleReport` became final
- [BC] Class `Psalm\Report\JsonSummaryReport` became final
- [BC] Class `Psalm\Report\XmlReport` became final
- [BC] Class `Psalm\Report\EmacsReport` became final
- [BC] Class `Psalm\Report\ConsoleReport` became final
- [BC] Class `Psalm\Report\ReportOptions` became final
- [BC] Class `Psalm\Report\PylintReport` became final
- [BC] Class `Psalm\Report\JunitReport` became final
- [BC] Class `Psalm\Report\CompactReport` became final
- [BC] Class `Psalm\Report\GithubActionsReport` became final
- [BC] Class `Psalm\Report\TextReport` became final
- [BC] Class `Psalm\Report\SarifReport` became final
- [BC] Class `Psalm\Report\PhpStormReport` became final
- [BC] Class `Psalm\Plugin\Shepherd` became final
- [BC] Class `Psalm\IssueBuffer` became final
- [BC] Class `Psalm\SourceControl\Git\RemoteInfo` became final
- [BC] Class `Psalm\SourceControl\Git\CommitInfo` became final
- [BC] Class `Psalm\SourceControl\Git\GitInfo` became final
- [BC] Class `Psalm\ErrorBaseline` became final
- [BC] `Psalm\Config#__construct()` was marked `@internal`
- [BC] Class `Psalm\DocComment` became final
- All non-abstract issues are now final:
    - [BC] Class `Psalm\Issue\InaccessibleProperty` became final
    - [BC] Class `Psalm\Issue\TaintedShell` became final
    - [BC] Class `Psalm\Issue\PossiblyInvalidIterator` became final
    - [BC] Class `Psalm\Issue\MethodSignatureMustOmitReturnType` became final
    - [BC] Class `Psalm\Issue\TaintedHtml` became final
    - [BC] Class `Psalm\Issue\DuplicateConstant` became final
    - [BC] Class `Psalm\Issue\MissingConstructor` became final
    - [BC] Class `Psalm\Issue\PossiblyFalseIterator` became final
    - [BC] Class `Psalm\Issue\PossiblyUndefinedArrayOffset` became final
    - [BC] Class `Psalm\Issue\FalseOperand` became final
    - [BC] Class `Psalm\Issue\MixedArrayAssignment` became final
    - [BC] Class `Psalm\Issue\MixedArrayAccess` became final
    - [BC] Class `Psalm\Issue\TaintedUnserialize` became final
    - [BC] Class `Psalm\Issue\NullFunctionCall` became final
    - [BC] Class `Psalm\Issue\UnusedConstructor` became final
    - [BC] Class `Psalm\Issue\InvalidEnumCaseValue` became final
    - [BC] Class `Psalm\Issue\MissingClosureReturnType` became final
    - [BC] Class `Psalm\Issue\LessSpecificClassConstantType` became final
    - [BC] Class `Psalm\Issue\MixedPropertyFetch` became final
    - [BC] Class `Psalm\Issue\PossiblyNullArrayAccess` became final
    - [BC] Class `Psalm\Issue\MissingPropertyType` became final
    - [BC] Class `Psalm\Issue\TaintedCallable` became final
    - [BC] Class `Psalm\Issue\PossiblyInvalidMethodCall` became final
    - [BC] Class `Psalm\Issue\TaintedHeader` became final
    - [BC] Class `Psalm\Issue\PossiblyInvalidArrayAssignment` became final
    - [BC] Class `Psalm\Issue\PossiblyInvalidCast` became final
    - [BC] Class `Psalm\Issue\ImpurePropertyAssignment` became final
    - [BC] Class `Psalm\Issue\MixedPropertyTypeCoercion` became final
    - [BC] Class `Psalm\Issue\UnresolvableConstant` became final
    - [BC] Class `Psalm\Issue\LoopInvalidation` became final
    - [BC] Class `Psalm\Issue\TooManyTemplateParams` became final
    - [BC] Class `Psalm\Issue\InvalidCatch` became final
    - [BC] Class `Psalm\Issue\MismatchingDocblockReturnType` became final
    - [BC] Class `Psalm\Issue\PossiblyUndefinedIntArrayOffset` became final
    - [BC] Class `Psalm\Issue\NullArrayAccess` became final
    - [BC] Class `Psalm\Issue\NoEnumProperties` became final
    - [BC] Class `Psalm\Issue\ImpureByReferenceAssignment` became final
    - [BC] Class `Psalm\Issue\RedundantConditionGivenDocblockType` became final
    - [BC] Class `Psalm\Issue\MixedReturnTypeCoercion` became final
    - [BC] Class `Psalm\Issue\PossiblyNullOperand` became final
    - [BC] Class `Psalm\Issue\InvalidGlobal` became final
    - [BC] Class `Psalm\Issue\PossiblyNullArgument` became final
    - [BC] Class `Psalm\Issue\ForbiddenCode` became final
    - [BC] Class `Psalm\Issue\RedundantCast` became final
    - [BC] Class `Psalm\Issue\UnusedParam` became final
    - [BC] Class `Psalm\Issue\DuplicateArrayKey` became final
    - [BC] Class `Psalm\Issue\MissingImmutableAnnotation` became final
    - [BC] Class `Psalm\Issue\MutableDependency` became final
    - [BC] Class `Psalm\Issue\MixedPropertyAssignment` became final
    - [BC] Class `Psalm\Issue\DeprecatedTrait` became final
    - [BC] Class `Psalm\Issue\InvalidArrayAccess` became final
    - [BC] Class `Psalm\Issue\LessSpecificReturnStatement` became final
    - [BC] Class `Psalm\Issue\AssignmentToVoid` became final
    - [BC] Class `Psalm\Issue\InvalidPropertyAssignment` became final
    - [BC] Class `Psalm\Issue\InvalidFalsableReturnType` became final
    - [BC] Class `Psalm\Issue\IfThisIsMismatch` became final
    - [BC] Class `Psalm\Issue\UndefinedPropertyFetch` became final
    - [BC] Class `Psalm\Issue\UndefinedMagicPropertyFetch` became final
    - [BC] Class `Psalm\Issue\PossiblyUnusedReturnValue` became final
    - [BC] Class `Psalm\Issue\PossiblyNullPropertyFetch` became final
    - [BC] Class `Psalm\Issue\PossiblyInvalidPropertyFetch` became final
    - [BC] Class `Psalm\Issue\MixedClone` became final
    - [BC] Class `Psalm\Issue\DuplicateFunction` became final
    - [BC] Class `Psalm\Issue\InaccessibleClassConstant` became final
    - [BC] Class `Psalm\Issue\UndefinedGlobalVariable` became final
    - [BC] Class `Psalm\Issue\ImplicitToStringCast` became final
    - [BC] Class `Psalm\Issue\PossiblyInvalidDocblockTag` became final
    - [BC] Class `Psalm\Issue\ReservedWord` became final
    - [BC] Class `Psalm\Issue\InvalidOperand` became final
    - [BC] Class `Psalm\Issue\UnusedProperty` became final
    - [BC] Class `Psalm\Issue\UnevaluatedCode` became final
    - [BC] Class `Psalm\Issue\NullPropertyFetch` became final
    - [BC] Class `Psalm\Issue\ParamNameMismatch` became final
    - [BC] Class `Psalm\Issue\CircularReference` became final
    - [BC] Class `Psalm\Issue\UndefinedThisPropertyFetch` became final
    - [BC] Class `Psalm\Issue\NonStaticSelfCall` became final
    - [BC] Class `Psalm\Issue\NullOperand` became final
    - [BC] Class `Psalm\Issue\MixedAssignment` became final
    - [BC] Class `Psalm\Issue\MixedFunctionCall` became final
    - [BC] Class `Psalm\Issue\InvalidTypeImport` became final
    - [BC] Class `Psalm\Issue\PossiblyNullArrayOffset` became final
    - [BC] Class `Psalm\Issue\PossiblyInvalidArrayOffset` became final
    - [BC] Class `Psalm\Issue\PossiblyInvalidArgument` became final
    - [BC] Class `Psalm\Issue\UndefinedPropertyAssignment` became final
    - [BC] Class `Psalm\Issue\UnusedReturnValue` became final
    - [BC] Class `Psalm\Issue\ImpureFunctionCall` became final
    - [BC] Class `Psalm\Issue\RedundantFunctionCallGivenDocblockType` became final
    - [BC] Class `Psalm\Issue\PossiblyInvalidPropertyAssignmentValue` became final
    - [BC] Class `Psalm\Issue\PossiblyInvalidOperand` became final
    - [BC] Class `Psalm\Issue\ArgumentTypeCoercion` became final
    - [BC] Class `Psalm\Issue\OverriddenPropertyAccess` became final
    - [BC] Class `Psalm\Issue\PossiblyInvalidArrayAccess` became final
    - [BC] Class `Psalm\Issue\UnusedForeachValue` became final
    - [BC] Class `Psalm\Issue\ImplementedParamTypeMismatch` became final
    - [BC] Class `Psalm\Issue\InvalidConstantAssignmentValue` became final
    - [BC] Class `Psalm\Issue\PossiblyUndefinedMethod` became final
    - [BC] Class `Psalm\Issue\DuplicateEnumCaseValue` became final
    - [BC] Class `Psalm\Issue\RawObjectIteration` became final
    - [BC] Class `Psalm\Issue\UndefinedVariable` became final
    - [BC] Class `Psalm\Issue\MissingDocblockType` became final
    - [BC] Class `Psalm\Issue\NullArrayOffset` became final
    - [BC] Class `Psalm\Issue\PropertyNotSetInConstructor` became final
    - [BC] Class `Psalm\Issue\PossiblyInvalidPropertyAssignment` became final
    - [BC] Class `Psalm\Issue\PossiblyNullPropertyAssignmentValue` became final
    - [BC] Class `Psalm\Issue\UnsafeInstantiation` became final
    - [BC] Class `Psalm\Issue\UnimplementedAbstractMethod` became final
    - [BC] Class `Psalm\Issue\UnusedClosureParam` became final
    - [BC] Class `Psalm\Issue\PossiblyNullFunctionCall` became final
    - [BC] Class `Psalm\Issue\UndefinedAttributeClass` became final
    - [BC] Class `Psalm\Issue\NullableReturnStatement` became final
    - [BC] Class `Psalm\Issue\DuplicateMethod` became final
    - [BC] Class `Psalm\Issue\TooFewArguments` became final
    - [BC] Class `Psalm\Issue\UndefinedConstant` became final
    - [BC] Class `Psalm\Issue\NullReference` became final
    - [BC] Class `Psalm\Issue\ImplementedReturnTypeMismatch` became final
    - [BC] Class `Psalm\Issue\InvalidEnumBackingType` became final
    - [BC] Class `Psalm\Issue\InvalidNullableReturnType` became final
    - [BC] Class `Psalm\Issue\ImpureVariable` became final
    - [BC] Class `Psalm\Issue\TypeDoesNotContainNull` became final
    - [BC] Class `Psalm\Issue\ConstructorSignatureMismatch` became final
    - [BC] Class `Psalm\Issue\ImpurePropertyFetch` became final
    - [BC] Class `Psalm\Issue\RedundantCastGivenDocblockType` became final
    - [BC] Class `Psalm\Issue\PropertyTypeCoercion` became final
    - [BC] Class `Psalm\Issue\InvalidDocblockParamName` became final
    - [BC] Class `Psalm\Issue\UnsafeGenericInstantiation` became final
    - [BC] Class `Psalm\Issue\MissingClosureParamType` became final
    - [BC] Class `Psalm\Issue\TraitMethodSignatureMismatch` became final
    - [BC] Class `Psalm\Issue\ImpureStaticProperty` became final
    - [BC] Class `Psalm\Issue\InvalidThrow` became final
    - [BC] Class `Psalm\Issue\ParentNotFound` became final
    - [BC] Class `Psalm\Issue\ImpureStaticVariable` became final
    - [BC] Class `Psalm\Issue\PossiblyFalseReference` became final
    - [BC] Class `Psalm\Issue\ComplexMethod` became final
    - [BC] Class `Psalm\Issue\PossiblyNullArrayAssignment` became final
    - [BC] Class `Psalm\Issue\AbstractInstantiation` became final
    - [BC] Class `Psalm\Issue\UncaughtThrowInGlobalScope` became final
    - [BC] Class `Psalm\Issue\MismatchingDocblockPropertyType` became final
    - [BC] Class `Psalm\Issue\UnresolvableInclude` became final
    - [BC] Class `Psalm\Issue\DocblockTypeContradiction` became final
    - [BC] Class `Psalm\Issue\TaintedEval` became final
    - [BC] Class `Psalm\Issue\UnusedVariable` became final
    - [BC] Class `Psalm\Issue\DeprecatedConstant` became final
    - [BC] Class `Psalm\Issue\TaintedSystemSecret` became final
    - [BC] Class `Psalm\Issue\EmptyArrayAccess` became final
    - [BC] Class `Psalm\Issue\UndefinedInterface` became final
    - [BC] Class `Psalm\Issue\MixedInferredReturnType` became final
    - [BC] Class `Psalm\Issue\TaintedCookie` became final
    - [BC] Class `Psalm\Issue\UndefinedMagicPropertyAssignment` became final
    - [BC] Class `Psalm\Issue\NamedArgumentNotAllowed` became final
    - [BC] Class `Psalm\Issue\MethodSignatureMustProvideReturnType` became final
    - [BC] Class `Psalm\Issue\MissingParamType` became final
    - [BC] Class `Psalm\Issue\InvalidArrayAssignment` became final
    - [BC] Class `Psalm\Issue\UnimplementedInterfaceMethod` became final
    - [BC] Class `Psalm\Issue\InvalidPassByReference` became final
    - [BC] Class `Psalm\Issue\MissingDependency` became final
    - [BC] Class `Psalm\Issue\ReferenceConstraintViolation` became final
    - [BC] Class `Psalm\Issue\TaintedLdap` became final
    - [BC] Class `Psalm\Issue\PossiblyNullIterator` became final
    - [BC] Class `Psalm\Issue\InvalidScalarArgument` became final
    - [BC] Class `Psalm\Issue\DeprecatedMethod` became final
    - [BC] Class `Psalm\Issue\NullPropertyAssignment` became final
    - [BC] Class `Psalm\Issue\InvalidExtendClass` became final
    - [BC] Class `Psalm\Issue\DeprecatedClass` became final
    - [BC] Class `Psalm\Issue\ReferenceReusedFromConfusingScope` became final
    - [BC] Class `Psalm\Issue\UndefinedFunction` became final
    - [BC] Class `Psalm\Issue\LessSpecificImplementedReturnType` became final
    - [BC] Class `Psalm\Issue\NullIterator` became final
    - [BC] Class `Psalm\Issue\TaintedInclude` became final
    - [BC] Class `Psalm\Issue\UnusedMethodCall` became final
    - [BC] Class `Psalm\Issue\InvalidIterator` became final
    - [BC] Class `Psalm\Issue\PsalmInternalError` became final
    - [BC] Class `Psalm\Issue\InvalidParent` became final
    - [BC] Class `Psalm\Issue\AmbiguousConstantInheritance` became final
    - [BC] Class `Psalm\Issue\InvalidLiteralArgument` became final
    - [BC] Class `Psalm\Issue\MixedReturnStatement` became final
    - [BC] Class `Psalm\Issue\AbstractMethodCall` became final
    - [BC] Class `Psalm\Issue\InvalidClone` became final
    - [BC] Class `Psalm\Issue\DuplicateEnumCase` became final
    - [BC] Class `Psalm\Issue\InvalidDocblock` became final
    - [BC] Class `Psalm\Issue\RedundantIdentityWithTrue` became final
    - [BC] Class `Psalm\Issue\MissingReturnType` became final
    - [BC] Class `Psalm\Issue\RedundantCondition` became final
    - [BC] Class `Psalm\Issue\UnnecessaryVarAnnotation` became final
    - [BC] Class `Psalm\Issue\ConfigIssue` became final
    - [BC] Class `Psalm\Issue\InternalClass` became final
    - [BC] Class `Psalm\Issue\UndefinedDocblockClass` became final
    - [BC] Class `Psalm\Issue\DuplicateParam` became final
    - [BC] Class `Psalm\Issue\MismatchingDocblockParamType` became final
    - [BC] Class `Psalm\Issue\LessSpecificReturnType` became final
    - [BC] Class `Psalm\Issue\PossiblyUnusedProperty` became final
    - [BC] Class `Psalm\Issue\PossiblyNullReference` became final
    - [BC] Class `Psalm\Issue\MissingFile` became final
    - [BC] Class `Psalm\Issue\InvalidArgument` became final
    - [BC] Class `Psalm\Issue\PossiblyUndefinedGlobalVariable` became final
    - [BC] Class `Psalm\Issue\UndefinedThisPropertyAssignment` became final
    - [BC] Class `Psalm\Issue\ConflictingReferenceConstraint` became final
    - [BC] Class `Psalm\Issue\InvalidCast` became final
    - [BC] Class `Psalm\Issue\MoreSpecificReturnType` became final
    - [BC] Class `Psalm\Issue\ImpureMethodCall` became final
    - [BC] Class `Psalm\Issue\UnrecognizedExpression` became final
    - [BC] Class `Psalm\Issue\NoValue` became final
    - [BC] Class `Psalm\Issue\DeprecatedInterface` became final
    - [BC] Class `Psalm\Issue\InvalidStringClass` became final
    - [BC] Class `Psalm\Issue\MixedMethodCall` became final
    - [BC] Class `Psalm\Issue\UndefinedMagicMethod` became final
    - [BC] Class `Psalm\Issue\MissingThrowsDocblock` became final
    - [BC] Class `Psalm\Issue\TaintedTextWithQuotes` became final
    - [BC] Class `Psalm\Issue\InvalidReturnStatement` became final
    - [BC] Class `Psalm\Issue\DeprecatedFunction` became final
    - [BC] Class `Psalm\Issue\InterfaceInstantiation` became final
    - [BC] Class `Psalm\Issue\TooManyArguments` became final
    - [BC] Class `Psalm\Issue\PossibleRawObjectIteration` became final
    - [BC] Class `Psalm\Issue\PossiblyFalsePropertyAssignmentValue` became final
    - [BC] Class `Psalm\Issue\FalsableReturnStatement` became final
    - [BC] Class `Psalm\Issue\RedundantFunctionCall` became final
    - [BC] Class `Psalm\Issue\ImplementationRequirementViolation` became final
    - [BC] Class `Psalm\Issue\InternalMethod` became final
    - [BC] Class `Psalm\Issue\PossiblyInvalidFunctionCall` became final
    - [BC] Class `Psalm\Issue\OverriddenMethodAccess` became final
    - [BC] Class `Psalm\Issue\MixedArgumentTypeCoercion` became final
    - [BC] Class `Psalm\Issue\InvalidAttribute` became final
    - [BC] Class `Psalm\Issue\UndefinedInterfaceMethod` became final
    - [BC] Class `Psalm\Issue\InvalidPropertyFetch` became final
    - [BC] Class `Psalm\Issue\PossiblyUnusedMethod` became final
    - [BC] Class `Psalm\Issue\UndefinedTrace` became final
    - [BC] Class `Psalm\Issue\NullArgument` became final
    - [BC] Class `Psalm\Issue\UndefinedMethod` became final
    - [BC] Class `Psalm\Issue\TaintedUserSecret` became final
    - [BC] Class `Psalm\Issue\UndefinedTrait` became final
    - [BC] Class `Psalm\Issue\UnusedClass` became final
    - [BC] Class `Psalm\Issue\StringIncrement` became final
    - [BC] Class `Psalm\Issue\InaccessibleMethod` became final
    - [BC] Class `Psalm\Issue\PossiblyUnusedParam` became final
    - [BC] Class `Psalm\Issue\Trace` became final
    - [BC] Class `Psalm\Issue\UnhandledMatchCondition` became final
    - [BC] Class `Psalm\Issue\DuplicateClass` became final
    - [BC] Class `Psalm\Issue\InvalidClass` became final
    - [BC] Class `Psalm\Issue\TypeDoesNotContainType` became final
    - [BC] Class `Psalm\Issue\InvalidScope` became final
    - [BC] Class `Psalm\Issue\TaintedCustom` became final
    - [BC] Class `Psalm\Issue\TaintedSSRF` became final
    - [BC] Class `Psalm\Issue\InvalidNamedArgument` became final
    - [BC] Class `Psalm\Issue\InvalidPropertyAssignmentValue` became final
    - [BC] Class `Psalm\Issue\ContinueOutsideLoop` became final
    - [BC] Class `Psalm\Issue\MixedArgument` became final
    - [BC] Class `Psalm\Issue\TaintedSql` became final
    - [BC] Class `Psalm\Issue\UnusedFunctionCall` became final
    - [BC] Class `Psalm\Issue\InternalProperty` became final
    - [BC] Class `Psalm\Issue\InvalidParamDefault` became final
    - [BC] Class `Psalm\Issue\RedundantPropertyInitializationCheck` became final
    - [BC] Class `Psalm\Issue\InvalidTraversableImplementation` became final
    - [BC] Class `Psalm\Issue\InvalidTemplateParam` became final
    - [BC] Class `Psalm\Issue\InvalidStaticInvocation` became final
    - [BC] Class `Psalm\Issue\MixedArrayOffset` became final
    - [BC] Class `Psalm\Issue\PossiblyInvalidClone` became final
    - [BC] Class `Psalm\Issue\InvalidFunctionCall` became final
    - [BC] Class `Psalm\Issue\InvalidMethodCall` became final
    - [BC] Class `Psalm\Issue\ComplexFunction` became final
    - [BC] Class `Psalm\Issue\UnusedPsalmSuppress` became final
    - [BC] Class `Psalm\Issue\MixedStringOffsetAssignment` became final
    - [BC] Class `Psalm\Issue\UnrecognizedStatement` became final
    - [BC] Class `Psalm\Issue\TaintedFile` became final
    - [BC] Class `Psalm\Issue\UnusedMethod` became final
    - [BC] Class `Psalm\Issue\PossiblyFalseArgument` became final
    - [BC] Class `Psalm\Issue\DeprecatedProperty` became final
    - [BC] Class `Psalm\Issue\PossiblyUndefinedVariable` became final
    - [BC] Class `Psalm\Issue\PossiblyNullPropertyAssignment` became final
    - [BC] Class `Psalm\Issue\MixedOperand` became final
    - [BC] Class `Psalm\Issue\NoInterfaceProperties` became final
    - [BC] Class `Psalm\Issue\InvalidReturnType` became final
    - [BC] Class `Psalm\Issue\MixedArrayTypeCoercion` became final
    - [BC] Class `Psalm\Issue\ParadoxicalCondition` became final
    - [BC] Class `Psalm\Issue\InvalidToString` became final
    - [BC] Class `Psalm\Issue\MethodSignatureMismatch` became final
    - [BC] Class `Psalm\Issue\PossiblyFalseOperand` became final
    - [BC] Class `Psalm\Issue\UndefinedClass` became final
    - [BC] Class `Psalm\Issue\OverriddenInterfaceConstant` became final
    - [BC] Class `Psalm\Issue\MissingTemplateParam` became final
    - [BC] Class `Psalm\Issue\InvalidArrayOffset` became final
    - [BC] Class `Psalm\Issue\MoreSpecificImplementedParamType` became final
    - [BC] Class `Psalm\Issue\UninitializedProperty` became final
    - [BC] Class `Psalm\Issue\ParseError` became final
    - [BC] Class `Psalm\Issue\PossiblyUndefinedStringArrayOffset` became final
    - [BC] Class `Psalm\Issue\ExtensionRequirementViolation` became final
 - Storage classes became final:
    - [BC] Class `Psalm\Storage\MethodStorage` became final
    - [BC] Class `Psalm\Storage\AttributeStorage` became final
    - [BC] Class `Psalm\Storage\FileStorage` became final
    - [BC] Class `Psalm\Storage\PropertyStorage` became final
    - [BC] Class `Psalm\Storage\FunctionStorage` became final
    - [BC] Class `Psalm\Storage\Assertion\HasArrayKey` became final
    - [BC] Class `Psalm\Storage\Assertion\Truthy` became final
    - [BC] Class `Psalm\Storage\Assertion\IsAClass` became final
    - [BC] Class `Psalm\Storage\Assertion\HasAtLeastCount` became final
    - [BC] Class `Psalm\Storage\Assertion\HasMethod` became final
    - [BC] Class `Psalm\Storage\Assertion\HasIntOrStringArrayAccess` became final
    - [BC] Class `Psalm\Storage\Assertion\DoesNotHaveMethod` became final
    - [BC] Class `Psalm\Storage\Assertion\IsLessThanOrEqualTo` became final
    - [BC] Class `Psalm\Storage\Assertion\IsNotAClass` became final
    - [BC] Class `Psalm\Storage\Assertion\ArrayKeyDoesNotExist` became final
    - [BC] Class `Psalm\Storage\Assertion\IsNotIdentical` became final
    - [BC] Class `Psalm\Storage\Assertion\IsClassEqual` became final
    - [BC] Class `Psalm\Storage\Assertion\NotNonEmptyCountable` became final
    - [BC] Class `Psalm\Storage\Assertion\Any` became final
    - [BC] Class `Psalm\Storage\Assertion\IsLooselyEqual` became final
    - [BC] Class `Psalm\Storage\Assertion\NonEmpty` became final
    - [BC] Class `Psalm\Storage\Assertion\IsGreaterThanOrEqualTo` became final
    - [BC] Class `Psalm\Storage\Assertion\HasStringArrayAccess` became final
    - [BC] Class `Psalm\Storage\Assertion\IsClassNotEqual` became final
    - [BC] Class `Psalm\Storage\Assertion\HasExactCount` became final
    - [BC] Class `Psalm\Storage\Assertion\IsNotCountable` became final
    - [BC] Class `Psalm\Storage\Assertion\IsIdentical` became final
    - [BC] Class `Psalm\Storage\Assertion\IsType` became final
    - [BC] Class `Psalm\Storage\Assertion\NotNestedAssertions` became final
    - [BC] Class `Psalm\Storage\Assertion\IsGreaterThan` became final
    - [BC] Class `Psalm\Storage\Assertion\IsIsset` became final
    - [BC] Class `Psalm\Storage\Assertion\Empty_` became final
    - [BC] Class `Psalm\Storage\Assertion\IsNotType` became final
    - [BC] Class `Psalm\Storage\Assertion\ArrayKeyExists` became final
    - [BC] Class `Psalm\Storage\Assertion\DoesNotHaveAtLeastCount` became final
    - [BC] Class `Psalm\Storage\Assertion\IsNotIsset` became final
    - [BC] Class `Psalm\Storage\Assertion\NonEmptyCountable` became final
    - [BC] Class `Psalm\Storage\Assertion\NestedAssertions` became final
    - [BC] Class `Psalm\Storage\Assertion\Falsy` became final
    - [BC] Class `Psalm\Storage\Assertion\IsNotLooselyEqual` became final
    - [BC] Class `Psalm\Storage\Assertion\IsEqualIsset` became final
    - [BC] Class `Psalm\Storage\Assertion\IsLessThan` became final
    - [BC] Class `Psalm\Storage\Assertion\DoesNotHaveExactCount` became final
    - [BC] Class `Psalm\Storage\Assertion\IsCountable` became final
    - [BC] Class `Psalm\Storage\Assertion\NotInArray` became final
    - [BC] Class `Psalm\Storage\Assertion\InArray` became final
    - [BC] Class `Psalm\Storage\FunctionLikeParameter` became final
    - [BC] Class `Psalm\Storage\Possibilities` became final
    - [BC] Class `Psalm\Storage\ClassConstantStorage` became final
    - [BC] Class `Psalm\Storage\ClassLikeStorage` became final
    - [BC] Class `Psalm\Storage\AttributeArg` became final
    - [BC] Class `Psalm\Storage\EnumCaseStorage` became final
 - VirtualNode classes became final
    - [BC] Class `Psalm\Node\Stmt\VirtualFunction` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualClassConst` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualTraitUse` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualElseIf` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualDeclare` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualHaltCompiler` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualThrow` became final
    - [BC] Class `Psalm\Node\Stmt\TraitUseAdaptation\VirtualAlias` became final
    - [BC] Class `Psalm\Node\Stmt\TraitUseAdaptation\VirtualPrecedence` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualNamespace` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualIf` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualStatic` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualInlineHTML` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualUseUse` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualCatch` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualDeclareDeclare` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualEcho` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualFinally` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualInterface` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualGlobal` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualGroupUse` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualLabel` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualTrait` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualClass` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualUse` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualProperty` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualUnset` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualPropertyProperty` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualExpression` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualSwitch` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualStaticVar` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualClassMethod` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualNop` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualReturn` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualDo` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualBreak` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualElse` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualContinue` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualForeach` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualGoto` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualWhile` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualFor` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualCase` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualTryCatch` became final
    - [BC] Class `Psalm\Node\Stmt\VirtualConst` became final
    - [BC] Class `Psalm\Node\VirtualAttribute` became final
    - [BC] Class `Psalm\Node\VirtualArg` became final
    - [BC] Class `Psalm\Node\Expr\VirtualUnaryPlus` became final
    - [BC] Class `Psalm\Node\Expr\VirtualMatch` became final
    - [BC] Class `Psalm\Node\Expr\VirtualNullsafeMethodCall` became final
    - [BC] Class `Psalm\Node\Expr\VirtualTernary` became final
    - [BC] Class `Psalm\Node\Expr\VirtualThrow` became final
    - [BC] Class `Psalm\Node\Expr\VirtualNew` became final
    - [BC] Class `Psalm\Node\Expr\VirtualEmpty` became final
    - [BC] Class `Psalm\Node\Expr\VirtualStaticPropertyFetch` became final
    - [BC] Class `Psalm\Node\Expr\VirtualUnaryMinus` became final
    - [BC] Class `Psalm\Node\Expr\VirtualStaticCall` became final
    - [BC] Class `Psalm\Node\Expr\VirtualPostInc` became final
    - [BC] Class `Psalm\Node\Expr\VirtualPreDec` became final
    - [BC] Class `Psalm\Node\Expr\VirtualAssign` became final
    - [BC] Class `Psalm\Node\Expr\VirtualErrorSuppress` became final
    - [BC] Class `Psalm\Node\Expr\VirtualPreInc` became final
    - [BC] Class `Psalm\Node\Expr\VirtualArray` became final
    - [BC] Class `Psalm\Node\Expr\VirtualArrayItem` became final
    - [BC] Class `Psalm\Node\Expr\VirtualIsset` became final
    - [BC] Class `Psalm\Node\Expr\VirtualClone` became final
    - [BC] Class `Psalm\Node\Expr\VirtualConstFetch` became final
    - [BC] Class `Psalm\Node\Expr\VirtualEval` became final
    - [BC] Class `Psalm\Node\Expr\VirtualPrint` became final
    - [BC] Class `Psalm\Node\Expr\VirtualError` became final
    - [BC] Class `Psalm\Node\Expr\VirtualClosure` became final
    - [BC] Class `Psalm\Node\Expr\VirtualNullsafePropertyFetch` became final
    - [BC] Class `Psalm\Node\Expr\VirtualArrowFunction` became final
    - [BC] Class `Psalm\Node\Expr\VirtualBooleanNot` became final
    - [BC] Class `Psalm\Node\Expr\VirtualPropertyFetch` became final
    - [BC] Class `Psalm\Node\Expr\Cast\VirtualArray` became final
    - [BC] Class `Psalm\Node\Expr\Cast\VirtualInt` became final
    - [BC] Class `Psalm\Node\Expr\Cast\VirtualObject` became final
    - [BC] Class `Psalm\Node\Expr\Cast\VirtualDouble` became final
    - [BC] Class `Psalm\Node\Expr\Cast\VirtualUnset` became final
    - [BC] Class `Psalm\Node\Expr\Cast\VirtualBool` became final
    - [BC] Class `Psalm\Node\Expr\Cast\VirtualString` became final
    - [BC] Class `Psalm\Node\Expr\VirtualMethodCall` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualBitwiseAnd` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualCoalesce` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualDiv` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualNotIdentical` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualLogicalAnd` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualSpaceship` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualGreater` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualShiftRight` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualIdentical` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualMul` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualLogicalOr` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualBitwiseXor` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualSmallerOrEqual` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualNotEqual` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualGreaterOrEqual` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualMinus` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualEqual` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualSmaller` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualLogicalXor` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualMod` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualBooleanAnd` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualPlus` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualShiftLeft` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualBooleanOr` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualBitwiseOr` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualConcat` became final
    - [BC] Class `Psalm\Node\Expr\BinaryOp\VirtualPow` became final
    - [BC] Class `Psalm\Node\Expr\VirtualVariable` became final
    - [BC] Class `Psalm\Node\Expr\VirtualBitwiseNot` became final
    - [BC] Class `Psalm\Node\Expr\VirtualShellExec` became final
    - [BC] Class `Psalm\Node\Expr\VirtualFuncCall` became final
    - [BC] Class `Psalm\Node\Expr\VirtualInclude` became final
    - [BC] Class `Psalm\Node\Expr\VirtualAssignRef` became final
    - [BC] Class `Psalm\Node\Expr\VirtualClassConstFetch` became final
    - [BC] Class `Psalm\Node\Expr\VirtualExit` became final
    - [BC] Class `Psalm\Node\Expr\VirtualArrayDimFetch` became final
    - [BC] Class `Psalm\Node\Expr\VirtualList` became final
    - [BC] Class `Psalm\Node\Expr\VirtualYield` became final
    - [BC] Class `Psalm\Node\Expr\VirtualYieldFrom` became final
    - [BC] Class `Psalm\Node\Expr\VirtualClosureUse` became final
    - [BC] Class `Psalm\Node\Expr\VirtualPostDec` became final
    - [BC] Class `Psalm\Node\Expr\AssignOp\VirtualBitwiseAnd` became final
    - [BC] Class `Psalm\Node\Expr\AssignOp\VirtualCoalesce` became final
    - [BC] Class `Psalm\Node\Expr\AssignOp\VirtualDiv` became final
    - [BC] Class `Psalm\Node\Expr\AssignOp\VirtualShiftRight` became final
    - [BC] Class `Psalm\Node\Expr\AssignOp\VirtualMul` became final
    - [BC] Class `Psalm\Node\Expr\AssignOp\VirtualBitwiseXor` became final
    - [BC] Class `Psalm\Node\Expr\AssignOp\VirtualMinus` became final
    - [BC] Class `Psalm\Node\Expr\AssignOp\VirtualMod` became final
    - [BC] Class `Psalm\Node\Expr\AssignOp\VirtualPlus` became final
    - [BC] Class `Psalm\Node\Expr\AssignOp\VirtualShiftLeft` became final
    - [BC] Class `Psalm\Node\Expr\AssignOp\VirtualBitwiseOr` became final
    - [BC] Class `Psalm\Node\Expr\AssignOp\VirtualConcat` became final
    - [BC] Class `Psalm\Node\Expr\AssignOp\VirtualPow` became final
    - [BC] Class `Psalm\Node\Expr\VirtualInstanceof` became final
    - [BC] Class `Psalm\Node\VirtualNullableType` became final
    - [BC] Class `Psalm\Node\VirtualMatchArm` became final
    - [BC] Class `Psalm\Node\VirtualIdentifier` became final
    - [BC] Class `Psalm\Node\VirtualName` became final
    - [BC] Class `Psalm\Node\VirtualParam` became final
    - [BC] Class `Psalm\Node\VirtualAttributeGroup` became final
    - [BC] Class `Psalm\Node\VirtualVarLikeIdentifier` became final
    - [BC] Class `Psalm\Node\Name\VirtualRelative` became final
    - [BC] Class `Psalm\Node\Name\VirtualFullyQualified` became final
    - [BC] Class `Psalm\Node\VirtualUnionType` became final
    - [BC] Class `Psalm\Node\Scalar\VirtualLNumber` became final
    - [BC] Class `Psalm\Node\Scalar\VirtualDNumber` became final
    - [BC] Class `Psalm\Node\Scalar\MagicConst\VirtualFunction` became final
    - [BC] Class `Psalm\Node\Scalar\MagicConst\VirtualNamespace` became final
    - [BC] Class `Psalm\Node\Scalar\MagicConst\VirtualMethod` became final
    - [BC] Class `Psalm\Node\Scalar\MagicConst\VirtualLine` became final
    - [BC] Class `Psalm\Node\Scalar\MagicConst\VirtualTrait` became final
    - [BC] Class `Psalm\Node\Scalar\MagicConst\VirtualClass` became final
    - [BC] Class `Psalm\Node\Scalar\MagicConst\VirtualDir` became final
    - [BC] Class `Psalm\Node\Scalar\MagicConst\VirtualFile` became final
    - [BC] Class `Psalm\Node\Scalar\VirtualEncapsedStringPart` became final
    - [BC] Class `Psalm\Node\Scalar\VirtualString` became final
    - [BC] Class `Psalm\Node\Scalar\VirtualEncapsed` became final
    - [BC] Class `Psalm\Node\VirtualConst` became final
 - Type nodes became final
    - [BC] Class `Psalm\Type\TaintKindGroup` became final
    - [BC] Class `Psalm\Type\Atomic\TNumericString` became final
    - [BC] Class `Psalm\Type\Atomic\TClassStringMap` became final
    - [BC] Class `Psalm\Type\Atomic\TEmptyNumeric` became final
    - [BC] Class `Psalm\Type\Atomic\TCallableObject` became final
    - [BC] Class `Psalm\Type\Atomic\TSingleLetter` became final
    - [BC] Class `Psalm\Type\Atomic\TClosedResource` became final
    - [BC] Class `Psalm\Type\Atomic\TIntMaskOf` became final
    - [BC] Class `Psalm\Type\Atomic\TNonEmptyScalar` became final
    - [BC] Class `Psalm\Type\Atomic\TLowercaseString` became final
    - [BC] Class `Psalm\Type\Atomic\TCallable` became final
    - [BC] Class `Psalm\Type\Atomic\TFalse` became final
    - [BC] Class `Psalm\Type\Atomic\TIterable` became final
    - [BC] Class `Psalm\Type\Atomic\TTraitString` became final
    - [BC] Class `Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString` became final
    - [BC] Class `Psalm\Type\Atomic\TLiteralInt` became final
    - [BC] Class `Psalm\Type\Atomic\TTrue` became final
    - [BC] Class `Psalm\Type\Atomic\TDependentGetClass` became final
    - [BC] Class `Psalm\Type\Atomic\TValueOf` became final
    - [BC] Class `Psalm\Type\Atomic\TGenericObject` became final
    - [BC] Class `Psalm\Type\Atomic\TNonEmptyLowercaseString` became final
    - [BC] Class `Psalm\Type\Atomic\TEnumCase` became final
    - [BC] Class `Psalm\Type\Atomic\TCallableKeyedArray` became final
    - [BC] Class `Psalm\Type\Atomic\TDependentGetDebugType` became final
    - [BC] Class `Psalm\Type\Atomic\TKeyOf` became final
    - [BC] Class `Psalm\Type\Atomic\TNonspecificLiteralInt` became final
    - [BC] Class `Psalm\Type\Atomic\TObjectWithProperties` became final
    - [BC] Class `Psalm\Type\Atomic\TTemplateValueOf` became final
    - [BC] Class `Psalm\Type\Atomic\TDependentListKey` became final
    - [BC] Class `Psalm\Type\Atomic\TConditional` became final
    - [BC] Class `Psalm\Type\Atomic\TIntRange` became final
    - [BC] Class `Psalm\Type\Atomic\TCallableString` became final
    - [BC] Class `Psalm\Type\Atomic\TClosure` became final
    - [BC] Class `Psalm\Type\Atomic\TTypeAlias` became final
    - [BC] Class `Psalm\Type\Atomic\TAnonymousClassInstance` became final
    - [BC] Class `Psalm\Type\Atomic\TIntMask` became final
    - [BC] Class `Psalm\Type\Atomic\TTemplateKeyOf` became final
    - [BC] Class `Psalm\Type\Atomic\TDependentGetType` became final
    - [BC] Class `Psalm\Type\Atomic\TLiteralFloat` became final
    - [BC] Class `Psalm\Type\Atomic\TCallableArray` became final
    - [BC] Class `Psalm\Type\Atomic\TNonEmptyMixed` became final
    - [BC] Class `Psalm\Type\Atomic\TTemplateParamClass` became final
    - [BC] Class `Psalm\Type\Atomic\TTemplateIndexedAccess` became final
    - [BC] Class `Psalm\Type\Atomic\TEmptyScalar` became final
    - [BC] Class `Psalm\Type\Atomic\TNever` became final
    - [BC] Class `Psalm\Type\Atomic\TNull` became final
    - [BC] Class `Psalm\Type\Atomic\TTemplateParam` became final
    - [BC] Class `Psalm\Type\Atomic\TLiteralClassString` became final
    - [BC] Class `Psalm\Type\Atomic\TResource` became final
    - [BC] Class `Psalm\Type\Atomic\TVoid` became final
    - [BC] Class `Psalm\Type\Atomic\TCallableList` became final
    - [BC] Class `Psalm\Type\Atomic\TEmptyMixed` became final
    - [BC] Class `Psalm\Type\Atomic\TClassConstant` became final
    - [BC] Class `Psalm\Type\TaintKind` became final
    - [BC] Class `Psalm\Type\Union` became final
 - [BC] Property `Psalm\Config::$universal_object_crates` changed default value
   from `array{'stdClass','SimpleXMLElement','SimpleXMLIterator'}` to `null`

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
 - [BC] Property `Psalm\Config::$load_xdebug_stub` was removed
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
 - [BC] Property `Psalm\Context::$vars_from_global` has been renamed to `$referenced_globals`
 - [BC] Self-registration of file type scanners and file type analyzers has been changed
   - `Psalm\Plugin\RegistrationInterface::addFileTypeScanner` was removed
   - `Psalm\Plugin\RegistrationInterface::addFileTypeAnalyzer` was removed
   - :information_source: migration possible using `Psalm\Plugin\FileExtensionsInterface`
   - `Psalm\PluginRegistrationSocket::addFileTypeScanner` was removed
   - `Psalm\PluginRegistrationSocket::getAdditionalFileTypeScanners` was removed
   - `Psalm\PluginRegistrationSocket::addFileTypeAnalyzer` was removed
   - `Psalm\PluginRegistrationSocket::getAdditionalFileTypeAnalyzers` was removed
   - `Psalm\PluginRegistrationSocket::getAdditionalFileExtensions` was removed
   - `Psalm\PluginRegistrationSocket::addFileExtension` was removed
   - :information_source: migration possible using `Psalm\PluginFileExtensionsSocket`
 - [BC] Method `\Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent::getClasslikeStorage()` was removed,
   use correct `\Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent::getFunctionlikeStorage()` instead
