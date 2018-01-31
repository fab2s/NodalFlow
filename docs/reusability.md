# Code re-usability

NodalFlow allows vast possibilities to reuse the code once written for any workflow. You can for example use an Exec Node logic in any other context:

```php
$node->exec($param);
```

 or wrapped in a flow:
 
```php
(new NodalFlow)->add($node)->exec($param);
```

And the same goes with Traversable Nodes:

```php
foreach ($traversableNode->getTraversable($param) as $value) {
    // do something with $value
}
```

All this means that while implementing flows, you create other opportunities either withing or outside the flow which will save more and more time over time, as long as you need some sort of flows.

And in fact, the overhead of doing so is very small, especially if your Traversable Node is a [`Generator`](http://php.net/Generator) yielding values.
