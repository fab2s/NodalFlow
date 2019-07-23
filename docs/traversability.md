# Traversability

A Traversable Node is a node that implement the `getTraversable` method as defined in `TraversableNodeInterface`. The `getTraversable` method returns a `Traversable` that will be iterated over during the flow's execution. In other words, a Traversable Node is a node that provides many values when invoked, with each values being fed as argument to the remaining nodes in the chain. This would be exactly what occurs if the `Traversable` where to be an array, but you can also use a `Generator` and `yield` results one by one, or whatever `Traversable`.

NodalFlow as a whole can thus be seen as a kind of dismantled "meta" loop upon each of its `Traversable` nodes with linear nodes in between, aka the Exec Nodes. Traversable Nodes can be aggregated, which results in all of them being looped upon as if they where a single data generator, or chained, which result in each of them being recursively iterated over (1st traversable 1st record -> 2nd traversable 1st records -> last traversable every records ...).

Upon each iteration, the remaining Nodes in the flow will be recurred over. This is for example useful when a data generator needs some kind of manipulation(s) and / or actions on each of his "records".
