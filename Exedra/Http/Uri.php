<?php

namespace Exedra\Http;

use Psr\Http\Message\UriInterface;

/**
 * A placeholder for upcoming PSR-7 impementation
 * implements Psr\Http\Message\UriInterface
 */
class Uri implements UriInterface
{
    protected $uri;

    protected $scheme = '';

    protected $host = '';

    protected $user = '';

    protected $pass = '';

    protected $path = '';

    protected $query = '';

    protected $fragment = '';

    protected $port = '';

    /**
     * Uri constructor.
     * @param string|array $uri
     */
    public function __construct($uri = '')
    {
        if (is_array($uri)) {
            $this->applyParts($uri);
        } else {
            $uri = parse_url($ori = $uri);

            if ($uri === false)
                throw new \InvalidArgumentException('Failed to build a URI by the given \'' . $ori . '\'');

            $this->applyParts($uri);
        }
    }

    public function applyParts($parts)
    {
        foreach ($parts as $part => $value)
            $this->{$part} = $value;
    }

    public static function createFromAuthority($authority)
    {
        if (strpos($authority, '@') !== false) {
            @list($userInfo, $domain) = explode('@', $authority);
            @list($host, $port) = explode(':', $domain);
            @list($user, $password) = explode(':', $userInfo);

            return new static(array('user' => $user, 'password' => $password, 'host' => $host, 'port' => $port));
        } else {
            @list($host, $port) = explode(':', $authority);

            return new static(array('host' => $host, 'port' => $port));
        }
    }

    public static function createFromDomain($domain)
    {
        @list($host, $port) = explode(':', $domain);

        return new static(array('host' => $host, 'port' => $port));
    }

    /**
     * Get request scheme
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Get uri authority
     * @return string
     */
    public function getAuthority()
    {
        if (!$this->host)
            return '';

        $authority = $this->host;

        $userInfo = $this->getUserInfo();

        if ($userInfo)
            $authority = $userInfo . '@' . $authority;

        if ($this->port)
            $authority = $authority . ':' . $this->port;

        return $authority;
    }

    /**
     * Get uri userinfo part
     * @return string
     */
    public function getUserInfo()
    {
        return $this->user ? ($this->pass ? $this->user . ':' . $this->pass : $this->user) : '';
    }

    /**
     * Get uri host part
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get uri port part
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Get uri path part
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get query string
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get uri fragment part
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @param $scheme
     * @return $this
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * @param $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->pass = $password;

        return $this;
    }

    /**
     * @param $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @param $port
     * @return $this
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @param $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param $query
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @param $fragment
     * @return $this
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;

        return $this;
    }

    /**
     * @param string $scheme
     * @return Uri
     */
    public function withScheme($scheme)
    {
        $uri = clone $this;

        return $uri->setScheme($scheme);
    }

    /**
     * @param string $user
     * @param null $password
     * @return $this
     */
    public function withUserInfo($user, $password = null)
    {
        $uri = clone $this;

        return $uri->setUser($user)->setPassword($password);
    }

    /**
     * @param string $host
     * @return Uri
     */
    public function withHost($host)
    {
        $uri = clone $this;

        return $uri->setHost($host);
    }

    /**
     * @param int|null $port
     * @return Uri
     */
    public function withPort($port)
    {
        $uri = clone $this;

        return $uri->setPort($port);
    }

    /**
     * @param string $path
     * @return Uri
     */
    public function withPath($path)
    {
        $uri = clone $this;

        return $uri->setPath($path);
    }

    /**
     * @param string $query
     * @return Uri
     */
    public function withQuery($query)
    {
        $uri = clone $this;

        return $uri->setQuery($query);
    }

    /**
     * @param string $fragment
     * @return Uri
     */
    public function withFragment($fragment)
    {
        $uri = clone $this;

        return $uri->setFragment($fragment);
    }

    /**
     * @return string
     */
    public function toString()
    {
        $uri = '';

        if ($this->scheme)
            $uri = $this->scheme . ':';

        $authority = $this->getAuthority();

        if ($authority)
            $uri = $uri . '//' . $authority;

        if ($this->path)
            $uri = $uri . ($authority ? '/' . ltrim($this->path, '/') : $this->path);

        if ($this->query)
            $uri .= '?' . $this->query;

        if ($this->fragment)
            $uri .= '#' . $this->fragment;

        return $uri;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}