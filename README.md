Exédra
======
A multi-tier ~~nestable~~ nestful routing oriented PHP Framework.
p/s : still in development.

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
(To search routes for the tests below, just find the title, like [Example 5])
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
		"error"=>Array("get","eh",function($result)
			{
				if($result->exception)
					return $result->exception->getMessage();
				else
					return $result->msg;
			}),
		## [Example 1] : Basics
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
							"execute"=>function($result)
								{
									return "my param is : ".$result->myparam;
								}
							)
						)
					),
				"about"=>Array("get","about-us/[:page]",function($result)
					{
						return "you're he in home.about, are you looking for page '".$result->page."'?";
					}),
				"contact"=>Array("get","[:uriparam]/contact-us",function($result)
					{
						return "Inside an home.contact, but you may come from somewhere if you could see this text : ".$result->myparam;
					}),
				## [Example 2] : re-routing
				"routeception"=>Array("get","test",function() use($app)
					{
						return $app->execute("home.contact",Array("myparam"=>"routeception (o....o)"));
					}),
				"mypage"=>Array(
					## [Example 3.1] and [Example 3.2] pre-execution container bind
					"bind:execute"=>function($result) use($app)
					{
						## check page-slug, if it ever exists.
						if($result->page_slug != "remi-page")
						{
							return $app->execute("error",Array("msg"=>"Bad day for me : are you looking for remi-page/about-me?."));
						}

						## we may do the execution part in behalf of the main execution, through the passed container.
						return $result->container($result,"i am gift from papa");
					},
					"method"=>"get",
					"uri"=>"[:page_slug]",
					"subroute"=>Array(
						"about-me"=>Array("get","about-me",function($result,$argFromPapa = null)
							{
								return "You're inside route home.mypage.about-me. Your page-slug is : ".$result->page_slug." can u see this. : ".$argFromPapa;
							})
						)
					)
				)
			)
		));

	## [Example 4] : Much simplified subrouting.
	$app->map->addRoute(Array(
		"route1"=>Array("any","hello/[:text1]",Array(
			"subroute2"=>Array("any","[:text2]/world",Array(
				"subroute3"=>Array("get","[:text3]/you",function($result)
					{
						$paramtest[]	= "Far-away nested route test..";
						$paramtest[]	= "text1 : ".$result->text1;
						$paramtest[]	= "text2 : ".$result->text2;
						$paramtest[]	= "text3 : ".$result->text3;

						return implode("\n",$paramtest);
					})
				))
			))
		));

	## [Example 5] : Dynamic pre-execute container binder in a nested routing
	$app->map->addRoute(Array(
		"user"=>Array(
			"method"=>"any",
			"uri"=>"user/[:username]",
			"bind:execute"=>function($result) use($app)
				{
					## simple auth.
					if(!in_array($result->username, Array('remi','eimihar','exedra')))
						return $app->execute("error",Array('msg'=>'Unable to find this user.'));

					## example data (user).
					$result->userdata['name'] = "Rehmi";

					## or pass 2nd argument, to the next container.
					$secondArg	= "yeah";

					## return the container.
					return $result->container($result,$secondArg);
				},
			"subroute"=>Array(
				"blog"=>Array(
					"method"=>"any",
					"uri"	=>"blog/[:articleslug]",
					## 
					"bind:execute"=>function($result,$secondArg) use($app)
					{
						## simple article existance check.
						if(!in_array($result->articleslug,Array("remiblog","exedrablog")))
							return $app->execute("error",Array("msg"=>"Unable to find the blog for ".$result->userdata['name'].". Because ".$secondArg));

						$blogname	= "Ini blog saya";

						## return the container
						return $result->container($result,$blogname);
					},
					"subroute"=>Array(
						"index"=>Array(
							"method"=>"any",
							"uri"	=>"",
							"execute"=>function($result,$blogname)
								{
									$text	= "If you can read this, you're finally here, in '".$result->userdata['name']."/".$blogname;
									$text	.= "',\nwithout failure in numerous authentication based on parameter from the given uri.";
									$text	.= "\nTry change the value in the uri of this example. Try do it like :";
									$text	.= "\nuser/gades/blog/my-IT-world, or user/eimihar/blog/my-blog";

									return $text;
								}
							)
						)
					)
				)
			)
		));

	## on route binding (all the subroute will be affected by this binding)
	$app->map->onRoute("home","bind:execute",function($result) use($app,$loggedIn)
	{
		if(!$loggedIn)
		{
			return $app->execute("error",Array("msg"=>"You're not logged in!"));
		}

		return $result->container($result);
	});
});
```
#### Tests :
##### [Example 1] : Basics
```
echo $myapp->execute("home.member.directory",Array("myparam"=>"hello-world."));
// my param is : hello-world.
echo $myapp->execute(Array("method"=>"get","uri"=>"ahli/direktori/hello-world-2"));
// my param is : hello-world-2
```

##### [Example 2] : Routeception (re-routing)
```
echo $myapp->execute("home.routeception");
// Inside an home.contact, but you may come from somewhere if you could see this text : routeception (o....o)
```

##### [Example 3.1] : Bind pre-execution container, do re-routing to route 'error'.
```
echo $myapp->execute(Array(
	"method"=>"get",
	"uri"=>"remipage/about-me"
	));
// Bad day for me : are you looking for remi-page/about-me?.
```

##### [Example 3.2] : Bind pre-execution container, do pass something to main execution container.
```
echo $myapp->execute(Array(
	"method"=>"get",
	"uri"=>"remi-page/about-me"
	));
// You're inside route home.mypage.about-me. Your page-slug is : remi-page. can u see this. : i am gift from papa
```

##### [Example 4] : Testing the nested route
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

##### [Example 5] : Multiple pre-execution container binding test.
```
echo $myapp->execute(Array(
	"method"=>"get",
	"uri"=>"user/eimihar/blog/exedrablog"
	));
/*
If you can read this, you're finally here, in 'Rehmi/Ini blog saya',
without failure in numerous authentication based on parameter from the given uri.
Try change the value in the uri of this example. Try do it like :
user/gades/blog/my-IT-world, or user/eimihar/blog/my-blog
*/
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
