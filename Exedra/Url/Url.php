<?php
namespace Exedra\Url;

class Url extends \Exedra\Http\Uri
{
    /**
     * @return array
     */
    public function getQueryParams()
    {
        $params = array();

        parse_str($this->query, $params);

        return $params;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setQueryParams(array $params)
    {
        $this->query = http_build_query($params);

        return $this;
    }

    /**
     * @param $key
     * @param string|array $value
     * @return $this
     */
    public function addQueryParam($key, $value)
    {
        if(!$this->query)
            return $this->setQueryParams(array($key => $value));

        if(is_string($value))
            $this->query .= '&' . $key . '=' . $value;
        else
            $this->query .= '&' . http_build_query(array($key => $value));

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function addQueryParams(array $params)
    {
        foreach($params as $key => $value)
            $this->addQueryParam($key, $value);

        return $this;
    }
}