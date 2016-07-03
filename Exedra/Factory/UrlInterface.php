<?php
namespace Exedra\Factory;

interface UrlInterface
{
	public function previous();

	public function to($url);

	public function route($route);

	public function current();
}