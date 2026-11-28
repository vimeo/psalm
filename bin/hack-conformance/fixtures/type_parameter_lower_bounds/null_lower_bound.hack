//// expect: no-errors
//// note: null is a value of a template with the lower bound null.
final class Sink<T super null> {
  public function put(T $v): void {}
  public function clear(): void { $this->put(null); }
}
