<?php
namespace Exedra\Url;

class Url extends \Exedra\Http\Uri
{
    public function getParams()
    {
        return parse_str($this->query);
    }

    public function setParams(array $params)
    {
        $this->query = http_build_query($params);

        return $this;
    }

    public function addParam($key, $value)
    {
        if(!$this->query)
            return $this->setParams(array($key => $value));

        $this->query .= '&'.$key.'=' . $value;

        return $this;
    }

    public function addParams(array $params)
    {
        foreach($params as $key => $value)
            $this->addParam($key, $value);

        return $this;
    }
}