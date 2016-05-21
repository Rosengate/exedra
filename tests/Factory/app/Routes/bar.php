<?php return function($group)
{
	$group->get('/')->name('bad')->execute(function()
	{
		return 'baz';
	});
}

?>