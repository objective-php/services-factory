<?php

namespace ObjectivePHP\ServicesFactory\Specification;


use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Exception\ServicesFactoryException;

class PrefabServiceSpecification extends AbstractServiceSpecification
{

    /**
     * @var mixed Previously instantiated service (can be any type of value, not only objects)
     */
    protected $instance;

    /**
     * @param $id
     * @param mixed $instance
     */
    public function __construct($id, $instance)
    {
        parent::__construct($id);

        $this->setInstance($instance);
    }

    static public function factory($rawDefinition)
    {
        $rawDefinition = Collection::cast($rawDefinition);

        if (!$rawDefinition->has('instance')) {
            throw new ServicesFactoryException('Missing \'instance\' parameter',
                ServicesFactoryException::INCOMPLETE_SERVICE_SPECS);
        }

        $serviceDefinition = new PrefabServiceSpecification($rawDefinition['id'], $rawDefinition['instance']);

        return $serviceDefinition;
    }

    /**
     * @return mixed
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @param mixed $instance
     *
     * @return $this
     */
    public function setInstance($instance)
    {
        $this->instance = $instance;
        return $this;
    }

    public function getAutoAliases()
    {
        $autoAliases = [];

        if (is_object($this->instance)) {
            $autoAliases = [get_class($this->instance)];
            $autoAliases = array_merge($autoAliases, class_implements(get_class($this->instance)));
        }

        return array_unique($autoAliases);
    }


}
