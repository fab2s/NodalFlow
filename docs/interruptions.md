# Interruptions

Interruption are implemented in a way that keeps them conceptually similar to the regular `continue` and `break` operation on a loop. In both case  `continue` and `break` becomes equal when the iteration is performed over one value or in our case over a fully scalar Flow. 
The main difference comes From the eventual presence of execution branches outside of the Interrupting Flow's ancestors.
NodalFlow comes with a `FlowInterrupt` class, implementing `FlowInterruptInterface`, which create a way to accurately control the Interrupt signal propagation among Flow ancestors.
When a Node issues an Interrupt signal, it is caught by is direct carrier Flow. When triggering the Interrupt, you can provide with a `FlowInterrupt` instance that can be set to propagate the interrupt signal up to a specific ancestor Flow. You can for example target the root Flow directly, or any of the ancestor.

Let's consider the following example Flow, composed of three Flows:

```bash
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

## Lowest level

Each nodes is filled with it's carrier Flow when it is attached to it. Extending the provided `NodeAbstract` you can use :

```php
// skip this very action
$this->carrier->continueFlow();
$this->carrier->interruptFlow(FlowInterruptInterface::TYPE_CONTINUE);
// propagate skip to root Flow
$this->carrier->continueFlow(new FlowInterrupt(FlowInterruptInterface::TARGET_TOP));
// propagate skip to Flow id `$targetFlowId`
$this->carrier->continueFlow(new FlowInterrupt($targetFlowId));
```

or

```php
// stop the whole flow right here
$this->carrier->breakFlow();
$this->carrier->interruptFlow(FlowInterruptInterface::TYPE_BREAK);
// ...
```

whenever you need to in the `getTraversable()` and / or `exec()` methods to `continue` or `break` the flow. 
