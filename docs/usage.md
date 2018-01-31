# Usage

The current version comes with directly usable Payload Nodes, which are also used to build tests.

## CallableNode

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

## BranchNode

```php
use fab2s\NodalFlow\Nodes\BranchNode;

$rootFlow = new ClassImplementingFlwoInterface;

$branchFlow = new ClassImplementingFlwoInterface;
// feed the flow
// ...

$rootFlow->addNode(new BranchNode($flow, false));
```

## AggregateNode

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

## ClosureNode

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

## The Flow

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
    ->exec($wateverParam);
```

As you can see, it is possible to dynamically generate and organize tasks which may or may not be linked together by their argument and return values.

## Flow Status

NodalFlow uses a `FlowStatusInterface` to expose its exec state. The FlowStatus object is maintained at all time by the flow and can be used to find out how things went. The status can reflect three states :

### Clean

That is if everything went well up to this point:

```php
$isClean = $flow->getFlowStatus()->isClean();
```

### Dirty

That is if the flow was broken by a node:

```php
$isDirty = $flow->getFlowStatus()->isDirty();
```

### Exception

That is if a node raised an exception during the execution:

```php
$isDirty = $flow->getFlowStatus()->isException();
```

This can be useful to find out what is going on within callbacks.

## Flow Map and Registry

Each Flow holds its `FlowMap`, in charge of handling increment and tracking Flow structure. Each `FlowMap` is bound by reference to a global `FlowRegistry` acting as a global state and enforcing the strong uniqueness requirement among Nodes and Flow instances. As the global state is kept withing a static member of every `FlowRegistry` instances, you can at any time and anywhere instantiate `FlowRegistry` to access the complete hash map of all Nodes and Flows, including usage statistics and instances. A more detailed presentation of `FlowMap` and `FlowRegistry` together with some of the design decisions explanation can be found in the [serialization documentation](https://github.com/fab2s/NodalFlow/blob/master/docs/serialization.md).
