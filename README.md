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

# Installation
#### Composer
Install rosengate/exedra through your console, in your project folder.
~~~
composer require rosengate/exedra dev-master
~~~

# Documentation
Documentation and the homebase for exedra is currently hosted here : http://exedra.rosengate.com
# Minimal Boot
Creating an Exedra application is just as simple as instantiating the application, with almost no initial configuration at all.
#### Bootstrap
Create a bootstrap *app.php* file as a starting entry of your application, which can be used for the public facing front controller, or the console.

At the most minimal level, below would just work. The only argument it takes is the root dir of your application.
```php
<?php
require_once __DIR__.'/vendor/autoload.php';

$app = new \Exedra\Application(__DIR__);

return $app;
```
#### Hello World
Add a little bit of routing to see some result.
```php
$app->map['hello']->get('/')->execute(function()
{
    return 'hello world';
});
```


#### Dispatch
Then create a */public/index.php* as the front controller in order to test your app.
```php
<?php
$app = require_once __DIR__.'/../app.php';

$app->dispatch();
```
And simply test it with the built-in php server.
```
cd public
php -S localhost:8080
```
Then, run the http://localhost:8080 on your browser. Refer to the Routing Sample below if you need more result.

# Framework provider
Exedra provides an easy set up for a minimal framework, and gets you quick registry for components like view, session, flash, form and console.

Let's get a bit more of the framework through some quick setup.
#### Bootstrap
```php
<?php
require_once __DIR__.'/vendor/autoload.php';

$app = new \Exedra\Application(__DIR__);

$app->provider->add(\Exedra\Support\Provider\Framework::class);

return $app;
```
This provider determines a simple structure for your app, and autoload the [src] path with the default namespace prefix *App\\*.
```
| .        //path.root
| app      //path.app
| ─ src    //path.src
| ─ routes //path.routes
| ─ views  //path.views
| public   //path.public
```
You may retrieve this paths, from the root path of the application.
```
$root = $app->path;
$app = $app->path['app'];
$public = $app->path['public'];
$src = $app->path['src'];
$view = $app->path['views'];
$routes = $app->path['routes'];
```

#### Public index
This file act as a public facing front controller of your application, which is usually located under /public/ folder, or **path.public** per configured above.
```php
<?php 
$app = require_once __DIR__.'/../app.php';

$app->dispatch();
```

#### /console
Create a file named **console**, in your project root directory, or anywhere convenient to you. And require the **app.php** again.
```php
<?php
$app = require_once __DIR__.'/app.php';

$app->console($argv);
```
##### Run the console wizard on your cli
```
php console
```
##### Start Basic PHP Server
```
php console serve -p 9000
```
and it'll serve based on the configured **path.public**, with port 9000.

##### console help
```
php console /?
```
##### command specific help
```
php console routes /?
```

# Standalone Components
Or you might just want to use a little help there and here.
#### View
```php
// register views lookup path under /views/
$app->path->register('views', 'views');

// or
$app->path['views'] = 'views';

$app->provider->add(\Exedra\View\ViewProvider::class);
```
Then you can use the view factory even in the runtime context.
```php
$app->map['web']->any('/')->execute(function($context)
{
    return $context->view->create('index')->render();
});
```

#### Session
```php
$app->provider->add(\Exedra\Session\SessionProvider::class);
```

#### Console
```php
$app->provider->add(\Exedra\Wizard\WizardProvider::class);
```

Have a look at those providers, on how they work, you might want to implement your own for more structured design.

# More Routing Sample
It'll work for both the minimal boot and the framework setup above.

#### Chainable routing
```php
// global middleware
$app->map->middleware(function($context)
{
    return $context->next($context);
});

// or specify by class name
$app->map->middleware(\App\Middleware\All::CLASS);

$app->map['index']->get('/')
    ->execute(function(Context $context) {
    });

$app->map['contact-us']
    ->method(['GET', 'POST'])
    ->path('/contact-us')
    ->execute(function() {
    });

$app->map['api']->any('/api')->middleware(\App\Middleware\Api::CLASS)->group(function($api)
{
    // or inversely, you can register the middleware into the current route, through this group.
    $api->middleware(\App\Middleware\ApiAuth::CLASS);
    
    $api->any('/users')->group(function($users)
    {
        // create new user
        // POST /api/users
        $users->post('/')->execute(function($context)
        {
            
        });
        
        // GET /api/users/:id
        $users->get('/:id')->execute(function($context)
        {
            return $context->param('id');
        });
    });
    
    $api->any('/channels')->group(function($channels)
    {
        // create new channel
        // POST /api/channels
        $channels->post('/')->execute(function($context)
        {
            
        });
        
        // GET /api/channels
        $channels->get('/')->execute(function($context)
        {
        
        });
        
        $channels->any('/:id')->group(function($channel)
        {
            // GET /api/channels/:id
            $channel->get('/')->execute(function()
            {
                
            });
            
            // POST /api/channels/:id/join
            $channel->post('/join')->execute(function()
            {
            
            });
        });
    });
});

return $app;
```

#### Array of routing
```php
$app->map->addRoutes(array(
    'book' => array(
        'path' => '/books',
        'subroutes' => array(
            'list' => array(
                'method' => 'GET',
                'path' => '/',
                'execute' => 'controller=Book@List',
            'view' => array(
                'method' => 'GET',
                'path' => '/:id',
                'execute' => 'controller=Book@View'
                )
            )
        )
    )
));
```
Some of the projects built on top of exedra :

http://github.com/rosengate/exedra-web (hosted at exedra.rosengate.com)

Psr Middleware
======
Double-pass signature psr7 http messages middleware bridge
### Through middleware bridge
```php
use Exedra\Support\Psr7\BridgeMiddleware;

$app->map->middleware(new BridgeMiddleware(array(
    // list of psr7 middlewares
));
```
The previous middleware must however be aware that the signature call of the psr7 middlewares returns the ResponseInterface.
Although in exedra, you're free to return whether a string or the response object itself.

For example :
```php
$app->map->middleware(function(Context $ctx) {
    // this one will be ResponseInterface
    $response = $ctx->next($ctx);
    
    return $response;
});
$app->map->middleware(new BridgeMiddleware(array(
    // list of psr7 middlewares
));
```

more on :
https://github.com/php-fig/fig-standards/blob/master/proposed/http-middleware/middleware-meta.md

Independent Routing Component
======
So, you want to use the routing component alone, because it definitely can be done. The only thing it cares is, 
the psr7 ServerRequest interface. A sample usage using guzzle http ServerRequest.

```php
use Exedra\Routing\Group;
use \GuzzleHttp\Psr7\ServerRequest;

$routingFactory = new \Exedra\Routing\Factory;

$router = $routingFactory->createGroup();

$router->addExecuteHandler('execute', \Exedra\Routing\ExecuteHandlers\ClosureHandler::class);

// a simple routing, and some middleware
$router->middleware(function(ServerRequest $request, $next) {
    return $next($request);
});

$router['hello']->any('/hello')->group(function(Group $group) {
    $group['world']->any('/world')->execute(function() {
        return 'hello world';
    });
});

// dispatch the guzzle ServerRequest
$finding = $router->findByRequest($request = ServerRequest::fromGlobals());

if(!$finding->isSuccess())
    throw new \Exception('Oops, couldn\'t match any page.');

$callStack = $finding->getCallStack();

echo $callStack($request, $callStack->getNextCaller());
```

Routing Controller
======
Look out for an amazing annotation based routing-controller component

http://github.com/exedron/routeller


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
