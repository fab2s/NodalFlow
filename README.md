# NodalFlow

[![Documentation Status](https://readthedocs.org/projects/nodalflow/badge/?version=latest)](http://nodalflow.readthedocs.io/en/latest/?badge=latest) [![CI](https://github.com/fab2s/NodalFlow/actions/workflows/ci.yml/badge.svg)](https://github.com/fab2s/NodalFlow/actions/workflows/ci.yml) [![QA](https://github.com/fab2s/NodalFlow/actions/workflows/qa.yml/badge.svg)](https://github.com/fab2s/NodalFlow/actions/workflows/qa.yml) [![Total Downloads](https://poser.pugx.org/fab2s/nodalflow/downloads)](https://packagist.org/packages/fab2s/nodalflow) [![Monthly Downloads](https://poser.pugx.org/fab2s/nodalflow/d/monthly)](https://packagist.org/packages/fab2s/nodalflow) [![Latest Stable Version](https://poser.pugx.org/fab2s/nodalflow/v/stable)](https://packagist.org/packages/fab2s/nodalflow) [![Code Climate](https://codeclimate.com/github/fab2s/NodalFlow/badges/gpa.svg)](https://codeclimate.com/github/fab2s/NodalFlow) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/fab2s/NodalFlow/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/fab2s/NodalFlow/?branch=master) [![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat)](http://makeapullrequest.com)  [![License](https://poser.pugx.org/fab2s/nodalflow/license)](https://packagist.org/packages/fab2s/nodalflow)

`NodalFlow` is a generic Workflow that can execute chained tasks. It is designed around simple interfaces that specifies a flow composed of executable Nodes and Flows. Nodes can be executed or traversed. They accept a single parameter as argument and can be set to pass or not their result as an argument for the next node.
Flows also accept one argument and may be set to pass their result to be used or not as an argument for their first Node.

```
+--------------------------+Flow Execution+----------------------------->

+-----------------+        +------------------+         +---------------+
|   scalar node   +--------> trarersable node +--------->   next node   +-------->...
+-----------------+        +------------------+         +---------------+
                                              |
                                              |         +---------------+
                                              +--------->   next node   +-------->...
                                              |         +---------------+
                                              |
                                              |         +---------------+
                                              +--------->   next node   +-------->...
                                              |         +---------------+
                                              |
                                              +--------->...

```

Nodes are linked together by the fact they return a value or not. When a node is returning a value (by declaration), it will be used as argument to the next node (but not necessarily used by it). When it doesn't, the current parameter (if any) will be used as argument by the next node, and so on until one node returns a result intended to be used as argument to the next node.

```
+--------+ Result 1 +--------+ Result 3
| Node 1 +----+-----> Node 3 +--------->...
+--------+    |     +--------+
              |
              |
         +----v---+
         | Node 2 |
         +--------+

```

In this flow, as node 2 (which may as well be a whole flow or branch) is not returning a value, it is executed "outside" of the main execution line.

In other words, `NodalFlow` implements a directed graph structure in the form of a tree composed of nodes that can be, but not always are, branches or leaves. 

`NodalFlow` also goes beyond that by allowing any Flow or Node to send whatever parameter to any part of any Flow alive within the same PHP process. The feature shares similarities with the `Generator`'s [`sendTo()`](/docs/usage.md#the-sendto-methods) method and makes it possible to turn Flows into _executable networks_ of Nodes (and Flows).

```
+-------------------------+-------+----------+
|               |-->      |       |          |
+-+Node1+->tNode|-->Node3+> bNode +-->NodeN+->
|FlowA       ^  |-->      |   |   |          |
+------------|----------------|--------------+
             |            |   v   |
             |            | Node1 |
             |            |   |   |
             |            |   v   |
             +---sendTo()-+ Node2 |
                          | +-+-+ |
                          | | | | |
                          | v v v |
                          | Node3 |
                          +---|--------------+
                          |   v   |          |
                          | bNode +-->Node1+->
                          |   |   |     |    |
                          +---|--------------+
                          |   |   |     |
                          +---v---+     |
                                        |
               +-------sendTo()---------+
               |
 +-------------|----------------+
 |             v                |
 +--Node1-->Node2-->NodeN--...+->
 |  FlowB                       |
 +------------------------------+
```

`NodalFlow` aims at organizing and simplifying data processing workflow's where arbitrary amount of data may come from various generators, pass through several data processors and / or end up in various places and formats. But it can as well be the foundation to organizing pretty much any sequence of tasks (`NodalFlow` could easily become Turing complete after all). It makes it possible to dynamically configure and execute complex scenario in an organized and repeatable manner (`NodalFlow` is [serializable](/docs/serialization.md)). And even more important, to write Nodes that will be reusable in any other workflow you may think of.

`NodalFlow` enforces minimalistic requirements upon nodes. This means that in most cases, you should extend `NodalFlow` to implement the required constraints and grammar for your use case.

[YaEtl](https://github.com/fab2s/YaEtl) is an example of a more specified workflow build upon [NodalFlow](https://github.com/fab2s/NodalFlow).

`NodalFlow` shares conceptual similarities with [Transducers](https://clojure.org/reference/transducers) (if you are interested, also have a look at [Transducers PHP](https://github.com/mtdowling/transducers.php)) as it allow basic interaction chaining, especially when dealing with `ExecNodes`, but the comparison diverges quickly.

## NodalFlow Documentation

[![Documentation Status](https://readthedocs.org/projects/nodalflow/badge/?version=latest)](http://nodalflow.readthedocs.io/en/latest/?badge=latest) Documentation can be found at [ReadTheDocs](http://nodalflow.readthedocs.io/en/latest/?badge=latest)

## Installation

`NodalFlow` can be installed using composer:

```
composer require "fab2s/nodalflow"
```
If you want to specifically install the php >=7.2.0 version, use:

```
composer require "fab2s/nodalflow" ^2
```

If you want to specifically install the php 5.6/7.1 version, use:

```
composer require "fab2s/nodalflow" ^1
```

Once done, you can start playing:

```php
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
    ->addPayload($anotherNodalFlow, false)
    // or ->add(new BranchNode($anotherNodalFlow, false))
    // or ->add(new PayloadNodeFactory($anotherNodalFlow, false))
    ->addPayload([$someObject, 'someMethod'], false)
    ->exec($wateverParam);
```

## Requirements

`NodalFlow` is tested against php 7.2, 7.3 and 7.4 8.0 and 8.1

## Contributing

Contributions are welcome, do not hesitate to open issues and submit pull requests.

## License

`NodalFlow` is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
