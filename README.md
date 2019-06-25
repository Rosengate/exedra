Exédra
======
[![Build Status](https://travis-ci.org/Rosengate/exedra.svg?branch=master)](https://travis-ci.org/Rosengate/exedra)
[![MIT Licence](https://badges.frapsoft.com/os/mit/mit.png?v=103)](https://github.com/Rosengate/exedra/blob/master/LICENSE)

A multi-tier nestful routing oriented PHP Microframework.

p/s : About to be released on stable version

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

- [Home](https://github.com/Rosengate/exedra/wiki)
- [Installation](https://github.com/Rosengate/exedra/wiki/ii.-Installation)
- [Minimal Setup](https://github.com/Rosengate/exedra/wiki/iii.-Minimal-Setup)
- [Framework Provider](https://github.com/Rosengate/exedra/wiki/iv.-Framework-Provider)
- [Routing Examples](https://github.com/Rosengate/exedra/wiki/v.-Routing-Examples)
- [API](https://github.com/Rosengate/exedra/wiki/vi.-API)
- [Independent Routing Components](https://github.com/Rosengate/exedra/wiki/vii.-Independent-Routing-Component---Examples)
- [Routing Controller](https://github.com/Rosengate/exedra/tree/master/Exedra/Routeller)
- [Psr7 Middleware](https://github.com/Rosengate/exedra/wiki/viv.-Psr-Middleware)

# Usage
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

# Roadmap to 1.0.0
- Full Test Coverages
- Class and components naming
- Removal of unnecessary codes
- More clarity to handlers (group handler, execute handler)

Roadmap to 0.3.0 [99% Done]
======
- Adapt several PSR's styles and standards
  - PSR-7 Message Interfaces [DONE]
  - PSR-2 ?
- More clarity on HTTP Response [DONE]
- proper jargon renames
  - 'uri' to 'path' [DONE]
  - builder to factory [DONE]
- Internal Routing Improvements [NEARLY?]
- More type of Exceptions [DONE]
- More clarity on application structure [Structure Class removed]
- Container based \Exedra\Application\Application and \Exedra\Application\Execution\Exec ? [DONE]
- Move \Exedra\Application\Execution\ and all the related namespaces outsides ? [DONE]
- Do more tests [NEEDMORE?]

Thank you!
======
I hope you guys like it!
