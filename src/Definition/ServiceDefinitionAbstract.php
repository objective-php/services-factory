<?php

namespace ObjectivePHP\ServicesFactory\Definition;

use ObjectivePHP\Primitives\String;
use ObjectivePHP\Primitives\Collection;

class ServiceDefinitionAbstract implements ServiceDefinitionInterface
{

    /**
     * @var String
     */
    protected $serviceId;

    /**
     * @var Collection
     */
    protected $aliases;

    /**
     * @var String
     */
    protected $className;

    /**
     * @var Collection
     */
    protected $params;

    /**
     * @var boolean
     */
    protected $shared;


    public function __construct($serviceId)
    {
        // init params
        $this->setParams([]);

        // assign default values
        $this->setServiceId($serviceId);
    }

    /**
     * @return String
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @param String $serviceId
     *
     * @return $this
     */
    public function setServiceId($serviceId)
    {
        $this->serviceId = String::cast($serviceId);

        return $this;
    }

    /**
     * @return Collection
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * @param Collection $aliases
     *
     * @return $this
     */
    public function setAliases($aliases)
    {
        $this->aliases = Collection::cast($aliases);

        return $this;
    }

    /**
     * @return String
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param String $className
     *
     * @return $this
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param Collection $params
     *
     * @return $this
     */
    public function setParams($params)
    {
        $this->params = Collection::cast($params);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isShared()
    {
        return $this->shared;
    }

    /**
     * @param boolean $shared
     *
     * @return $this
     */
    public function setShared($shared)
    {
        $this->shared = (bool) $shared;

        return $this;
    }





}