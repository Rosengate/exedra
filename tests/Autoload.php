<?php
// just autoloading things. no test here. ;)
spl_autoload_register(function($class)
{
	$path = __DIR__.'/../'.$class.'.php';

	if(file_exists($path))
		require_once $path;
});


