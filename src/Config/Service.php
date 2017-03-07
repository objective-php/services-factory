<?php
/**
 * This file is part of the Objective PHP project
 *
 * More info about Objective PHP on www.objective-php.org
 *
 * @license http://opensource.org/licenses/GPL-3.0 GNU GPL License 3.0
 */

namespace ObjectivePHP\ServicesFactory\Config;

use ObjectivePHP\Config\StackedValuesDirective;

class Service extends StackedValuesDirective
{
    public function __construct(array $value = [])
    {
        parent::__construct($value);
    }

    /**
     * Set the id of the service
     *
     * @param string $id
     *
     * @return Service
     */
    public function setId(string $id) : Service
    {
        $this->value['id'] = $id;

        return $this;
    }

    /**
     * Set the class used as a service
     *
     * @param string $class
     *
     * @return Service
     */
    public function setCLass(string $class) : Service
    {
        $this->value['class'] = $class;

        return $this;
    }

    /**
     * Set the setters to call when the service is instanciate
     *
     * @param array $setters
     *
     * @return Service
     */
    public function setSetters(array $setters) : Service
    {
        $this->value['setters'] = $setters;

        return $this;
    }

    /**
     * Add one setter to call when the service is instanciate
     *
     * @param string $name
     * @param mixed $params
     *
     * @return Service
     */
    public function addSetter(string $name, $params) : Service
    {
        $this->value['setters'][$name] = $params;

        return $this;
    }

    /**
     * Set the params of the constructor of the service
     *
     * @param array $params
     *
     * @return Service
     */
    public function setParams(array $params) : Service
    {
        $this->value['params'] = $params;

        return $this;
    }

    /**
     * Add one param in the constructor of the service
     *
     * @param string $name
     * @param mixed $value
     *
     * @return Service
     */
    public function addParam(string $name, $value) : Service
    {
        $this->value['params'][$name] = $value;

        return $this;
    }
}
