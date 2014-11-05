<?php
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
								return "You're inside route home.mypage.about-me. Your page-slug is : ".$param['page-slug'].". can u see this? : ".$argFromPapa;
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

						return implode("\n",$paramtest);
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