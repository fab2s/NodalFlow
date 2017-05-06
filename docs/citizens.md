# NodalFlow Citizens

A Flow is an executable workflow composed of a set of executable Nodes. They all carry a somehow executable logic and can be of several kinds :

## Exec Nodes:

An Exec Node is a node implementing `ExecNodeInterface` and thus exposing an `exec()` method which accepts one parameter and eventually returns a value that may or may not be used as argument to the next node in the flow. The eventual return value usage is defined when creating the Node, which means that a Node that returns a value may still be used as if if was not.

## Traversable Node:

A Traversable Node is a node that exposes a `getTraversable()` method from the `TraversableNodeInterface`. Its accepting one parameter and returning with a `Traversable` which may or may not spit values that may or may not be used as argument to the next node in the flow.

## Aggregate Node:

An Aggregate Node is a node that will aggregate several Traversable Node as if they where a single Node. Each Traversable Node in the Aggregate may or may not spit values that may or may not be used as argument to the next node in the Aggregate. And the Aggregate itself may also do the same with next nodes in the Flow.

## Payload Nodes:

A Payload Node is a node carrying an underlying payload which holds the execution logic. It is used to allow things like `Callable` Nodes where the execution logic is fully generic and cannot implement `NodeInterface` directly. It acts kind of like a proxy between the business payload and the workflow. Payload Nodes may be executable and / or Traversable depending on their initialization. In the Callable Node case, NodalFlow cannot predict if the underlying payload is Traversable, so it's up to the developer to properly initialize the node in such case.
Payload Nodes are meant to be immutable, and thus have no setters on $isAReturningVal and $isATraversable. Each usable Payload Nodes in NodalFlow extends from `PayloadNodeAbstract` using this constructor(the branch node currently forces `$isATraversable` to `false`):

```php
    /**
     * A Payload Node is supposed to be immutable, and thus
     * have no setters on $isAReturningVal and $isATraversable
     *
     * @param mixed $payload
     * @param bool  $isAReturningVal
     * @param bool  $isATraversable
     *
     * @throws \Exception
     */
    public function __construct($payload, $isAReturningVal, $isATraversable = false);
```

## Branch Node:

Branch Node is a Payload Node where the payload is a Flow. It will be treated as an exec node which may return a value that may (which results in executing the branch within the parent's Flow's flow, as if it was part of it) or may not (which result in a true branch which starts from a specific location in the parent's Flow's flow) be used as argument to the next node in the flow.
Branch Nodes cannot be traversed. It is not a technical limitation, but rather something that requires further thinking and may be later implemented.
