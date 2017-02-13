<?php
namespace Exedra\Session;

use Exedra\Support\DotArray;

/**
 * A simple session manager based on php native session
 */
class Session
{
    /**
     * Referenced storage for session data
     */
    protected $storage;

    /**
     * Prefix
     */
    protected $prefix;

    public function __construct(&$storage = null)
    {
        if($storage !== null)
            $this->storage = &$storage;
        else
            $this->start();
    }

    /**
     * Start the session
     */
    public function start()
    {
        if(!self::hasStarted())
            session_start();

        $this->storage = &$_SESSION;
    }

    /**
     * Point the current storage to the prefixing point.
     * This session manager will later point every session operation (has, get, set, getAll, destroy) on this key group
     * Another prefix will not to a new prefix, except append to the current reference.
     * @param $prefix
     * @return Session
     */
    public function setPrefix($prefix)
    {
        $storage = &DotArray::getReference($this->getStorage(), $prefix);

        $this->set($prefix, $storage);

        $this->storage = &$storage;

        return $this;
    }

    /**
     * not sure but look's ugly because this is the only use of static :X
     * @return bool
     */
    public static function hasStarted()
    {
        return session_status() != PHP_SESSION_NONE;
    }

    /**
     * Get referenced storage variable
     * @return &storage
     */
    public function &getStorage()
    {
        return $this->storage;
    }

    /**
     * Set a session by the given key.
     * @param string key
     * @param mixed value
     * @return \Exedra\Session\Session
     */
    public function set($key, $value)
    {
        DotArray::set($this->getStorage(), $key,$value);

        return $this;
    }

    /**
     * Get the session by the given key.
     * Default on null
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $data = DotArray::get($this->getStorage(), $key);

        return $data === null ? $default : $data;
    }

    /**
     * Get everything within storage
     * @return mixed
     */
    public function getAll()
    {
        return $this->storage;
    }

    /**
     * Check whether session exist by the given key.
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return DotArray::has($this->storage, $key);
    }

    /**
     * Get PHP native session id
     * @return string
     */
    public function id()
    {
        return session_id();
    }

    /**
     * Regenerate session id
     * @return boolean
     */
    public function regenerate()
    {
        return session_regenerate_id();
    }

    /**
     * PHP session_write_close
     * @return void
     */
    public function close()
    {
        session_write_close();
    }

    /**
     * Destroy session, or only the given key.
     * @param string $key
     * @return \Exedra\Session\Session
     */
    public function destroy($key = null)
    {
        DotArray::delete($this->storage, $key);

        return $this;
    }
}
