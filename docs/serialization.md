# Serialization

Flow Serialization comes with some interesting challenges, especially since Flows may contain Flows. The problematic is tight with a very fundamental aspect of NodalFlow's design: Node Instances can only be carried by a single Flow at a time. 
So there is no way around it, Node instances _must_ be unique among _every_ Flows in the process. For the good part, this brings immutable instances ids, but this also introduces some interesting challenges and exotic cases.

To enforce such a strong requirement among Flows that may have no other relation than to reside into the same php process require some sort of global state. In NodalFlow, this global state is embodied by a `static` variable of a `FlowRegistry` instance hold by each Flow's `FlowMap` instance.
Each `FlowMap` instance additionally holds references to the portion of the registry that belong to its carrying Flow, so that at any time, each Flow is bound to the relevant global state part through a `FlowMap` instance, abstracting the entries stored in the Global state to those relevant to the Flow.
This is where it start dealing with serialization, as static variables are not serialized. But, as each Flow holds a `FlowMap` instance carrying the relevant portion of the Global state by reference, the global state is actually serialized by relevant portions within each serialized `FlowMap`.

When un-serializing a Flow, the global state is restored bit by bit, as more member or independent Flows gets un-serialized or instantiated within the process. It's no real overhead since it's already required to make sure that uniqueness is not violated, so it's only a matter of registering each Nodes and Flows in the global state upon un-serialization and setting references again.

## About the why

Serializing a Flow is not useful in all cases. For example, if you where to trigger some data processing Flow within a worker, it would certainly be simpler to just pass parameters to some job function in charge of instantiating and executing the proper Flow with proper parameters.

On the other hand, it could be handy to dynamically generate and store Flows if you where to use a lot of them. You could even support multiple implementations and versions of your Flows all together this way.

## Exoticism and RTFM

This uniqueness requirement comes with a limitation: it is not possible to un-serialize a Flow if it was already un-serialized within the same process, even if you `unset` the Flow before that. This is a tricky case because php does not necessarily call it's garbage collector right away when you `unset` an object, it may occur later. 

Supporting this quite particular case would require to implement and manually call the root Flow's `__destruct()` method and have it call the underlying `FlowMap` instance `__destruct()` where references and consistency would also need to be maintained. While it's not impossible in principle, the details can become very interesting, especially since the global state also carries each Flows and Nodes instances. It's just a circle that felt a bit too much to square for the purpose.

This also implies that un-serializing a Flow may only occur once per process.
 
All together, it's not such a big limitation, and this case is treated like any other Flow and Node duplication: an exception is thrown. So it should be obvious enough not to become a real issue.

## Closures

Closure serialization is not natively supported by PHP, but there are ways around it like [Opis Closure](https://github.com/opis/closure)
