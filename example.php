<?php
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
				"routeception"=>Array("get","test",function() use($app)
					{
						return $app->execute("home.contact",Array("myparam"=>"routeception (o....o)"));
					}),
				"mypage"=>Array(
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

	## much simplified subrouting.
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

## Example 1 : basics
echo "<pre>\n";
echo $myapp->execute("home.member.directory",Array("myparam"=>"hello-world."))."\n";
echo $myapp->execute(Array("method"=>"get","uri"=>"ahli/direktori/hello-world-2"))."\n";
echo "\n";

## Example 2 : routeception (re-routing)
echo $myapp->execute("home.routeception")."\n";
echo "\n";

## Example 3 : testing the nested route
echo $myapp->execute(Array(
	"method"=>"get",
	"uri"=>"hello/where/and-which/world/are/you"
	))."\n"."\n";

## Example 4.1 : bind pre-execution container, do re-routing to some a route 'error'.
echo $myapp->execute(Array(
	"method"=>"get",
	"uri"=>"remipage/about-me"
	))."\n\n";

## Example 4.2 : bind pre-execution container, do pass something to main execution container.
echo $myapp->execute(Array(
	"method"=>"get",
	"uri"=>"remi-page/about-me"
	));

?>