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

 - [AbstractMethodCall](issues/AbstractMethodCall)
 - [DuplicateArrayKey](issues/DuplicateArrayKey)
 - [DuplicateClass](issues/DuplicateClass)
 - [DuplicateFunction](issues/DuplicateFunction)
 - [DuplicateMethod](issues/DuplicateMethod)
 - [DuplicateParam](issues/DuplicateParam)
 - [EmptyArrayAccess](issues/EmptyArrayAccess)
 - [ImpureByReferenceAssignment](issues/ImpureByReferenceAssignment)
 - [ImpureFunctionCall](issues/ImpureFunctionCall)
 - [ImpureMethodCall](issues/ImpureMethodCall)
 - [ImpurePropertyAssignment](issues/ImpurePropertyAssignment)
 - [ImpureStaticProperty](issues/ImpureStaticProperty)
 - [ImpureStaticVariable](issues/ImpureStaticVariable)
 - [InaccessibleClassConstant](issues/InaccessibleClassConstant)
 - [InaccessibleMethod](issues/InaccessibleMethod)
 - [InaccessibleProperty](issues/InaccessibleProperty)
 - [InterfaceInstantiation](issues/InterfaceInstantiation)
 - [InvalidGlobal](issues/InvalidGlobal)
 - [InvalidParamDefault](issues/InvalidParamDefault)
 - [InvalidParent](issues/InvalidParent)
 - [InvalidPassByReference](issues/InvalidPassByReference)
 - [InvalidScope](issues/InvalidScope)
 - [InvalidStaticInvocation](issues/InvalidStaticInvocation)
 - [InvalidThrow](issues/InvalidThrow)
 - [LoopInvalidation](issues/LoopInvalidation)
 - [MethodSignatureMustOmitReturnType](issues/MethodSignatureMustOmitReturnType)
 - [MissingDependency](issues/MissingDependency)
 - [MissingFile](issues/MissingFile)
 - [MissingImmutableAnnotation](issues/MissingImmutableAnnotation)
 - [MissingTemplateParam](issues/MissingTemplateParam)
 - [MissingThrowsDocblock](issues/MissingThrowsDocblock)
 - [NoValue](issues/NoValue)
 - [NonStaticSelfCall](issues/NonStaticSelfCall)
 - [NullArrayAccess](issues/NullArrayAccess)
 - [NullFunctionCall](issues/NullFunctionCall)
 - [NullIterator](issues/NullIterator)
 - [NullPropertyAssignment](issues/NullPropertyAssignment)
 - [NullPropertyFetch](issues/NullPropertyFetch)
 - [NullReference](issues/NullReference)
 - [OverriddenPropertyAccess](issues/OverriddenPropertyAccess)
 - [ParadoxicalCondition](issues/ParadoxicalCondition)
 - [ParentNotFound](issues/ParentNotFound)
 - [TooFewArguments](issues/TooFewArguments)
 - [UndefinedClass](issues/UndefinedClass)
 - [UndefinedConstant](issues/UndefinedConstant)
 - [UndefinedDocblockClass](issues/UndefinedDocblockClass)
 - [UndefinedFunction](issues/UndefinedFunction)
 - [UndefinedGlobalVariable](issues/UndefinedGlobalVariable)
 - [UndefinedInterface](issues/UndefinedInterface)
 - [UndefinedTrait](issues/UndefinedTrait)
 - [UndefinedVariable](issues/UndefinedVariable)
 - [UnimplementedAbstractMethod](issues/UnimplementedAbstractMethod)
 - [UnimplementedInterfaceMethod](issues/UnimplementedInterfaceMethod)
 - [UnrecognizedExpression](issues/UnrecognizedExpression)
 - [UnrecognizedStatement](issues/UnrecognizedStatement)
 - [UnusedFunctionCall](issues/UnusedFunctionCall)
 - [UnusedMethodCall](issues/UnusedMethodCall)

## Errors that only appear at level 1

 - [LessSpecificReturnType](issues/LessSpecificReturnType)
 - [MixedArgument](issues/MixedArgument)
 - [MixedArgumentTypeCoercion](issues/MixedArgumentTypeCoercion)
 - [MixedArrayAccess](issues/MixedArrayAccess)
 - [MixedArrayAssignment](issues/MixedArrayAssignment)
 - [MixedArrayOffset](issues/MixedArrayOffset)
 - [MixedArrayTypeCoercion](issues/MixedArrayTypeCoercion)
 - [MixedAssignment](issues/MixedAssignment)
 - [MixedFunctionCall](issues/MixedFunctionCall)
 - [MixedInferredReturnType](issues/MixedInferredReturnType)
 - [MixedMethodCall](issues/MixedMethodCall)
 - [MixedOperand](issues/MixedOperand)
 - [MixedPropertyAssignment](issues/MixedPropertyAssignment)
 - [MixedPropertyFetch](issues/MixedPropertyFetch)
 - [MixedPropertyTypeCoercion](issues/MixedPropertyTypeCoercion)
 - [MixedReturnStatement](issues/MixedReturnStatement)
 - [MixedReturnTypeCoercion](issues/MixedReturnTypeCoercion)
 - [MixedStringOffsetAssignment](issues/MixedStringOffsetAssignment)
 - [MixedTypeCoercion](issues/MixedTypeCoercion)
 - [MutableDependency](issues/MutableDependency)
 - [PossiblyNullOperand](issues/PossiblyNullOperand)

## Errors ignored at level 3 and higher

These issues are treated as errors at level 2 and below.

 - [DeprecatedClass](issues/DeprecatedClass)
 - [DeprecatedConstant](issues/DeprecatedConstant)
 - [DeprecatedFunction](issues/DeprecatedFunction)
 - [DeprecatedInterface](issues/DeprecatedInterface)
 - [DeprecatedMethod](issues/DeprecatedMethod)
 - [DeprecatedProperty](issues/DeprecatedProperty)
 - [DeprecatedTrait](issues/DeprecatedTrait)
 - [DocblockTypeContradiction](issues/DocblockTypeContradiction)
 - [InvalidDocblockParamName](issues/InvalidDocblockParamName)
 - [InvalidFalsableReturnType](issues/InvalidFalsableReturnType)
 - [InvalidStringClass](issues/InvalidStringClass)
 - [MisplacedRequiredParam](issues/MisplacedRequiredParam)
 - [MissingClosureParamType](issues/MissingClosureParamType)
 - [MissingClosureReturnType](issues/MissingClosureReturnType)
 - [MissingConstructor](issues/MissingConstructor)
 - [MissingParamType](issues/MissingParamType)
 - [MissingPropertyType](issues/MissingPropertyType)
 - [MissingReturnType](issues/MissingReturnType)
 - [NullOperand](issues/NullOperand)
 - [PropertyNotSetInConstructor](issues/PropertyNotSetInConstructor)
 - [RawObjectIteration](issues/RawObjectIteration)
 - [RedundantConditionGivenDocblockType](issues/RedundantConditionGivenDocblockType)
 - [ReferenceConstraintViolation](issues/ReferenceConstraintViolation)
 - [UnresolvableInclude](issues/UnresolvableInclude)

## Errors ignored at level 4 and higher

These issues are treated as errors at level 3 and below.

 - [ArgumentTypeCoercion](issues/ArgumentTypeCoercion)
 - [LessSpecificReturnStatement](issues/LessSpecificReturnStatement)
 - [MoreSpecificReturnType](issues/MoreSpecificReturnType)
 - [PossiblyFalseArgument](issues/PossiblyFalseArgument)
 - [PossiblyFalseIterator](issues/PossiblyFalseIterator)
 - [PossiblyFalseOperand](issues/PossiblyFalseOperand)
 - [PossiblyFalsePropertyAssignmentValue](issues/PossiblyFalsePropertyAssignmentValue)
 - [PossiblyFalseReference](issues/PossiblyFalseReference)
 - [PossiblyInvalidArgument](issues/PossiblyInvalidArgument)
 - [PossiblyInvalidArrayAccess](issues/PossiblyInvalidArrayAccess)
 - [PossiblyInvalidArrayAssignment](issues/PossiblyInvalidArrayAssignment)
 - [PossiblyInvalidArrayOffset](issues/PossiblyInvalidArrayOffset)
 - [PossiblyInvalidCast](issues/PossiblyInvalidCast)
 - [PossiblyInvalidFunctionCall](issues/PossiblyInvalidFunctionCall)
 - [PossiblyInvalidIterator](issues/PossiblyInvalidIterator)
 - [PossiblyInvalidMethodCall](issues/PossiblyInvalidMethodCall)
 - [PossiblyInvalidOperand](issues/PossiblyInvalidOperand)
 - [PossiblyInvalidPropertyAssignment](issues/PossiblyInvalidPropertyAssignment)
 - [PossiblyInvalidPropertyAssignmentValue](issues/PossiblyInvalidPropertyAssignmentValue)
 - [PossiblyInvalidPropertyFetch](issues/PossiblyInvalidPropertyFetch)
 - [PossiblyNullArgument](issues/PossiblyNullArgument)
 - [PossiblyNullArrayAccess](issues/PossiblyNullArrayAccess)
 - [PossiblyNullArrayAssignment](issues/PossiblyNullArrayAssignment)
 - [PossiblyNullArrayOffset](issues/PossiblyNullArrayOffset)
 - [PossiblyNullFunctionCall](issues/PossiblyNullFunctionCall)
 - [PossiblyNullIterator](issues/PossiblyNullIterator)
 - [PossiblyNullPropertyAssignment](issues/PossiblyNullPropertyAssignment)
 - [PossiblyNullPropertyAssignmentValue](issues/PossiblyNullPropertyAssignmentValue)
 - [PossiblyNullPropertyFetch](issues/PossiblyNullPropertyFetch)
 - [PossiblyNullReference](issues/PossiblyNullReference)
 - [PossiblyUndefinedArrayOffset](issues/PossiblyUndefinedArrayOffset)
 - [PossiblyUndefinedGlobalVariable](issues/PossiblyUndefinedGlobalVariable)
 - [PossiblyUndefinedMethod](issues/PossiblyUndefinedMethod)
 - [PossiblyUndefinedVariable](issues/PossiblyUndefinedVariable)
 - [PropertyTypeCoercion](issues/PropertyTypeCoercion)
 - [TypeCoercion](issues/TypeCoercion)

## Errors ignored at level 5 and higher

These issues are treated as errors at level 4 and below.

 - [FalseOperand](issues/FalseOperand)
 - [ForbiddenCode](issues/ForbiddenCode)
 - [ImplementedParamTypeMismatch](issues/ImplementedParamTypeMismatch)
 - [ImplementedReturnTypeMismatch](issues/ImplementedReturnTypeMismatch)
 - [ImplicitToStringCast](issues/ImplicitToStringCast)
 - [InternalClass](issues/InternalClass)
 - [InternalMethod](issues/InternalMethod)
 - [InternalProperty](issues/InternalProperty)
 - [InvalidDocblock](issues/InvalidDocblock)
 - [InvalidOperand](issues/InvalidOperand)
 - [InvalidScalarArgument](issues/InvalidScalarArgument)
 - [InvalidToString](issues/InvalidToString)
 - [MismatchingDocblockParamType](issues/MismatchingDocblockParamType)
 - [MismatchingDocblockReturnType](issues/MismatchingDocblockReturnType)
 - [MissingDocblockType](issues/MissingDocblockType)
 - [NoInterfaceProperties](issues/NoInterfaceProperties)
 - [PossibleRawObjectIteration](issues/PossibleRawObjectIteration)
 - [RedundantCondition](issues/RedundantCondition)
 - [StringIncrement](issues/StringIncrement)
 - [TooManyArguments](issues/TooManyArguments)
 - [TypeDoesNotContainNull](issues/TypeDoesNotContainNull)
 - [TypeDoesNotContainType](issues/TypeDoesNotContainType)
 - [UndefinedMagicMethod](issues/UndefinedMagicMethod)
 - [UndefinedMagicPropertyAssignment](issues/UndefinedMagicPropertyAssignment)
 - [UndefinedMagicPropertyFetch](issues/UndefinedMagicPropertyFetch)

## Errors ignored at level 6 and higher

These issues are treated as errors at level 5 and below.

 - [FalsableReturnStatement](issues/FalsableReturnStatement)
 - [InvalidNullableReturnType](issues/InvalidNullableReturnType)
 - [LessSpecificImplementedReturnType](issues/LessSpecificImplementedReturnType)
 - [MoreSpecificImplementedParamType](issues/MoreSpecificImplementedParamType)
 - [NullableReturnStatement](issues/NullableReturnStatement)
 - [UndefinedInterfaceMethod](issues/UndefinedInterfaceMethod)
 - [UndefinedThisPropertyAssignment](issues/UndefinedThisPropertyAssignment)

## Errors ignored at level 7 and higher

These issues are treated as errors at level 6 and below.

 - [InvalidArgument](issues/InvalidArgument)
 - [InvalidArrayAccess](issues/InvalidArrayAccess)
 - [InvalidArrayAssignment](issues/InvalidArrayAssignment)
 - [InvalidArrayOffset](issues/InvalidArrayOffset)
 - [InvalidCast](issues/InvalidCast)
 - [InvalidCatch](issues/InvalidCatch)
 - [InvalidClass](issues/InvalidClass)
 - [InvalidClone](issues/InvalidClone)
 - [InvalidFunctionCall](issues/InvalidFunctionCall)
 - [InvalidIterator](issues/InvalidIterator)
 - [InvalidMethodCall](issues/InvalidMethodCall)
 - [InvalidPropertyAssignment](issues/InvalidPropertyAssignment)
 - [InvalidPropertyAssignmentValue](issues/InvalidPropertyAssignmentValue)
 - [InvalidPropertyFetch](issues/InvalidPropertyFetch)
 - [InvalidReturnStatement](issues/InvalidReturnStatement)
 - [InvalidReturnType](issues/InvalidReturnType)
 - [InvalidTemplateParam](issues/InvalidTemplateParam)
 - [NullArgument](issues/NullArgument)
 - [NullArrayOffset](issues/NullArrayOffset)
 - [TooManyTemplateParams](issues/TooManyTemplateParams)
 - [TraitMethodSignatureMismatch](issues/TraitMethodSignatureMismatch)
 - [UndefinedMethod](issues/UndefinedMethod)
 - [UndefinedPropertyAssignment](issues/UndefinedPropertyAssignment)
 - [UndefinedPropertyFetch](issues/UndefinedPropertyFetch)
 - [UndefinedThisPropertyFetch](issues/UndefinedThisPropertyFetch)

## Errors ignored at level 8

These issues are treated as errors at level 7 and below.

 - [AbstractInstantiation](issues/AbstractInstantiation)
 - [AssignmentToVoid](issues/AssignmentToVoid)
 - [CircularReference](issues/CircularReference)
 - [ConflictingReferenceConstraint](issues/ConflictingReferenceConstraint)
 - [ContinueOutsideLoop](issues/ContinueOutsideLoop)
 - [MethodSignatureMismatch](issues/MethodSignatureMismatch)
 - [OverriddenMethodAccess](issues/OverriddenMethodAccess)
 - [ReservedWord](issues/ReservedWord)
 - [UninitializedProperty](issues/UninitializedProperty)


## Feature-specific errors

 - [ForbiddenEcho](issues/ForbiddenEcho)
 - [PossiblyUndefinedIntArrayOffset](issues/PossiblyUndefinedIntArrayOffset)
 - [PossiblyUndefinedStringArrayOffset](issues/PossiblyUndefinedStringArrayOffset)
 - [PossiblyUnusedMethod](issues/PossiblyUnusedMethod)
 - [PossiblyUnusedParam](issues/PossiblyUnusedParam)
 - [PossiblyUnusedProperty](issues/PossiblyUnusedProperty)
 - [TaintedInput](issues/TaintedInput)
 - [UncaughtThrowInGlobalScope](issues/UncaughtThrowInGlobalScope)
 - [UnevaluatedCode](issues/UnevaluatedCode)
 - [UnnecessaryVarAnnotation](issues/UnnecessaryVarAnnotation)
 - [UnusedClass](issues/UnusedClass)
 - [UnusedClosureParam](issues/UnusedClosureParam)
 - [UnusedMethod](issues/UnusedMethod)
 - [UnusedParam](issues/UnusedParam)
 - [UnusedProperty](issues/UnusedProperty)
 - [UnusedPsalmSuppress](issues/UnusedPsalmSuppress)
 - [UnusedVariable](issues/UnusedVariable)
