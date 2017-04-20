# Interruptions

Each Node may issue interruptions that will act similarly to `continue` and `break` as if the whole branch was a single loop. This means that `continue` will only skip current action in chain and the flow will continue with the first upstream traversable next value (if any), while `break` will terminate the whole workflow.

Each nodes is filled with it's carrier flow when it is attached to it. Extending the provided `NodeAbstract` you can do :
```php
// skip this very action
$this->carrier->continueFlow();
```
or
```php
// stop the whole flow right here
$this->carrier->breakFlow();
```

whenever you need to in the `getTraversable()` and / or `exec()` methods to `continue` or `break` the flow.
