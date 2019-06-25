# \Exedron\Routeller
A Minimal annotation and reflection based anemic routeful controller for Exedra.

In a simple word, a routeable-action-controller component in steroid.

## Introduction

Writing a lot of `\Closure` for your deep nested routing can get messier and not so IDE-friendly as they grow much bigger. 
This package is built to tackle the issue and give you a nice routing controller over your routing groups.

The controller is anemic, flattened and incapable of construction, but knows very well about the routing design.

The annotation design is fairly simple, just a `@property-value` mapping. Nothing much!

## Requirement
This package works only for Exedra. It cannot be used independantly, because.. Of course, 
it's part of Exedra routing component.

## Installation and usage
### Installation
Install through composer
```
composer require exedron\routeller dev-master
```
#### Add via service provider
Setup the service provider
```php
// your application instance
$app->provider->add(\Exedron\Routeller\Provider::class);
```

#### Add via routing factory
Alternatively, you may manually add the handler, and set up additional things.
```php
$app->routingFactory->addGroupHandler(new \Exedron\Routeller\Handler($app));

$app->map->addExecuteHandler('routeller_execute', ExecuteHandler::class);
```

#### Enable caching
Enable a file based cache
```php
$options = array(
    'auto_reload' => true
);

$cache = new \Exedron\Routeller\Cache\FileCache(__DIR__ .'/routing_caches')

$app->provider->add(new \Exedron\Routeller\Provider($cache, $options));
```
The ```auto_reload``` option lets it reload the cache each time there's some change to the controllers.

### Usage
On your preferred route, add use the controller using the *group()* method.
```php
$app->map['web']->group(\App\Controller\WebController::class);
```
The controller **MUST** by type of *\Exedron\Routeller\Controller\Controller*.
```php
<?php
namespace App\Controller;

use Exedron\Routeller\Controller\Controller;

class WebController extends Controller {

}
```
### Adding executable routes
Annotate the method with the route properties. The method name has to be prefixed with *execute*.
```php
<?php
namespace App\Controller;

use Exedra\Runtime\Context;

class WebController extends \Exedron\Routeller\Controller\Controller
{
    /**
     * @path /
     */
    public function executeIndex(Context $context)
    {
        return 'This is index page';
    }
    
    /**
     * @name about-us
     * @path /about-us
     * @method GET|POST
     */
    public function executeAboutUs(Context $context)
    {
        return 'This is about page';
    }
}
```
Doing above is similar to doing :
```php
use Exedra\Routing\Group;
use Exedra\Runtime\Context;

$app->map['web']->group(function(Group $group) {
    $group['index']->any('/')->execute(function(Context $context) {
        return 'This is index page';
    });
    
    $group['about-us']->any('/about-us')->execute(function(Context $context) {
        return 'This is about page';
    });
});
```

### Adding plain route
You may want to customize the route more object orientedly, prefix with `route`.
```php
/**
 * @path /faq
 */
public function routeFaq(\Exedra\Routing\Route $route)
{
    $route->execute(function() {
    
    });
}
```

### Middleware
Add a middleware for the current group's route, by prefixing the method name with `middleware`.
 This method expects no annotation.
```php
public function middlewareAuth(Context $context)
{
    if(!$context->session->has('user_id'))
        return $context->redirect->route('@web.login');

    return $context->next($context);
}
```
The middleware name is optional though, so, you can still set it.
```php
/**
 * @name csrf
 */
public function middlewareCsrf()
{
    return $context->next($context);
}
```

### Subrouting / Nest-Routing
Add a subgroup route. The method name must be prefixed with `group`.

The method must return the routing group pattern.
```php
/**
 * @path /products
 */
public function groupProducts()
{
    return \App\Controller\ProductController::class;
}
```

### Immediate subrouting
Similar to `group` prefix, except that this one have their group resolved immediately.
```php
/**
 * @path /:product-id
 */
public function subProduct(Group $group)
{
    $group['get']->get('/')->execute(function() {
        // do your things
    });
    
    $group['update']->post('/')->execute(function() {
        // do your things
    });
}
```


### Normal routing
You can also do a usual routing by prefixing the method name with `setup`. This method expects no annotation.
```php
public function setup(Group $group)
{
    $group->get('/')->execute(function() {
    });
}

public function setupCustom(Group $group)
{
    // do another thing
}
```
This method also receives `Exedra\Application` as the second argument.

```php
/**
 * @path /comments
 */
public function setup(Group $group, \Exedra\Application $app)
{
    $group->get('/')->execute(function() {
    });
}
```


## Restful verbs
This package also support a simple restful mapping.

#### GET, POST, PUT, PATCH, DELETE verb
Prefix each method with the http verb as you like.
```php
/**
 * Get all products
 * @path /
 */
public function getProducts(Context $context)
{
}
```
```php
/**
 * Create a new product
 * @path /
 */
pubic function postProducts(Context $context)
{
}
```
```php
/**
 * GET the product
 * @path /[:id]
 */
pubic function getProduct(Context $context)
{
}
```
```php
/**
 * DELETE the product
 * @path /[:id]
 */
pubic function getProduct(Context $context)
{
}
```

#### Verb only method name
And you can have a route with only the verb.
```php
public function get(Context $context)
{
}

public function post(Context $context)
{
}
```
Of course, this is just a sample. Best way to do a resourceful design in Exedra, is through a subrouting.

#### Dependency injection
Inject with known service(s)
```php
use Exedra\Url\UrlGenerator;

/**
 * @inject context.url
 * @path /
 */
public function get(UrlGenerator $url)
{
    echo $url->current();
}
```
Multiple services
```php
use Exedra\Runtime\Context;
use Exedra\Application;
use Exedra\Routing\Group;

/**
 * @inject context, url, self.response, app, app.map
 * @path /
 */
 public function post(Context $context, $url, $response, Application $app, Group $map)
 {
 }
```
##### Notes
- ```self``` and ```context``` is the same thing that is a type of \Exedra\Runtime\Context, the context of the current runtime.
- the services prefixed with ```app.``` will instead look inside the ```Exedra\Application``` container 
- without a prefix, ```context.```, ```self.``` or ```app.```, the resolve will only look for the service registered the Context object.

## Other route properties
#### Tagging and ajax
```php
/**
 * @tag users
 * @ajax true
 * @middleware \App\Middleware\Auth
 */
pubic function executeUsers()
{
}
```
#### Attributes (and sample middleware)
```php
public function middlewareAuth(\Exedra\Runtime\Context $context)
{
    if($context->attr('need_auth', false) && !$context->session->has('user_id'))
        throw new NotLoggedInException;
        
    return $context->next($context);
}

/**
 * @attr.need_auth true
 * @path /admin
 * @method any
 */
public function groupAdmin()
{
    return Admin::class;
}
```

#### All possible properties
```php
/**
 * @name admin_default
 * @method GET|POST
 * @path /admin/:controller/:action
 * @middleware \App\Middleware\Auth
 * @middleware \App\Middleware\Csrf
 * @middleware \App\Middleware\Limiter
 * @tag admin_default
 * @attr.session_timeout 36000
 * @config.request_limit 15
 */
public function executeAdminDefault($context)
{
    // nah, just a sample.
    $controller = $context->param('controller');
    $action = $context->param('action');
    
    return (new $controller)->{$action}($context);
}
```

## Exceptions
The non route property tags like **return**, **param**, and **throws** tags and will not be parsed.

## Console Commands
This package also provides a similar command on the original route listing, except that it added a little bit more details on the result.

```php
$app = new \Exedra\Application(__DIR__);

//... do some routing

$console = new \Symfony\Component\Console\Application();

$console->add(new \Exedron\Routeller\Console\Commands\RouteListCommand($app, $app->map));

$console->run();
```

## Notes
### Routing name
For some type of usage, like **executable** and **grouped** kind of route, the route name will be taken from the
 case-lowered remaining method name, IF no **@name** property is annotated.
 
##### Route name for the restful controller
It takes a combination of verb and the method name. For example,
```php
public function postProducts()
{
}

public function get()
{
}
```
Above routing will have a method name like **.post-products**. (**@web.products.post-products**)

For verb only method name, it'll just be the verb as the name. And an absolute name for it would look something like :
**@web.products.get**


### Ordering
Routing order is being read from top to above. So, it matters how you code the routing.
