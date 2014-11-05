Exédra
======
A multi-tier nestable routing PHP Framework.

Introduction
======
This simple nano (smaller than micro?) framework will first focus mainly on nestable route mapping, route-parameter based execution and which hopefully will provide much much modular application execution. Features and example will soon be provided.

History
======
The first unreleased version of exedra has quite constraining ungroupable routing capability, and has a lot of static usage. This version was intended to fix them, give more (self-proclaimed) powerful multi-tier nestable routing ability and hopefully will adapt to modern web development practices.

Main route-mapping API
======
##### Just to explain the structure k
```
## Structure/schema.
$app->map->addRoute(Array(
'route1'=>Array(
	'parameter1'=>'val',
	'parameter2'=>'val',
	'parameter3'=>'val')
	));
	
## little-example
$app->map->addRoute(Array(
'mainroute'=>Array(
	'method'=>'get,post',
	'uri'=>'some/uri',
	'execute'=>function(){ return 'something'; }
	)));
	
## little-bit-more example on subrouting/nestrouting.
$app->map->addRoute(Array(
'route2'=>Array(
	'method'=>'any',
	'uri'=>'home',
	'subroute'=>Array(
		// follow the same structure.
		'childroute1'=>Array(
			'method'=>'any',
			'uri'=>'about-us',
			'execute'=>function(){ return 'another thing'; }
		)
	)
));
```

Example
======
#### Real-life routing example :
Let's try build some example :
```
// require exedra, or wherever you can find it.
require "Exedra/Exedra.php";
$exedra	= new \Exedra\Exedra;

$myapp = $exedra->build("myapp",function($app)
{
	## used in the nearly bottom of the world.
	$loggedIn	= true;

	## no route found at all.
	$app->setExecutionFailRoute("error");

	$app->map->addRoute(Array(
		"error"=>Array("get","eh",function($param)
			{
				if($param['exception'])
					return $param['exception']->getMessage();
				else
					return $param['msg'];
			}),
		"home"=>Array(
			"method"=>"any",
			"uri"	=>"",
			"subroute"=>Array(
				"member"=>Array(
					"uri"	=>"ahli",
					"subroute"=>Array(
						"directory"=>Array(
							"method"=>"get",
							"uri"=>"direktori/[:myparam]",
							"execute"=>function($param)
								{
									return "my param is : ".$param['myparam'];
								}
							)
						)
					),
				"about"=>Array("get","about-us/[:page]",function($param)
					{
						return "you're he in home.about, are you looking for page '".$param['page']."'?";
					}),
				"contact"=>Array("get","[:uriparam]/contact-us",function($param)
					{
						return "Inside an home.contact, but you may come from somewhere if you could see this text : ".$param['myparam'];
					}),
				"routeception"=>Array("get","test",function() use($app)
					{
						return $app->execute("home.contact",Array("myparam"=>"routeception (o....o)"));
					}),
				"mypage"=>Array(
					"bind:execute"=>function($param,$execution) use($app)
					{
						## check page-slug, if it ever exists.
						if($param['page-slug'] != "remi-page")
						{
							return $app->execute("error",Array("msg"=>"Bad day for me : are you looking for remi-page/about-me?."));
						}

						## we may do the execution part in behalf of the main execution.
						return $execution($param,"i am gift from papa");
					},
					"method"=>"get",
					"uri"=>"[:page-slug]",
					"subroute"=>Array(
						"about-me"=>Array("get","about-me",function($param,$argFromPapa = null)
							{
								return "You're inside route home.mypage.about-me. Your page-slug is : ".$param['page-slug']." can u see this. : ".$argFromPapa;
							})
						)
					)
				)
			)
		));

	## much simplified subrouting.
	$app->map->addRoute(Array(
		"route1"=>Array("any","hello/[:text1]",Array(
			"subroute2"=>Array("any","[:text2]/world",Array(
				"subroute3"=>Array("get","[:text3]/you",function($param)
					{
						$paramtest[]	= "Far-away nested route test..";
						$paramtest[]	= "text1 : ".$param['text1'];
						$paramtest[]	= "text2 : ".$param['text2'];
						$paramtest[]	= "text3 : ".$param['text3'];

						return implode("<br>",$paramtest);
					})
				))
			))
		));

	## on route binding (all the subroute will be affected by this binding)
	$app->map->onRoute("home","bind:execute",function($param,$execution) use($app,$loggedIn)
	{
		if(!$loggedIn)
		{
			return $app->execute("error",Array("msg"=>"You're not logged in!"));
		}

		return $execution($param);
	});
});
```
#### Tests :
##### Example 1 : basics
```
echo $myapp->execute("home.member.directory",Array("myparam"=>"hello-world."));
// my param is : hello-world.
echo $myapp->execute(Array("method"=>"get","uri"=>"ahli/direktori/hello-world-2"));
// my param is : hello-world-2
```

##### Example 2 : routeception (re-routing)
```
echo $myapp->execute("home.routeception");
// Inside an home.contact, but you may come from somewhere if you could see this text : routeception (o....o)
```

##### Example 3 : testing the nested route
```
echo $myapp->execute(Array(
	"method"=>"get",
	"uri"=>"hello/where/and-which/world/are/you"
	));
/*
Far-away nested route test..
text1 : where
text2 : and-which
text3 : are
*/
```

##### Example 4.1 : bind pre-execution container, do re-routing to some a route 'error'.
```
echo $myapp->execute(Array(
	"method"=>"get",
	"uri"=>"remi-page/about-me"
	));
// Bad day for me : are you looking for remi-page/about-me?.
```

##### Example 4.2 : bind pre-execution container, do pass something to main execution container.
```
echo $myapp->execute(Array(
	"method"=>"get",
	"uri"=>"remi-page/about-me"
	));
// You're inside route home.mypage.about-me. Your page-slug is : remi-page. can u see this. : i am gift from papa
```

Development
======
- Still in development, and visioning for proper direction. But the basic route-mapping and execution concept was there. Will later adapt to PSR thingy, once everything is here. You may try, and see if you like it.
- currently you may only try uri based querying by passing the (the uri and request method) through execute() like the way i did for testing purpose.
- pre-execution container can only be binded once per route lineage (or ancestry?!). I need to think later how should I handle multiple binding, over the same lineage (or generation)
- Later will build more modular capability, like routes loading, appending, and etc.

Receptions
======
I am not sure if developers like this kind of routing (multi-tier nestable groupable route-mapping buzz w). Please do send me email at newrehmi@gmail.com whether you like it or not, or mail me there too if there're any suggestions. Thank you. ;D

Link to the former unreleased version : https://github.com/eimihar/exedra (excuse the emptiness and dirtiness of my github profile >..< )
