<?php
namespace Exedra\Support\Runtime\Url;

/**
 * Class UrlFactory
 * @package Exedra\Support\Runtime\Url
 *
 * @method \Exedra\Url\Url parent()
 * @method \Exedra\Url\Url create()
 * @method \Exedra\Url\Url __call()
 */
class UrlFactory extends \Exedra\Url\UrlFactory
{
    use UrlGeneratorTrait;
}