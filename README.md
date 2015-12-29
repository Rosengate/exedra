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
### Clone
~~~
git clone https://github.com/rosengate/exedra project_name
~~~

### Composer
===
Install rosengate/exedra through your console, in your project folder.
~~~
composer require rosengate/exedra dev-master
~~~

Or install/update manually
~~~
{
    "require": {
        "rosengate/exedra": "dev-master"
    }
}
~~~
and do 
~~~
composer update
~~~

Documentation
===
Documentation and the homebase for exedra is currently hosted here : http://exedra.rosengate.com.

Basic Usage
======
Let's try below out! Just some nested routing, and stacked middleware going on!
~~~
require_once 'vendor/autoload.php'; // or just require exedra wherever u can find it
$exedra = new \Exedra\Exedra(__DIR__);
$app = $exedra->build('App');
$app->mapFactory->useConvenientRouting();

// global middleware
$app->middleware->add(function($exe)
{
    $exe->texts = array('You');

    return $exe->next($exe);
});

$app->map->any('/')->middleware(function($exe) // route based middleware
{
    $exe->texts[] = 'are';

    return $exe->next($exe);
    
})->group(function($group)
{
    $group->any('/members')->group(function($group)
    {
        $group->get('/')->execute('controller=Member@index');
        
        $group->get('/[:id]')->execute('controller=Member@view');
    });
    
    $group->get('/')->middleware(function($exe)
    {
        $exe->texts[] = 'definitely';

        return $exe->next($exe);

    })->group(function($group)
    {
        $group->get('/')->middleware(function($exe)
        {
            $exe->texts[] = 'here!';

            return $exe->next($exe);

        })->group(function($group)
        {
            $group->get('/')->execute(function($exe)
            {
                return implode(' ', $exe->texts);
            });
        });
    });
});

$exedra->httpRequest->resolveUriPath(); // resolve uri path when you're under some deep subfolder.
$exedra->dispatch();
~~~

#### Start Basic PHP Server
~~~
php -S localhost:9000
~~~

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
                'method' => 'get',
                'execute' => ''controller=Book@View'
                )
            )
        )
    )
));
~~~
##### Convenient routing
~~~
// specify the usage
$app->mapFactory->useConvenientRouting();

$app->map->any('/books')->group(function($group)
{
    $group->get('/')->execute('controller=Book@index');
    
    $group->get('tags', function($exe)
    {
        return 'list of tags';
    });
    
    $group->any('[:id]')->group(function($group)
    {
        $group->get('/')->execute('controller=Book@view');
        
        $group->get('/authors', 'controller=Book/Author@index');
    });
});
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
