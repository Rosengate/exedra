<?php
namespace Exedra\Contracts\Url;

use Exedra\Url\Url;

interface UrlGenerator
{
    /**
     * @return string|Url
     */
    public function previous();

    /**
     * @return string|Url
     */
    public function to($url);

    /**
     * @return string|Url
     */
    public function route($route);

    /**
     * @return string|Url
     */
    public function current();
}