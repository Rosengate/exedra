<?php
namespace Exedra\Http;

/**
 * A placeholder for upcoming PSR-7 impementation
 * implements Psr\Http\Message\UriInterface
 */
class Uri
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

	public function __construct($uri = '')
	{
		if(is_array($uri))
		{
			$this->applyParts($uri);
		}
		else
		{
			$uri = parse_url($ori = $uri);

			if($uri === false)
				throw new \InvalidArgumentException('Failed to build a URI by the given \''.$ori.'\'');

			$this->applyParts($uri);
		}
	}

	public function applyParts($parts)
	{
		foreach($parts as $part => $value)
			$this->{$part} = $value;
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
		if(!$this->host)
			return '';

		$authority = $this->host;

		$userInfo = $this->getUserInfo();

		if($userInfo)
			$authority = $userInfo.'@'.$authority;

		if($this->port)
			$authority = $authority.':'.$this->port;

		return $authority;
	}

	/**
	 * Get uri userinfo part
	 * @return string
	 */
	public function getUserInfo()
	{
		return $this->user ? ($this->pass ? $this->user.':'.$this->pass : $this->user) : '';
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

	public function setScheme($scheme)
	{
		$this->scheme = $scheme;

		return $this;
	}

	public function setUser($user)
	{
		$this->user = $user;

		return $this;
	}

	public function setPassword($password)
	{
		$this->pass = $password;

		return $this;
	}

	public function setHost($host)
	{
		$this->host = $host;

		return $this;
	}

	public function setPort($port)
	{
		$this->port = $port;

		return $this;
	}

	public function setPath($path)
	{
		$this->path = $path;

		return $this;
	}

	public function setQuery($query)
	{
		$this->query = $query;

		return $this;
	}

	public function setFragment($fragment)
	{
		$this->fragment = $fragment;

		return $this;
	}

	public function withScheme($scheme)
	{
		$uri = clone $this;

		return $uri->setScheme($scheme);
	}

	public function withUserInfo($user, $password = null)
	{
		$uri = clone $this;

		return $uri->setUser($user)->setPassword($password);
	}

	public function withHost($host)
	{
		$uri = clone $this;

		return $uri->setHost($host);
	}

	public function withPort($port)
	{
		$uri = clone $this;

		return $uri->setPort($port);
	}

	public function withPath($path)
	{
		$uri = clone $this;

		return $uri->setPath($path);
	}

	public function withQuery($query)
	{
		$uri = clone $this;

		return $uri->setQuery($query);
	}

	public function withFragment($fragment)
	{
		$uri = clone $this;

		return $uri->setFragment($fragment);
	}

	public function toString()
	{
		$uri = '';

		if($this->scheme)
			$uri = $this->scheme.':';

		$authority = $this->getAuthority();

		if($authority)
			$uri = $uri.'//'.$authority;

		if($this->path)
			$uri = $uri.($authority ? '/'.ltrim($this->path, '/') : $this->path);

		if($this->query)
			$uri .= '?'.$this->query;

		if($this->fragment)
			$uri .= '#'.$this->fragment;

		return $uri;
	}

	public function __toString()
	{
		return $this->toString();
	}
}