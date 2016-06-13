<?php return function($group)
{
	$group['bad']->get('/')->execute(function()
	{
		return 'baz';
	});
}

?>