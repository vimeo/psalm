//// psalm-test: Psalm\Tests\CapabilitiesTest::dynamicNewChecksConstructor
//// expect: error
//// note: `new $c()` on a classname checks the constructor of that class. Psalm checks
////       the constructor of the class a class-string stands for, and treats an unknown
////       class as impure.

final class Noisy { public function __construct() { echo "created"; } }

function make(classname<Noisy> $c)[]: Noisy { return new $c(); }
