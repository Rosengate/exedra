Exédra
======
A multi-tier nestful routing oriented PHP Framework.

p/s : Development is still on-going. Expect some b.c. breaks.

Introduction
======
This PHP framework focuses mainly on nestable route mapping, route oriented based execution and which hopefully will provide much modular application execution. Route is unique, and identified by name. Imagine developing an application hierarchically down the depth without losing the identity of the executions you planned, while stacking middlewares down the path on every possible node of route.

History
======
The first unreleased version of exedra has so much constraining ungroupable routing capability, and has a lot of static usages (which is extremely violating). This version was intended to fix them, give more controllable multi-tier nestable routing ability and hopefully would adapt to modern web development practices, while staying simple, flexible and contextual at the same time.

Installation
======
### Composer
===
Install rosengate/exedra through your console, in your project folder.
~~~
composer require rosengate/exedra dev-master
~~~

Documentation
===
Documentation and the homebase for exedra is currently hosted here : http://exedra.rosengate.com (not updated)

Booting up \Exedra\Application
======
At the end of this file, this is how your sample project directory may look like :
~~~
App
  app.php
public
  index.php
vendor
wizard
composer.json
composer.lock
~~~
#### /App/app.php
You can write up the boot file (**app.php**) anywhere. But there're several important directory config paths required to be configured.

First, create the boot file named **app.php** under App.

And load the composer autoload accordingly. The **app.php** file should return the same \Exedra\Application instance, so it's usable for the front controller index.php, or wizard (console) later.
~~~
require_once __DIR__.'/../vendor/autoload.php';

$app = new \Exedra\Application(array(
    'namespace' => 'App',
    'dir.app' => __DIR__',
    'dir.root' => __DIR__.'/../',
    'dir.public' => __DIR__.'/../public/'
    ));

return $app;
~~~
Or you can code it this way :
~~~
require_once __DIR__.'/../vendor/autoload.php';

$app = new \Exedra\Application(__DIR__);

return $app;
~~~
By default it'll take the argument as the **dir.app** path, and configure the **namespace**, based on the folder name it's located in, the **dir.root** will be higher one level, and **dir.public** is configured under the **dir.root**.

Now, in the same **app.php** let's write some nestful chatting api codes :
~~~
// global middleware
$app->map->middleware(function($exe)
{
    return $exe->next($exe);
});

// or specify by class name
$app->map->middleware(\App\Middleware\All::CLASS);

$app->map->any('/api')->middleware(\App\Middleware\Api::CLASS)->group(function($api)
{
    // or inversely, you can register the middleware into the current route, through this level.
    $api->middleware(\App\Middleware\ApiAuth::CLASS);
    
    $api->any('/users')->group(function(users)
    {
        // create new user
        // POST /api/users
        $users->post('/')->execute(function($exe)
        {
            
        });
        
        // GET /api/users/[:id]
        $users->get('/[:id]')->execute(function($exe)
        {
            return $exe->param('id');
        });
    });
    
    $api->any('/channels')->group(function($channels)
    {
        // create new channel
        // POST /api/channels
        $channels->post('/')->execute(function($exe)
        {
            
        });
        
        // GET /api/channels
        $channels->get('/')->execute(function($exe)
        {
        
        });
        
        $channels->get('/[:id]')->group(function($channel)
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
~~~

#### /public/index.php
Create your front controller file (**index.php**) under your public folder (**dir.public**). And require the **app.php** file;
~~~
$app = require_once __DIR__.'/../App/app.php';

$app->request->resolveUriPath();

$app->dispatch();
~~~

#### /wizard
Create a file named **wizard**, in your project root directory, or anywhere convenient to you. And require the **app.php** again.
~~~
$app = require_once __DIR__.'/App/app.php';

$app->wizard($argv);
~~~

#### Start Basic PHP Server
execute the wizard with php :
~~~
php wizard
~~~
and choose the serve option. it'll serve based on the **dir.public** path configured.

Another Examples
======
##### Default routing
~~~
$app->map->addRoutes(array(
    'book' => array(
        'uri' => '/books',
        'subroutes' => array(
            'list' => array(
                'uri' => '',
                'method' => 'GET',
                'execute' => 'controller=Book@List',
            'view' => array(
                'uri' => '[:id]',
                'method' => 'GET',
                'execute' => ''controller=Book@View'
                )
            )
        )
    )
));
~~~
Some of the projects built on top of exedra :

http://github.com/rosengate/exedra-web (hosted at exedra.rosengate.com)

Roadmap to 0.3.0
======
- Adapt several PSR's styles and standards
  - PSR-7 Message Interfaces
  - PSR-2 4 spaces indent ?
- More clarity on HTTP Response
- rename number of wrong terms used
  - 'uri' to 'path' [DONE]
- Internal Routing Improvements
- More type of Exceptions
- More clarity on application structure
- Container based \Exedra\Application\Application and \Exedra\Application\Execution\Exec ?
- Move \Exedra\Application\Execution\ and all the related namespaces outsides ?
- Do more tests

Thank you!
======
I hope you guys like it!
