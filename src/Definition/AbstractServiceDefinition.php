<?php

namespace ObjectivePHP\ServicesFactory\Definition;

use ObjectivePHP\Primitives\String;
use ObjectivePHP\Primitives\Collection;
use ObjectivePHP\ServicesFactory\Exception;

class AbstractServiceDefinition implements ServiceDefinitionInterface
{

    /**
     * @var String
     */
    protected $id;

    /**
     * @var Collection
     */
    protected $aliases;

    /**
     * @var String
     */
    protected $class;

    /**
     * @var Collection
     */
    protected $params;

    /**
     * @var boolean
     */
    protected $static;


    public function __construct($serviceId)
    {
        // init params
        $this->setParams([]);

        // assign default values
        $this->setId($serviceId);
    }

    /**
     * @return String
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param String $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = String::cast($id);

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
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param String $class
     *
     * @return $this
     */
    public function setClass($class)
    {
        $this->class = $class;

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
    public function isStatic()
    {
        return $this->static;
    }

    /**
     * @param boolean $static
     *
     * @return $this
     */
    public function setStatic($static)
    {
        $this->static = (bool) $static;

        return $this;
    }

    static function factory($rawDefinition)
    {

        $rawDefinition = Collection::cast($rawDefinition);

        // first check an id has been provided
        if(!$rawDefinition->has('id'))
        {
            throw new Exception('Missing mandatory \'id\' parameter in service definition', Exception::INCOMPLETE_SERVICE_DEFINITION);
        }

        // set default service definition type
        $rawDefinition = $rawDefinition->add(['type' => ClassServiceDefinition::class]);

        $serviceDefinition = call_user_func([$rawDefinition['type'], 'factory'], $rawDefinition);

        // static
        if($rawDefinition->has('static'))
        {
            $serviceDefinition->setStatic($rawDefinition['static']);
        }

        // aliases
        if($rawDefinition->has('alias') || $rawDefinition->has('aliases'))
        {
            $aliases = new Collection();
            if($rawDefinition->has('alias')) $aliases[] = $rawDefinition['alias'];
            if($rawDefinition->has('aliases')) $aliases->merge($rawDefinition['aliases']);

            $serviceDefinition->setAliases($aliases);
        }

        return $serviceDefinition;
    }
}