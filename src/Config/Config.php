<?php

/**
 * @todo Write file documentation.
 */

namespace Finlet\flexmail\Config;

class Config implements ConfigInterface
{

    private $container = [];

    public function get($key)
    {
        return $this->set($key);
    }

    public function set($key, $value = null)
    {
        if ($value !== null) {
            $this->container[$key] = $value;
        }

        return $this->container[$key];
    }
}
