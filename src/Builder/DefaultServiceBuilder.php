<?php
/**
 * Created by PhpStorm.
 * User: gauthier
 * Date: 19/05/15
 * Time: 15:18
 */

namespace ObjectivePHP\ServicesFactory\Builder;


use ObjectivePHP\ServicesFactory\Definition\ServiceDefinitionInterface;
use ObjectivePHP\ServicesFactory\Exception;
use ObjectivePHP\ServicesFactory\Factory;

class DefaultServiceBuilder extends ServiceBuilderAbstract implements FactoryAwareInterface
{

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * Service definition types this builder can handle
     *
     * @var array
     */
    protected $handledDefinitions = [ServiceDefinitionInterface::class];


    public function build(ServiceDefinitionInterface $serviceDefinition, $params = [])
    {

        // check compatibility with the service definition
        if(!$this->doesHandle($serviceDefinition))
        {
            throw new Exception(sprintf('"%s" service definition is not handled by this builder.', get_class($serviceDefinition)), Exception::INCOMPATIBLE_SERVICE_DEFINITION);
        }

        $serviceClassName = $serviceDefinition->getClassName();

        // merge service defined and runtime params
        $params = $serviceDefinition->getParams()->merge($params);

        $service = new $serviceClassName(...$params->getValues());

        return $service;
    }

    /**
     * @return Factory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param Factory $factory
     *
     * @return $this
     */
    public function setFactory(Factory $factory)
    {
        $this->factory = $factory;

        return $this;
    }

}