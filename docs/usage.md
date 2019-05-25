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

## Interruptions

Have a look at the [Interruption section](/docs/interruptions.md) of the documentation

## The `sendTo()` methods

The `sendTo()` method is a Flow method that can send a parameter to any of its Node. When `sendTo()` is called, the Flow will start a new recursion starting at the targeted Node position in the Flow. This means that everything will happen like if the Flow only contained the target Node and the ones after it. The return value will be the Flow return value of its targeted portion if any involved Node returns something.

The Flow method uses two optional arguments, a Node Id to target within the Flow (absence is identical to full execution) and an eventual argument to pass to the target:

```php
    /**
     * @param string|null $nodeId
     * @param mixed|null  $param
     *
     * @throws NodalFlowException
     *
     * @return mixed
     */
    public function sendTo($nodeId = null, $param = null);
```

In practice:

```php
$node1 = new Node1;
$node2 = new Node2;
$nodeN = new NodeN;

$flow = (new NodalFlow)
    ->add($node1)
    ->add($node2)
    ->add($nodeN);
    
// exec the whole Flow with $something as initial parameter
// and get the $result, being the return value of the last
// Node returning a value (by declaration), or exactely 
// $something in case none are
$result = $flow->exec($something);
// same as 
$result = $flow->sendTo(null, $something);

// execute the Flow as if it did not contain $node1
$partialResult = $flow->sendTo($node2->getId(), $something);
```

For convenience, a version of `sendTo()` is also present in `NodeInterface`  and implemented in `NodeAbstract` as a proxy to the Flow method. Its purpose is to ease Flow targeting outside of the Node's carrier Flow (since targeting the carrier is already trivial using Node's `getCarrier()`). The Node version of `sendTo()` thus uses one more argument to target the Flow:

```php
    /**
     * @param string      $flowId
     * @param string|null $nodeId
     * @param string|null $param
     *
     * @throws NodalFlowException
     *
     * @return mixed
     */
    public function sendTo($flowId, $nodeId = null, $param = null);
```

This means that from _any_ Node you can send _any_ parameter to _any_ Flow in the same process at _any_ Node position. This effectively can turn any set of Nodal(work)Flow residing into the same PHP process into an _Executable Network_ of Nodes and Flows.

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

NodalFlow uses a `FlowStatusInterface` to expose its exec state. A `FlowStatus` instance is maintained at all time by the flow and can be used to find out how things went. The status can reflect three states :

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

When an exception was thrown during the flow execution, it will be stored in the `FlowStatus` instance and can be easily retrieved :

```php
$exception = $flow->getFlowStatus()->getException();
```

This can be useful to find out what is going on within Flow events as they carry the Flow instance.

## Flow Map and Registry

Each Flow holds its `FlowMap`, in charge of handling increment and tracking Flow structure. Each `FlowMap` is bound by reference to a global `FlowRegistry` acting as a global state and enforcing the strong uniqueness requirement among Nodes and Flow instances. As the global state is kept withing a static member of every `FlowRegistry` instances (acting as an instance proxy to static data), you can at any time and anywhere instantiate a `FlowRegistry` instance and access the complete hash map of all Nodes and Flows, including usage statistics and actual instances. A more detailed presentation of `FlowMap` and `FlowRegistry` together with some of the design decisions explanation can be found in the [serialization documentation](/docs/serialization.md).

Anywhere at any time you can:

```php
$registry = new FlowRegistry;

// get any Flow instance by Id
$registry->getFlow($flowId);

// get any Node instance by Id
$registry->getNode($nodeId);

// get the underlying array struct for a given Flow Id
$registry->get($flowId);
```
