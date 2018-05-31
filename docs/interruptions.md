# Interruptions

Interruption are implemented in a way that keeps them conceptually similar to the regular `continue` and `break` operation on a loop. In both case  `continue` and `break` becomes equal when the iteration is performed over one value or in our case over a fully scalar Flow. 
The main difference comes From the eventual presence of execution branches outside of the Interrupting Flow's ancestors.
NodalFlow comes with a `FlowInterrupt` class, implementing `FlowInterruptInterface`, which create a way to accurately control the Interrupt signal propagation among Flow ancestors.
When a Node issues an Interrupt signal, it is caught by is direct carrier Flow. When triggering the Interrupt, you can provide with a `FlowInterrupt` instance that can be set to propagate the interrupt signal up to a specific ancestor Flow. You can for example target the root Flow directly, or any of the ancestor.

Let's consider the following example Flow, composed of three Flows:

```
+-------------------------+-------+-----------------+
|               |-->      |       |                 |
+--Node1-->tNode|-->Node3-> bNode +-->iNode--....+-->
|RootFlow       |-->      |   |   |                 |
+---------------------------------------------------+
                          |   v   |
                          | Node1 |
                          |   |   |
                          |   v   |
                          | tNode |
                          | ----- |
                          | | | | |
                          | v v v |
                          | iNode |
                          +---+-----------------------------------+
                          |   v   |                               |
                          | bNode +-->Node1-->iNode-->NodeN--...-->
                          |   |   |                    branchFlowB|
                          +---------------------------------------+
                          |   |   |
                          |   |   |
                          |   |   |
                          |   |   |
                          |   |   |
                          |   |   | 
                          +---v---+
                          branchFlowA
iNode : InterruptNode
bNode : BranchNode
tNode : TraversableNode

```

**In this example, RootFlow's iNode can:**

- Trigger a `continue`: The current RootFlow's parameter is skipped for all of iNode's successor nodes, meaning that both branchFlowA and branchFlowB will get the full set, including the record having been skipped, since it occurred after.
- Trigger a `break`: The signal bubbles up to the first upstream `Traversable` Node, tNode, and `break` its loop, resulting in halting the RootFlow. Like with the `continue` case, both branchFlowA and branchFlowB would still process the $record triggering the break, unlike iNode's successors, as this occurs before the break signal. It is though possible to implement some rollback mechanism based on interrupt signal detection if required by the usage.

**branchFlowA's iNode can:**

- Trigger a _default_ `continue`: The current branchFlowA's parameter is skipped for all of iNode's successor nodes, including branchFlowB
- Trigger a _targeted_ `continue`:
    - Target branchFlowA (by id or targeting self flow) : same as _default_ `continue`
    There is no real point in also targeting a Node in branchFlowA as it only carries one Traversable. If there where more, targeting any of them would `break` every Traversable in between the Node triggering and the target, and then `continue` on the Traversable target itself.
    - Target RootFlow (by id or targeting root flow) : The signal bubbles up to RootFlow's bNode, being branchFlowA's carrier Node, resulting in skipping current RootFlow's parameter for all of bNode's successor nodes, and in skipping current branchFlowA's parameter for all of iNode's successor nodes and in breaking branchFlowA's Node2 since it's current parameter is to be skipped.
- Trigger a _default_ `break`: The signal bubbles up to the first upstream `Traversable` Node, tNode, and `break` its loop, resulting in halting the branchFlowB for this RootFlow's parameter.
- Trigger a _targeted_ `break`:
    - Target branchFlowA (by id or targeting self flow) : same as _default_ `break`
    There is no real point in also targeting a Node in branchFlowA as it only carries one Traversable. If there where more, targeting any of them would `break` every Traversable in between the Node triggering and the target, and then `break` on the Traversable target itself.
    - Target RootFlow (by id or targeting root flow) : The signal bubbles up to RootFlow's bNode, being branchFlowA's carrier Node, resulting in halting RootFlow as there are no upstream Traversable left to generate more parameters. If there where more Traversable above bNode in RootFlow, we could target any of them.

**branchFlowB's iNode can:**

- Trigger a _default_ `continue`: The current branchFlowB's parameter is skipped for all of iNode's successor nodes
- Trigger a _targeted_ `continue`:
    - Target branchFlowB (by id or targeting self flow) : same as _default_ `continue`
    - Targeting branchFlowA (by id): The signal bubbles up to branchFlowA's bNode, being branchFlowB's carrier Node, resulting in skipping current branchFlowA's parameter for all of bNode's successor nodes, and in skipping current branchFlowA's parameter for all of iNode's successor nodes. Targeting any Node in branchFlowA would result in the same effect as there is only one Traversable above bNode.
    - Targeting RootFlow (by id or targeting root flow): The signal bubbles up to branchFlowA's bNode and to RootFlow's bNode, resulting in skipping current RootFlow's parameter for all of both bNode's successor nodes and skipping current branchFlowB's parameter for all of iNode's successor nodes. Likewise, targeting any Node in branchFlowA would result in the same effect as there is only one Traversable above bNode.
- Trigger a _default_ `break`: The signal bubbles up to the the top of branchFlowB and halts it.
- Trigger a _targeted_ `break`:
    - Target branchFlowB (by id or targeting self flow) : same as _default_ `break`
    - Targeting branchFlowA (by id): The signal bubbles up to the first `Traversable` Node above bNode in branchFlowA, tNode, and `break` its loop, resulting in halting the branchFlowA for this RootFlow's parameter. Targeting any Node in RootFlow would result in the same effect as there is only one Traversable above bNode.
    - Targeting RootFlow (by id or targeting root flow): The signal bubbles up to RootFlow's first upstream (read above bNode) Traversable, tNode, and `break` its loop, resulting in halting the RootFlow and all it's children Flows at their respective point of execution. Likewise, targeting any Node in RootFlow would result in the same effect as there is only one Traversable above bNode.

## In practice

NodalFlow comes with an `InterruptNodeInterface` which you can implement by extending `InterruptNodeAbstract` leaving you with a single method to implement :

```php
    /**
     * @param mixed $param
     *
     * @return InterrupterInterface|null|bool `null` do do nothing, eg let the Flow proceed untouched
     *                                        `true` to trigger a continue on the carrier Flow (not ancestors)
     *                                        `false` to trigger a break on the carrier Flow (not ancestors)
     *                                        `InterrupterInterface` to trigger an interrupt to propagate up to a target (which may be one ancestor)
     */
    public function interrupt($param);
```

As you can see, the basics are simple, just return `null` to let the Flow proceed,  `true` to skip (`continue`) the current record (`$param`) at the current point of execution or `false` to `break` the first upstream `Traversable` in the carrying Flow.

By returning an `InterrupterInterface` instance, implemented as the `Interrupter` class, you can accurately target any Flow and / or Node among the Node carrier Flow's ancestors.

For example, returning `true` is equivalent to returning:

```php
new Interrupter(null, null, InterrupterInterface::TYPE_CONTINUE);
```

and returning false is equivalent to returning:

```php
new Interrupter(null, null, InterrupterInterface::TYPE_BREAK);
```

The first two parameters respectively stands for Flow and Node instances or ids. 

### Targeting a Flow

Within an `InterruptNodeInterface` Node, targeting the carrier Flow can be done by either using as first parameter of the constructor:

 - `null`
 - `$this->getCarrier()`
 - `$this->getCarrier()->getId()`
 - `InterrupterInterface::TARGET_SELF`

You can target any ancestor of the carrying Flow either by Instance or Id, and you can target the root Flow directly by using `InterrupterInterface::TARGET_TOP`.

In each of these cases, the signal will bubble up to the targeted Flow and will:
    - for `continue` signals: skip the current record for all Nodes that may be found after the `BranchNode` where the signal showed up
    - for `break` signals: continue to bubble up among upstream Nodes in the target Flow until a `Traversable` Node is found, in which case its loop is halted, or up to the first Node of the targeted Flow in which case the Flow is halted.

If you feed the `Interrupter` with something that does not match any Flow among the carrier's ancestors, a `NodalFlowException` will be thrown.

### Targeting a Node

You can additionally target a particular Node within the targeted Flow. Obviously, it will only do something if the targeted Flow is reached. You can target Node by feeding `Interrupter` with:

    - a Node Instance in the target Flow
    - a Node Id in the target Flow
    - `false|null` to target the branching point on the target Flow
    - `true` to target the first Node in the target Flow
    
There is _no_ magic aliases like `InterrupterInterface::TARGET_TOP` for Nodes.

When an Interrupt signal reaches its target Flow and there is a target Node, the signal will bubble up to the targeted Node. Internally, this is done by resolving recursions without altering the record and continuing any traversable on the way up to the target where the signal is finally processed.

If a target Node was set and it was not found on the way, a `NodalFlowException` will be thrown.
 
## Lowest level

Each nodes is filled with it's carrier Flow when it is attached to it. Any Node implementing `NodeInterface` can interrupt any Node in its carrier Flow and ancestors :

```php
// skip this very action
$this->getCarrier()->continueFlow();
$this->getCarrier()->interruptFlow(InterrupterInterface::TYPE_CONTINUE);
// propagate skip to root Flow
$this->getCarrier()->continueFlow(new Interrupter(InterrupterInterface::TARGET_TOP));
// propagate skip to Flow id $targetFlowId
$this->getCarrier()->continueFlow(new Interrupter($targetFlowId));
// propagate skip at $targetNode  in Flow $targetFlow by instance
$this->getCarrier()->continueFlow(new Interrupter($targetFlow, $targetNode));
// same, but using lower level interruptFlow and by id
$this->getCarrier()->interruptFlow(InterrupterInterface::TYPE_CONTINUE, new Interrupter($targetFlow->getId(), $targetNode->getId())));
```

or

```php
// stop the carrrier Flow right here
$this->getCarrier()->breakFlow();
$this->getCarrier()->interruptFlow(FlowInterruptInterface::TYPE_BREAK);
// ...
```

whenever you need to, when `getTraversable()` and / or `exec()` methods are triggered to `continue` or `break` the flow. 
