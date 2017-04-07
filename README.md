# NodalFlow

[![Build Status](https://travis-ci.org/fab2s/NodalFlow.svg?branch=master)](https://travis-ci.org/fab2s/NodalFlow) [![HHVM](https://img.shields.io/hhvm/fab2s/YaEtl.svg)](http://hhvm.h4cc.de/package/fab2s/nodalflow) [![Code Climate](https://codeclimate.com/github/fab2s/NodalFlow/badges/gpa.svg)](https://codeclimate.com/github/fab2s/NodalFlow) [![Codacy Badge](https://api.codacy.com/project/badge/Grade/0a68622246734a16983616188eeefa01)](https://www.codacy.com/app/fab2s/NodalFlow) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/fab2s/NodalFlow/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/fab2s/NodalFlow/?branch=master) [![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat)](http://makeapullrequest.com) [![License](https://poser.pugx.org/fab2s/nodalflow/license)](https://packagist.org/packages/fab2s/nodalflow)

NodalFlow is a generic Workflow that can execute chained tasks. It is designed around simple interfaces that specifies a flow composed of executable nodes and flows. Nodes can be executed or traversed. They accept a single parameter as argument and can be set to pass or not their result as an argument for the next node.
Flows also accept one argument and may be set to pass their result to be used or not as an argument for the next node.
If a node does not pass it's result as parameter to the next node, the current parameter will be used for the next node, and so on until one node returns a result intended to be used as argument to the next node.
In other words, NodalFlow implements a directed graph structure in the form of a tree composed of nodes that can, but not always are, branches or leaves.

NodalFlow aims at organizing and simplifying data processing workflows where arbitrary amount of data may come from various generators, pass through several data processors and / or end up in various places and formats. It makes it possible to dynamically configure and execute complex scenario in an organized and repeatable manner. And even more important, to write Nodes that will be reusable in any other workflow you may think of.

NodalFlow enforces minimalistic requirements upon nodes. This means that in most cases, you should extend `NodalFlow` to implement the required constraints and grammar for your use case.

[YaEtl](https://github.com/fab2s/YaEtl) is an example of a more specified workflow build upon [NodalFlow](https://github.com/fab2s/NodalFlow).

NodalFlow shares conceptual similarities with [Transduction](https://en.wikipedia.org/wiki/Transduction) as it allow basic interaction chaining, especially when dealing with `ExecNodes`, but the comparison diverges quickly.

## Traversability

A Traversable Node is a node that implement the `getTraversable` method as defined in `TraversableNodeInterface`. The `getTraversable` method returns a `Traversable` that will be iterated over during the flow's execution. In other words, a Traversable Node is a node that provides many values when invoked, with each values being fed as argument to the remaining nodes in the chain. This would be exactly what occurs if the `Traversable` where to be an array, but you can also use a `Generator` and `yield` results one by one, or whatever `Traversable`.

NodalFlow as a whole can thus be seen as a kind of dismantled "meta" loop upon each of its `Traversable` nodes with linear nodes in between, aka the Exec Nodes. Traversable Nodes can be aggregated, which results in all of them being looped upon as if they where a single data generator, or chained, which result in each of them being recursively iterated over (1st traversable 1st record -> 2nd traversable 1st records -> last traversable every records ...).

Upon each iteration, the remaining Nodes in the flow will be recursed on. This is for example useful when a data generator needs some kind of manipulation(s) and / or actions on each of his "records".

## Installation

NodalFlow can be installed using composer :

``` shell
composer require "fab2s/NodalFlow"
```

## NodalFlow Citizens

A Flow is an executable workflow composed of a set of executable Nodes. They all carry a somehow executable logic and can be of several kinds :

* Exec Nodes:

    An Exec Node is a node implementing `ExecNodeInterface` and thus exposing an `exec()` method which accepts one parameter and eventually returns a value that may or may not be used as argument to the next node in the flow. The eventual return value usage is defined when creating the Node, which means that a Node that returns a value may still be used as if if was not.

* Traversable Node:

    A Traversable Node is a node that exposes a `getTraversable()` method from the `TraversableNodeInterface`. Its accepting one parameter and returning with a `Traversable` which may or may not spit values that may or may not be used as argument to the next node in the flow.

* Aggregate Node:

    An Aggregate Node is a node that will aggregate several Traversable Node as if they where a single Node. Each Traversable Node in the Aggregate may or may not spit values that may or may not be used as argument to the next node in the Aggregate. And the Aggregate itself may also do the same with next nodes in the Flow.

* Payload Nodes:

    A Payload Node is a node carrying an underlying payload which holds the execution logic. It is used to allow things like `Callable` Nodes where the execution logic is fully generic and cannot implement `NodeInterface` directly. It acts kind of like a proxy between the business payload and the workflow. Payload Nodes may be executable and / or Traversable depending on their initialization. In the Callable Node case, NodalFlow cannot predict if the underlying payload is Traversable, so it's up to the developer to properly initialize the node in such case.

* Branch Node:

    Branch Node is a Payload Node where the payload is a Flow. It will be treated as an exec node which may return a value that may (which results in executing the branch within the parent's Flow's flow, as if it was part of it) or may not (which result in a true branch which starts from a specific location in the parent's Flow's flow) be used as argument to the next node in the flow.
    Branch Nodes cannot be traversed. It is not a technical limitation, but rather something that requires further thinking and may be later implemented.

Payload Nodes are supposed to be immutable, and thus have no setters on $isAReturningVal and $isATraversable. Each usable Payload Nodes in NodalFlow extends from `PayloadNodeAbstract` using this constructor(the branch node currently forces `$isATraversable` to `false`):

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

## Interruptions

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

## Code reusability

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

## Usage
The current version comes with three directly usable Payload Nodes, which are also used to build tests :

* CallableNode

    For convenience, `CallableNode` implements both `ExecNodeInterface` and `TraversableNodeInterface`. It's thus up to you to use a suitable Callable for each case.
    ```php
    use fab2s\NodalFlow\Nodes\CallableNode;

    $callableExecNode = new CallableNode(function($param) {
        return $param + 1;
    }, true);

    // which allows us to call the closure using
    $result = $callableExecNode->exec($param);

    $callableTraversableNode = new CallableNode(function($param) {
        for($i = 1; $i < 1024; $i++) {
            yield $param + $i;
        }
    }, true, true);

    // which allows us to call the closure using
    foreach ($callableTraversableNode->getTraversable(null) as $result) {
        // do something
    }
    ```

* BranchNode

    ```php
    use fab2s\NodalFlow\Nodes\BranchNode;

    $rootFlow = new ClassImplementingFlwoInterface;

    $branchFlow = new ClassImplementingFlwoInterface;
    // feed the flow
    // ...

    $rootFlow->addNode(new BranchNode($flow, false));
    ```

* AggregateNode

    ```php
    use fab2s\NodalFlow\Nodes\AggregateNode;

    $firstTraversable = new ClassImplementingTraversableNodeInterface;
    // ...
    $nthTraversable = new ClassImplementingTraversableNodeInterface;

    // aggregate node may or may not return a value
    // but is always a Traversable Node
    $isAReturningVal = true;
    $aggregateNode = new AggregateNode($isAReturningVal);
    $aggregateNode->addTraversable($firstTraversable)
        //...
        ->addTraversable($nthTraversable);

    // attach to a Flow
    $flow->add($aggregateNode);
    ```


* ClosureNode

    ClosureNode is not bringing anything really other than providing with another example. It is very similar to CallableNode except it will only accept a strict Closure as payload.

NodalFlow also comes with a PayloadNodeFactory to ease Payload Node usage :
```php
use fab2s\NodalFlow\PayloadNodeFactory;

$node = new PayloadNodeFactory(function($param) {
    return $param + 1;
}, true);

$node = new PayloadNodeFactory('trim', true);

$node = new PayloadNodeFactory([$someObject, 'someMethod'], true);

$node = new PayloadNodeFactory('SomeClass::someTraversableMethod', true, true);


$branchFlow = new ClassImplementingFlwoInterface;
// feed the flow
// ...

$node = new PayloadNodeFactory($branchFlow, true);

// ..
```

And the Flow, NodalFlow:

```php
use fab2s\NodalFlow\NodalFlow;
use fab2s\NodalFlow\PayloadNodeFactory;
use fab2s\NodalFlow\Nodes\CallableNode;

$branchFlow = new ClassImplementingFlwoInterface;
// feed the branch flow
// adding Nodes
$branchFlow->add(new CallableNode(function ($param = null) use ($whatever) {
    return doSomething($param);
}, true));
// or internally using the PayloadNodeFactory
$branchFlow->addPayload(function ($param = null) use ($whatever) {
    return doSomething($param);
}, true);
// ...

// Then the root flow
$nodalFlow = new NodalFlow;
$result = $nodalFlow->addPayload(('SomeClass::someTraversableMethod', true, true))
    ->addPayload('intval', true)
    // or ->add(new CallableNode('intval', false))
    // or ->add(new PayloadNodeFactory('intval', false))
    ->addPayload(function($param) {
        return $param + 1;
    }, true)
    ->addPayload(function($param) {
        for($i = 1; $i < 1024; $i++) {
            yield $param + $i;
        }
    }, true, true)
    ->addPayload($branchFlow, false)
    // or ->add(new BranchNode($branchFlow, false))
    // or ->add(new PayloadNodeFactory($branchFlow, false))
    ->addPayload([$someObject, 'someMethod'], false)
    ->exec();
```

As you can see, it is possible to dynamically generate and organize tasks which may or may not be linked together by their argument and return values.

NodalFlow uses a `FlowStatusInterface` to expose its exec state. The FlowStatus object is maintained at all time by the flow and can be used to find out if:
* The flow is clean, that is if everything went well up to this point:
    ```php
    $isClean = $flow->getFlowStatus()->isClean();
    ```
* The flow is dirty, that is if the flow was broken by a node:
    ```php
    $isDirty = $flow->getFlowStatus()->isDirty();
    ```
* The flow is exception, that is if a node raised an exception during the execution:
    ```php
    $isDirty = $flow->getFlowStatus()->isException();
    ```

This can be useful to find out what is going on within callbacks.


## Callbacks

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

## Serialization

As the workflow became an object, it became serializable, but this is unless it carries Closures. Closure serialization is not natively supported by PHP, but there are ways around it like [Opis Closure](https://github.com/opis/closure)


## Requirements

NodalFlow is tested against php 5.6, 7.0, 7.1 and hhvm, but it may run bellow that (might up to 5.3).

## Contributing

Contributions are welcome, do not hesitate to open issues and submit pull requests.

## License

NodalFlow is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).