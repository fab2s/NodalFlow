# Callbacks (deprecated)

Although _deprecated_, Callbacks works just exactly as before, but you should consider using the new [Event handling implementation](events.md) for future work.

NodalFlow implements a KISS callback interface you can use to trigger callback events in various steps of the process.

- the `start($flow)` method is triggered when the Flow starts
- the `progress($flow, $node)` method is triggered each `$progressMod` time a full Flow iterates, which may occur whenever a `Traversable` node iterates.
- the `success($flow)` method is triggered when the Flow completes successfully
- the `fail($flow)` method is triggered when an exception was raised during the flow's execution. The exception is caught to perform few operations and re-thrown as is.

Each of these trigger slots takes current flow as first argument, for each slot to allow control of the carrying flow. Please note that the flow provided may be a branch in some upstream flow. `progress($flow, $node)` additionally gets the current node as second argument which allows you to eventually get more insights about what is going on.
Please note that there is no guarantee that you will see each node in `progress()` as this method is only triggered each `$progressMod` time the flow iterates, and this can occur in any `Traversable` node.

NodalFlow also implements two protected method that will be triggered just before and after the flow's execution, `flowStrat()` and `flowEnd($success)`. You can override them to add more logic. These are not treated as events as they are always used by NodalFlow to provide with basic statistics.

To use a callback, just implement `CallbackInterface` and inject it in the flow.

```php
$flow = new NodalFlow;
$callback = new ClassImplementingCallbackInterface;

$flow->setCallBack($callback);
```

A `CallbackAbstract` providing with a NoOp implementation of `CallbackInterface` was added in case you only need to override few of the interface methods without implementing the others.
