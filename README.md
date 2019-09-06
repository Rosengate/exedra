Exédra
======
[![Build Status](https://travis-ci.org/Rosengate/exedra.svg?branch=master)](https://travis-ci.org/Rosengate/exedra)
[![MIT Licence](https://badges.frapsoft.com/os/mit/mit.png?v=103)](https://github.com/Rosengate/exedra/blob/master/LICENSE)

A nestful route oriented PHP Microframework.

Introduction
======
This PHP microframework focuses on nestable/groupable URI path/segments based routing, that allows you to prototype your application through URI routing without losing control over it's depth. Route is unique and identifiable by name, tag and queriable through request dispatch, or finder within URL factory. Along with nested routing, is middlewarable routing group to give you more control over your application design.

The goal is to be contextual, explicitful while being simple and extremely minimal at the same time. It can be intended to work 
as a supporting framework to your existing application.

Imagine building a plane while flying it!

# Features
- Nestable routing
- Minimal, contextual, flexible, components agnostic
- Annotated based route-action controller (optional)
- Routing component built for Psr7 Http Messages
- Psr7 middleware support
- Container based
- Explicit dependency injection (not auto wiring)

Documentation
======
More detailed documentation and installation can be found at http://exedra.rosengate.com/docs

# Installation
```
composer require rosengate/exedra
```

# Example
Just an example to quickly test exedra.

Create an index.php file with the following contents.
```php
<?php
use Exedra\Routing\Group;
use Exedra\Runtime\Context;
use Exedra\Application;

require_once __DIR__ . '/vendor/autoload.php';

$app = new Application(__DIR__);

$app->map['web']->any('/hello')->group(function (Group $group) {
    $group->middleware(function (Context $context) {
        return strtoupper($context->next($context));
    });

    $group['welcome']->get('/:name')->execute(function (Context $context) {
        return 'Hello ' . $context->param('name');
    });
});

$app->dispatch();
```
And run a simple web server on the same dir.
```
php -S localhost:9000
```
Then open up your browser and type `http://localhost:9000/hello/world` to get your `HELLO WORLD`.

Heads up to the documentation http://exedra.rosengate.com/docs for more detailed setup.

Thank you!
======
I hope you guys like it!

License
======
[MIT License](LICENSE)
