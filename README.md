Exédra
======
A multi-tier nestful routing oriented PHP Framework.

Introduction
======
This PHP framework focuses mainly on nestable route mapping, route oriented based execution and which hopefully will provide much modular application execution. Route is unique, and identified by name. Imagine developing an application hierarchically down the depth without losing the identity of the executions you planned, while stacking middlewares down the path on every possible node of route.

History
======
The first unreleased version of exedra has so much constraining ungroupable routing capability, and has a lot of static usages (which is extremely violating). This version was intended to fix them, give more controllable multi-tier nestable routing ability and hopefully would adapt to modern web development practices, while staying simple, flexible and contextual at the same time.

Installation
======
Clone
========
~~~
git clone https://github.com/rosengate/exedra project_name
~~~
and in your front-controller file (like index.php), just include Exedra.php wherever you can find it. For more information, refer documentation.
~~~
require_once "Exedra\Exedra.php";
$exedra = new \Exedra\Exedra(__DIR__);
~~~

Composer
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

and then, use composer autoloader, in your front-controller file. (index.php)
~~~
require_once "vendor/autoload.php";
$exedra = new \Exedra\Exedra(__DIR__);
~~~

Documentation
===
Documentation and the homebase for exedra is currently hosted here : http://exedra.rosengate.com.

Examples
======
Some of the projects built on top of exedra :

http://github.com/rosengate/exedra-web (hosted at exedra.rosengate.com)

http://github.com/eimihar/persona (hosted at persona.rosengate.com or eimihar.rosengate.com)


Suggestion and issues
======
I am not sure if developers like this kind of routing. Please do send me email at newrehmi@gmail.com whether you like it or not, or mail me there too if there're any suggestions. Thank you.
