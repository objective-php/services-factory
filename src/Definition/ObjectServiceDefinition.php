<?php

namespace ObjectivePHP\ServicesFactory\Definition;


use ObjectivePHP\Primitives\Collection;
use ObjectivePHP\ServicesFactory\Exception;

class ObjectServiceDefinition extends AbstractServiceDefinition
{

    /**
     * @var object Previously instantiated service
     */
    protected $instance;

    static public function factory($rawDefinition)
    {
        $rawDefinition = Collection::cast($rawDefinition);
        $serviceDefinition = new ObjectServiceDefinition($rawDefinition['id']);

        if(!$rawDefinition->has('instance'))
        {
            throw new Exception('Missing \'instance\' parameter', Exception::INCOMPLETE_SERVICE_DEFINITION);
        }

        if(!is_object($instance = $rawDefinition['instance']))
        {
            throw new Exception('Instance parameter must be an object', Exception::INVALID_SERVICE_DEFINITION);
        }

        $serviceDefinition->setInstance($instance);

        return $serviceDefinition;
    }

    /**
     * @return object
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @param object $instance
     *
     * @return $this
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;
        return $this;
    }


}