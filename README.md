Exédra
======
[![Build Status](https://travis-ci.org/Rosengate/exedra.svg?branch=master)](https://travis-ci.org/Rosengate/exedra)
[![MIT Licence](https://badges.frapsoft.com/os/mit/mit.png?v=103)](https://github.com/Rosengate/exedra/blob/master/LICENSE)

A nestful route oriented PHP Microframework.

Introduction
======
This PHP microframework focuses on nestable/groupable routing, mainly through Http Request components, with middlewarable routes and which hopefully will provide much modular application execution. Route is unique, and identifiable by name, tag, and queriable by Http Request. Imagine developing an application down the depth without losing the identity of the executions you planned, controlling the routes hierarchically with your own layers of middlewares.

The goal is to be contextual, explicitful while being simple and extremely minimal at the same time. It can be intended to work 
as a supporting framework to your existing application.

Imagine building a plane while flying it!

# Features
- Nestable routing
- Minimal, contextual, flexible, framework agnostic
- Routing component built for Psr7 Http Messages
- Psr7 middleware support
- Container based
- Decoupled components
- Explicit dependency injection (no auto wiring)

Documentation
======
More detailed documentation can be found at http://exedra.rosengate.com

# Example
Create an index.php file with the following contents.
```php
<?php
use Exedra\Routing\Group;
use Exedra\Runtime\Context;
use Exedra\Application;

require_once __DIR__ . '/vendor/autoload.php';

$app = new Application(__DIR__);

$app->map['web']->any('/hello')->group(function(Group $group) {
    $group['welcome']->get('/:name')->execute(function(Context $context) {
        return 'Hello ' . $context->param('name');
    });
});

$app->dispatch();
```
And run a simple web server on the same dir.
```
php -S localhost:9000
```
Then open up your browser and type `http://localhost:9000/hello/world` to get your `Hello world`.

Thank you!
======
I hope you guys like it!

License
======
[MIT License](LICENSE)