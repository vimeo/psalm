# Error levels

You can run Psalm in at different levels of strictness from 1 to 8.

Level 1 is the most strict, level 8 is the most lenient.

Some issues are [always treated as errors](#always-treated-as-errors). These are issues with a very low probability of false-positives.

At [level 1](#errors-that-only-appear-at-level-1) all issues (except those emitted for opt-in features) that Psalm can find are treated as errors. Those issues include any situation where Psalm cannot infer the type of a given expression.

At level 2 Psalm ignores those `Mixed*` issues, but treats most other issues as errors.

At level 3 Psalm starts to be a little more lenient. For example Psalm allows missing param types, return types and property types.

At level 4 Psalm ignores issues for _possible_ problems. These are more likely to be false positives – where the application code may guarantee behaviour that Psalm isn't able to infer.

Level 5 and above allows a more non-verifiable code, and higher levels are even more permissive.

## Always treated as errors

 - [DuplicateArrayKey](issues.md#duplicatearraykey)
 - [DuplicateClass](issues.md#duplicateclass)
 - [DuplicateFunction](issues.md#duplicatefunction)
 - [DuplicateMethod](issues.md#duplicatemethod)
 - [DuplicateParam](issues.md#duplicateparam)
 - [EmptyArrayAccess](issues.md#emptyarrayaccess)
 - [ImpureByReferenceAssignment](issues.md#impurebyreferenceassignment)
 - [ImpureFunctionCall](issues.md#impurefunctioncall)
 - [ImpureMethodCall](issues.md#impuremethodcall)
 - [ImpurePropertyAssignment](issues.md#impurepropertyassignment)
 - [ImpureStaticProperty](issues.md#impurestaticproperty)
 - [ImpureStaticVariable](issues.md#impurestaticvariable)
 - [InaccessibleClassConstant](issues.md#inaccessibleclassconstant)
 - [InaccessibleMethod](issues.md#inaccessiblemethod)
 - [InaccessibleProperty](issues.md#inaccessibleproperty)
 - [InterfaceInstantiation](issues.md#interfaceinstantiation)
 - [InvalidGlobal](issues.md#invalidglobal)
 - [InvalidParamDefault](issues.md#invalidparamdefault)
 - [InvalidParent](issues.md#invalidparent)
 - [InvalidPassByReference](issues.md#invalidpassbyreference)
 - [InvalidScope](issues.md#invalidscope)
 - [InvalidStaticInvocation](issues.md#invalidstaticinvocation)
 - [InvalidThrow](issues.md#invalidthrow)
 - [LoopInvalidation](issues.md#loopinvalidation)
 - [MethodSignatureMustOmitReturnType](issues.md#methodsignaturemustomitreturntype)
 - [MissingDependency](issues.md#missingdependency)
 - [MissingFile](issues.md#missingfile)
 - [MissingImmutableAnnotation](issues.md#missingimmutableannotation)
 - [MissingTemplateParam](issues.md#missingtemplateparam)
 - [MissingThrowsDocblock](issues.md#missingthrowsdocblock)
 - [NoValue](issues.md#novalue)
 - [NonStaticSelfCall](issues.md#nonstaticselfcall)
 - [NullArrayAccess](issues.md#nullarrayaccess)
 - [NullFunctionCall](issues.md#nullfunctioncall)
 - [NullIterator](issues.md#nulliterator)
 - [NullPropertyAssignment](issues.md#nullpropertyassignment)
 - [NullPropertyFetch](issues.md#nullpropertyfetch)
 - [NullReference](issues.md#nullreference)
 - [OverriddenPropertyAccess](issues.md#overriddenpropertyaccess)
 - [ParadoxicalCondition](issues.md#paradoxicalcondition)
 - [ParentNotFound](issues.md#parentnotfound)
 - [TooFewArguments](issues.md#toofewarguments)
 - [UndefinedClass](issues.md#undefinedclass)
 - [UndefinedConstant](issues.md#undefinedconstant)
 - [UndefinedDocblockClass](issues.md#undefineddocblockclass)
 - [UndefinedFunction](issues.md#undefinedfunction)
 - [UndefinedGlobalVariable](issues.md#undefinedglobalvariable)
 - [UndefinedInterface](issues.md#undefinedinterface)
 - [UndefinedTrait](issues.md#undefinedtrait)
 - [UndefinedVariable](issues.md#undefinedvariable)
 - [UnimplementedAbstractMethod](issues.md#unimplementedabstractmethod)
 - [UnimplementedInterfaceMethod](issues.md#unimplementedinterfacemethod)
 - [UnrecognizedExpression](issues.md#unrecognizedexpression)
 - [UnrecognizedStatement](issues.md#unrecognizedstatement)
 - [UnusedFunctionCall](issues.md#unusedfunctioncall)
 - [UnusedMethodCall](issues.md#unusedmethodcall)

## Errors that only appear at level 1

 - [LessSpecificReturnType](issues.md#lessspecificreturntype)
 - [MixedArgument](issues.md#mixedargument)
 - [MixedArgumentTypeCoercion](issues.md#mixedargumenttypecoercion)
 - [MixedArrayAccess](issues.md#mixedarrayaccess)
 - [MixedArrayAssignment](issues.md#mixedarrayassignment)
 - [MixedArrayOffset](issues.md#mixedarrayoffset)
 - [MixedArrayTypeCoercion](issues.md#mixedarraytypecoercion)
 - [MixedAssignment](issues.md#mixedassignment)
 - [MixedFunctionCall](issues.md#mixedfunctioncall)
 - [MixedInferredReturnType](issues.md#mixedinferredreturntype)
 - [MixedMethodCall](issues.md#mixedmethodcall)
 - [MixedOperand](issues.md#mixedoperand)
 - [MixedPropertyAssignment](issues.md#mixedpropertyassignment)
 - [MixedPropertyFetch](issues.md#mixedpropertyfetch)
 - [MixedPropertyTypeCoercion](issues.md#mixedpropertytypecoercion)
 - [MixedReturnStatement](issues.md#mixedreturnstatement)
 - [MixedReturnTypeCoercion](issues.md#mixedreturntypecoercion)
 - [MixedStringOffsetAssignment](issues.md#mixedstringoffsetassignment)
 - [MixedTypeCoercion](issues.md#mixedtypecoercion)
 - [PossiblyNullOperand](issues.md#possiblynulloperand)

## Errors ignored at level 3 and higher

These issues are treated as errors at level 2 and below.

 - [DeprecatedClass](issues.md#deprecatedclass)
 - [DeprecatedConstant](issues.md#deprecatedconstant)
 - [DeprecatedFunction](issues.md#deprecatedfunction)
 - [DeprecatedInterface](issues.md#deprecatedinterface)
 - [DeprecatedMethod](issues.md#deprecatedmethod)
 - [DeprecatedProperty](issues.md#deprecatedproperty)
 - [DeprecatedTrait](issues.md#deprecatedtrait)
 - [DocblockTypeContradiction](issues.md#docblocktypecontradiction)
 - [InvalidDocblockParamName](issues.md#invaliddocblockparamname)
 - [InvalidFalsableReturnType](issues.md#invalidfalsablereturntype)
 - [InvalidStringClass](issues.md#invalidstringclass)
 - [MisplacedRequiredParam](issues.md#misplacedrequiredparam)
 - [MissingClosureParamType](issues.md#missingclosureparamtype)
 - [MissingClosureReturnType](issues.md#missingclosurereturntype)
 - [MissingConstructor](issues.md#missingconstructor)
 - [MissingParamType](issues.md#missingparamtype)
 - [MissingPropertyType](issues.md#missingpropertytype)
 - [MissingReturnType](issues.md#missingreturntype)
 - [NullOperand](issues.md#nulloperand)
 - [PropertyNotSetInConstructor](issues.md#propertynotsetinconstructor)
 - [RawObjectIteration](issues.md#rawobjectiteration)
 - [RedundantConditionGivenDocblockType](issues.md#redundantconditiongivendocblocktype)
 - [ReferenceConstraintViolation](issues.md#referenceconstraintviolation)
 - [UnresolvableInclude](issues.md#unresolvableinclude)

## Errors ignored at level 4 and higher

These issues are treated as errors at level 3 and below.

 - [ArgumentTypeCoercion](issues.md#argumenttypecoercion)
 - [LessSpecificReturnStatement](issues.md#lessspecificreturnstatement)
 - [MoreSpecificReturnType](issues.md#morespecificreturntype)
 - [PossiblyFalseArgument](issues.md#possiblyfalseargument)
 - [PossiblyFalseIterator](issues.md#possiblyfalseiterator)
 - [PossiblyFalseOperand](issues.md#possiblyfalseoperand)
 - [PossiblyFalsePropertyAssignmentValue](issues.md#possiblyfalsepropertyassignmentvalue)
 - [PossiblyFalseReference](issues.md#possiblyfalsereference)
 - [PossiblyInvalidArgument](issues.md#possiblyinvalidargument)
 - [PossiblyInvalidArrayAccess](issues.md#possiblyinvalidarrayaccess)
 - [PossiblyInvalidArrayAssignment](issues.md#possiblyinvalidarrayassignment)
 - [PossiblyInvalidArrayOffset](issues.md#possiblyinvalidarrayoffset)
 - [PossiblyInvalidCast](issues.md#possiblyinvalidcast)
 - [PossiblyInvalidFunctionCall](issues.md#possiblyinvalidfunctioncall)
 - [PossiblyInvalidIterator](issues.md#possiblyinvaliditerator)
 - [PossiblyInvalidMethodCall](issues.md#possiblyinvalidmethodcall)
 - [PossiblyInvalidOperand](issues.md#possiblyinvalidoperand)
 - [PossiblyInvalidPropertyAssignment](issues.md#possiblyinvalidpropertyassignment)
 - [PossiblyInvalidPropertyAssignmentValue](issues.md#possiblyinvalidpropertyassignmentvalue)
 - [PossiblyInvalidPropertyFetch](issues.md#possiblyinvalidpropertyfetch)
 - [PossiblyNullArgument](issues.md#possiblynullargument)
 - [PossiblyNullArrayAccess](issues.md#possiblynullarrayaccess)
 - [PossiblyNullArrayAssignment](issues.md#possiblynullarrayassignment)
 - [PossiblyNullArrayOffset](issues.md#possiblynullarrayoffset)
 - [PossiblyNullFunctionCall](issues.md#possiblynullfunctioncall)
 - [PossiblyNullIterator](issues.md#possiblynulliterator)
 - [PossiblyNullPropertyAssignment](issues.md#possiblynullpropertyassignment)
 - [PossiblyNullPropertyAssignmentValue](issues.md#possiblynullpropertyassignmentvalue)
 - [PossiblyNullPropertyFetch](issues.md#possiblynullpropertyfetch)
 - [PossiblyNullReference](issues.md#possiblynullreference)
 - [PossiblyUndefinedArrayOffset](issues.md#possiblyundefinedarrayoffset)
 - [PossiblyUndefinedGlobalVariable](issues.md#possiblyundefinedglobalvariable)
 - [PossiblyUndefinedMethod](issues.md#possiblyundefinedmethod)
 - [PossiblyUndefinedVariable](issues.md#possiblyundefinedvariable)
 - [PropertyTypeCoercion](issues.md#propertytypecoercion)
 - [TypeCoercion](issues.md#typecoercion)

## Errors ignored at level 5 and higher

These issues are treated as errors at level 4 and below.

 - [FalseOperand](issues.md#falseoperand)
 - [ForbiddenCode](issues.md#forbiddencode)
 - [ImplementedParamTypeMismatch](issues.md#implementedparamtypemismatch)
 - [ImplementedReturnTypeMismatch](issues.md#implementedreturntypemismatch)
 - [ImplicitToStringCast](issues.md#implicittostringcast)
 - [InternalClass](issues.md#internalclass)
 - [InternalMethod](issues.md#internalmethod)
 - [InternalProperty](issues.md#internalproperty)
 - [InvalidDocblock](issues.md#invaliddocblock)
 - [InvalidOperand](issues.md#invalidoperand)
 - [InvalidScalarArgument](issues.md#invalidscalarargument)
 - [InvalidToString](issues.md#invalidtostring)
 - [MismatchingDocblockParamType](issues.md#mismatchingdocblockparamtype)
 - [MismatchingDocblockReturnType](issues.md#mismatchingdocblockreturntype)
 - [MissingDocblockType](issues.md#missingdocblocktype)
 - [NoInterfaceProperties](issues.md#nointerfaceproperties)
 - [PossibleRawObjectIteration](issues.md#possiblerawobjectiteration)
 - [RedundantCondition](issues.md#redundantcondition)
 - [StringIncrement](issues.md#stringincrement)
 - [TooManyArguments](issues.md#toomanyarguments)
 - [TypeDoesNotContainNull](issues.md#typedoesnotcontainnull)
 - [TypeDoesNotContainType](issues.md#typedoesnotcontaintype)
 - [UndefinedMagicMethod](issues.md#undefinedmagicmethod)
 - [UndefinedMagicPropertyAssignment](issues.md#undefinedmagicpropertyassignment)
 - [UndefinedMagicPropertyFetch](issues.md#undefinedmagicpropertyfetch)

## Errors ignored at level 6 and higher

These issues are treated as errors at level 5 and below.

 - [FalsableReturnStatement](issues.md#falsablereturnstatement)
 - [InvalidNullableReturnType](issues.md#invalidnullablereturntype)
 - [LessSpecificImplementedReturnType](issues.md#lessspecificimplementedreturntype)
 - [MoreSpecificImplementedParamType](issues.md#morespecificimplementedparamtype)
 - [NullableReturnStatement](issues.md#nullablereturnstatement)
 - [UndefinedInterfaceMethod](issues.md#undefinedinterfacemethod)
 - [UndefinedThisPropertyAssignment](issues.md#undefinedthispropertyassignment)

## Errors ignored at level 7 and higher

These issues are treated as errors at level 6 and below.

 - [InvalidArgument](issues.md#invalidargument)
 - [InvalidArrayAccess](issues.md#invalidarrayaccess)
 - [InvalidArrayAssignment](issues.md#invalidarrayassignment)
 - [InvalidArrayOffset](issues.md#invalidarrayoffset)
 - [InvalidCast](issues.md#invalidcast)
 - [InvalidCatch](issues.md#invalidcatch)
 - [InvalidClass](issues.md#invalidclass)
 - [InvalidClone](issues.md#invalidclone)
 - [InvalidFunctionCall](issues.md#invalidfunctioncall)
 - [InvalidIterator](issues.md#invaliditerator)
 - [InvalidMethodCall](issues.md#invalidmethodcall)
 - [InvalidPropertyAssignment](issues.md#invalidpropertyassignment)
 - [InvalidPropertyAssignmentValue](issues.md#invalidpropertyassignmentvalue)
 - [InvalidPropertyFetch](issues.md#invalidpropertyfetch)
 - [InvalidReturnStatement](issues.md#invalidreturnstatement)
 - [InvalidReturnType](issues.md#invalidreturntype)
 - [InvalidTemplateParam](issues.md#invalidtemplateparam)
 - [NullArgument](issues.md#nullargument)
 - [NullArrayOffset](issues.md#nullarrayoffset)
 - [TooManyTemplateParams](issues.md#toomanytemplateparams)
 - [TraitMethodSignatureMismatch](issues.md#traitmethodsignaturemismatch)
 - [UndefinedMethod](issues.md#undefinedmethod)
 - [UndefinedPropertyAssignment](issues.md#undefinedpropertyassignment)
 - [UndefinedPropertyFetch](issues.md#undefinedpropertyfetch)
 - [UndefinedThisPropertyFetch](issues.md#undefinedthispropertyfetch)

## Errors ignored at level 8

These issues are treated as errors at level 7 and below.

 - [AbstractInstantiation](issues.md#abstractinstantiation)
 - [AssignmentToVoid](issues.md#assignmenttovoid)
 - [CircularReference](issues.md#circularreference)
 - [ConflictingReferenceConstraint](issues.md#conflictingreferenceconstraint)
 - [ContinueOutsideLoop](issues.md#continueoutsideloop)
 - [MethodSignatureMismatch](issues.md#methodsignaturemismatch)
 - [OverriddenMethodAccess](issues.md#overriddenmethodaccess)
 - [ReservedWord](issues.md#reservedword)
 - [UninitializedProperty](issues.md#uninitializedproperty)


## Feature-specific errors

 - [ForbiddenEcho](issues.md#forbiddenecho)
 - [PossiblyUndefinedIntArrayOffset](issues.md#possiblyundefinedintarrayoffset)
 - [PossiblyUndefinedStringArrayOffset](issues.md#possiblyundefinedstringarrayoffset)
 - [PossiblyUnusedMethod](issues.md#possiblyunusedmethod)
 - [PossiblyUnusedParam](issues.md#possiblyunusedparam)
 - [PossiblyUnusedProperty](issues.md#possiblyunusedproperty)
 - [TaintedInput](issues.md#taintedinput)
 - [UncaughtThrowInGlobalScope](issues.md#uncaughtthrowinglobalscope)
 - [UnevaluatedCode](issues.md#unevaluatedcode)
 - [UnnecessaryVarAnnotation](issues.md#unnecessaryvarannotation)
 - [UnusedClass](issues.md#unusedclass)
 - [UnusedClosureParam](issues.md#unusedclosureparam)
 - [UnusedMethod](issues.md#unusedmethod)
 - [UnusedParam](issues.md#unusedparam)
 - [UnusedProperty](issues.md#unusedproperty)
 - [UnusedPsalmSuppress](issues.md#unusedpsalmsuppress)
 - [UnusedVariable](issues.md#unusedvariable)
