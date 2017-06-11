<?php
namespace Exedra\Http;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * A placeholder for upcoming PSR-7 impementation
 * implements Psr\Http\Message\MessageInterface
 */
class Message implements MessageInterface
{
    protected $protocol = '1.1';

    /**
     * A case lowered key headers.
     * A supposed copies for headerLines
     * @var array headers
     */
    protected $headers = array();

    /**
     * Headers that store original key case
     * @var array headerLines
     */
    protected $headerLines = array();

    /**
     * Stream
     * @var Stream
     */
    protected $body;

    public function __construct(array $headers, StreamInterface $body, $protocol = '1.1')
    {
        $this->headers = $headers;

        $this->body = $body;

        $this->protocol = $protocol;
    }

    public function __clone()
    {
        $this->body = clone $this->body;
    }

    /**
     * @param string $name
     * @return string
     */
    public static function headerCase($name)
    {
        return str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($name))));
    }

    /**
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    /**
     * @param $version
     * @return $this
     */
    public function setProtocolVersion($version)
    {
        $this->protocol = $version;

        return $this;
    }

    /**
     * @param string $version
     * @return Message|static
     */
    public function withProtocolVersion($version)
    {
        $message = clone $this;

        return $message->setProtocolVersion($version);
    }

    /**
     * Get all headers
     * @return array
     */
    public function getHeaders()
    {
        return $this->headerLines;
    }

    /**
     * Get header values
     * @param string $header
     * @return array
     */
    public function getHeader($header)
    {
        return isset($this->headers[$name = strtolower($header)]) ? $this->headers[$name] : array();
    }

    /**
     * Get header value
     * @param string $name
     * @return string
     */
    public function getHeaderLine($name)
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * @param string $name
     * @param string|\string[] $value
     * @return Message|static
     */
    public function withHeader($name, $value)
    {
        $message = clone $this;

        return $message->setHeader($name, $value);
    }

    /**
     * @param $name
     * @param $value
     * @return Message
     */
    public function withAddedHeader($name, $value)
    {
        if(!$this->hasHeader($name))
            return $this->withHeader($name, $value);

        $message = clone $this;

        return $message->addHeader($name, $value);
    }

    /**
     * @param string $name
     * @return Message
     */
    public function withoutHeader($name)
    {
        $message = clone $this;

        if(!$this->hasHeader($name))
            return $message;

        return $message->removeHeader($name);
    }

    /**
     * Get messange body Stream
     * @return \Exedra\Http\Stream
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param $body
     * @param string $mode
     * @return $this
     */
    public function setBody($body, $mode = 'r+')
    {
        switch(gettype($body))
        {
            case 'string':
                $this->body = Stream::createFromContents($body, $mode);
            break;
            case 'object':
                if($body instanceof Stream)
                    $this->body = $body;
                else
                    $this->body = new Stream($body, $mode);
            break;
        }

        return $this;
    }

    /**
     * @param StreamInterface $body
     * @return Message
     */
    public function withBody(StreamInterface $body)
    {
        $message = clone $this;

        switch(gettype($body))
        {
            case 'string':
                $message->body = Stream::createFromContents($body);
                break;
            case 'object':
                if($body instanceof Stream)
                    $message->body = $body;
                else
                    $message->body = new Stream($body, 'r+');
                break;
        }

        return $message;
    }

    /**
     * @param string $header
     * @return bool
     */
    public function hasHeader($header)
    {
        return isset($this->headers[strtolower($header)]);
    }

    /**
     * @param $name
     * @param $value
     * @return bool
     */
    public function headerHas($name, $value)
    {
        $name = strtolower($name);

        if(!isset($this->headers[$name]))
            return false;

        return in_array($value, $this->headers[$name]);
    }

    public function clearHeaders()
    {
        $this->headers = array();
        $this->headerLines = array();
    }

    /**
     * @param array $headerLines
     * @return $this
     */
    public function setHeaders(array $headerLines)
    {
        foreach($headerLines as $header => $values)
            $this->headers[strtolower($header)] = $values;

        $this->headerLines = $headerLines;

        return $this;
    }

    /**
     * Set header as if it's new
     * @param string $header
     * @param array|string $value
     * @return $this
     */
    public function setHeader($header, $value)
    {
        $value = !is_array($value) ? array($value) : array_map('trim', $value);

        $name = strtolower($header);

        $this->headers[$name] = $value;

        foreach(array_keys($this->headerLines) as $key)
            if(strtolower($key) == $name)
                unset($this->headerLines[$key]);

        $this->headerLines[$header] = $value;

        return $this;
    }

    /**
     * Add header value(s)
     * @param string $header
     * @param string|array value
     * @return $this
     */
    public function addHeader($header, $value)
    {
        $name = strtolower($header);

        if(is_array($value))
        {
            foreach($value as $v)
                $this->headers[$name][] = trim($v);

            foreach(array_keys($this->headerLines) as $key)
                if(strtolower($key) == $name)
                    unset($this->headerLines[$key]);

            $this->headerLines[$header] = $this->headers[$name];
        }
        else
        {
            $this->headers[$name][] = trim($value);

            foreach(array_keys($this->headerLines) as $key)
                if(strtolower($key) == $name)
                    unset($this->headerLines[$key]);

            $this->headerLines[$header] = $this->headers[$name];
        }

        return $this;
    }

    /**
     * Remove header
     * @param string $header
     * @return $this
     */
    public function removeHeader($header)
    {
        $name = strtolower($header);
        unset($this->headers[$name]);

        foreach($this->headerLines as $key => $value)
            if(strtolower($key) == $name)
                unset($this->headerLines[$key]);

        return $this;
    }
}