# NodalFlow

[![Documentation Status](https://readthedocs.org/projects/nodalflow/badge/?version=latest)](http://nodalflow.readthedocs.io/en/latest/?badge=latest) [![Build Status](https://travis-ci.org/fab2s/NodalFlow.svg?branch=master)](https://travis-ci.org/fab2s/NodalFlow) [![HHVM](https://img.shields.io/hhvm/fab2s/YaEtl.svg)](http://hhvm.h4cc.de/package/fab2s/nodalflow) [![Code Climate](https://codeclimate.com/github/fab2s/NodalFlow/badges/gpa.svg)](https://codeclimate.com/github/fab2s/NodalFlow) [![Codacy Badge](https://api.codacy.com/project/badge/Grade/0a68622246734a16983616188eeefa01)](https://www.codacy.com/app/fab2s/NodalFlow) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/fab2s/NodalFlow/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/fab2s/NodalFlow/?branch=master) [![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg?style=flat)](http://makeapullrequest.com) [![License](https://poser.pugx.org/fab2s/nodalflow/license)](https://packagist.org/packages/fab2s/nodalflow)

NodalFlow is a generic Workflow that can execute chained tasks. It is designed around simple interfaces that specifies a flow composed of executable nodes and flows. Nodes can be executed or traversed. They accept a single parameter as argument and can be set to pass or not their result as an argument for the next node.
Flows also accept one argument and may be set to pass their result to be used or not as an argument for the next node.
If a node does not pass it's result as parameter to the next node, the current parameter will be used for the next node, and so on until one node returns a result intended to be used as argument to the next node.
In other words, NodalFlow implements a directed graph structure in the form of a tree composed of nodes that can, but not always are, branches or leaves.

NodalFlow aims at organizing and simplifying data processing workflows where arbitrary amount of data may come from various generators, pass through several data processors and / or end up in various places and formats. It makes it possible to dynamically configure and execute complex scenario in an organized and repeatable manner. And even more important, to write Nodes that will be reusable in any other workflow you may think of.

NodalFlow enforces minimalistic requirements upon nodes. This means that in most cases, you should extend `NodalFlow` to implement the required constraints and grammar for your use case.

[YaEtl](https://github.com/fab2s/YaEtl) is an example of a more specified workflow build upon [NodalFlow](https://github.com/fab2s/NodalFlow).

NodalFlow shares conceptual similarities with [Transduction](https://en.wikipedia.org/wiki/Transduction) as it allow basic interaction chaining, especially when dealing with `ExecNodes`, but the comparison diverges quickly.

## NodalFlow Documentation
[![Documentation Status](https://readthedocs.org/projects/nodalflow/badge/?version=latest)](http://nodalflow.readthedocs.io/en/latest/?badge=latest) Documentation can be found at [ReadTheDocs](http://nodalflow.readthedocs.io/en/latest/?badge=latest)

## Installation

NodalFlow can be installed using composer :

```shell
composer require "fab2s/NodalFlow"
```

Once done, you can start playing :

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

## Serialization

As the workflow became an object, it became serializable, but this is unless it carries Closures. Closure serialization is not natively supported by PHP, but there are ways around it like [Opis Closure](https://github.com/opis/closure)


## Requirements

NodalFlow is tested against php 5.6, 7.0, 7.1 and hhvm, but it may run bellow that (might up to 5.3).

## Contributing

Contributions are welcome, do not hesitate to open issues and submit pull requests.

## License

NodalFlow is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).