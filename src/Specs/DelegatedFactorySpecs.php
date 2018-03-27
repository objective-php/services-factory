<?php

namespace ObjectivePHP\ServicesFactory\Specs;

use ObjectivePHP\Invokable\Invokable;
use ObjectivePHP\Invokable\InvokableInterface;
use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Exception\Exception;

class DelegatedFactorySpecs extends AbstractServiceSpecs
{

    /**
     * @var Invokable
     */
    protected $factory;


    /**
     * @param $id
     * @param $factory
     * @param $params
     */
    public function __construct($id, $factory, $params = [])
    {
        parent::__construct($id, $params);

        $this->setFactory($factory);
    }

    /**
     * Service definition factory
     *
     * IT IS NOT RECOMMENDED TO CALL THIS METHOD EXPLICITLY
     *
     * Please call AbstractServiceSpecs::factory(), that will
     * forward to the appropriate factory after having performed
     * basic sanity checks ('id' presence)
     *
     * @param array|Collection $rawDefinition
     * @throws Exception
     */
    public static function factory($rawDefinition)
    {

        $rawDefinition = Collection::cast($rawDefinition);

        // then check check a class has been provided
        if (!$rawDefinition->has('factory')) {
            throw new Exception('Missing \'factory\' parameter', Exception::INCOMPLETE_SERVICE_SPECS);
        }

        $serviceDefinition = new DelegatedFactorySpecs($rawDefinition['id'], $rawDefinition['factory']);

        // constructor params
        if ($rawDefinition->has('params')) {
            $serviceDefinition->setParams($rawDefinition['params']);
        }

        return $serviceDefinition;
    }

    /**
     * @return InvokableInterface
     */
    public function getFactory()
    {
        return $this->factory;
    }
    
    /**
     * @param mixed $factory
     *
     * @return $this
     */
    public function setFactory($factory)
    {
        $this->factory = Invokable::cast($factory);
        
        return $this;
    }
    
    /**
     * Delegated factories can't be autoaliased
     *
     * @return null
     */
    public function getAutoAlias()
    {
        return null;
    }
}
