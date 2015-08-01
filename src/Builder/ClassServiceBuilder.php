<?php

namespace ObjectivePHP\ServicesFactory\Builder;


use ObjectivePHP\ServicesFactory\Definition\ClassServiceDefinition;
use ObjectivePHP\ServicesFactory\Definition\ServiceDefinitionInterface;
use ObjectivePHP\ServicesFactory\Exception;
use ObjectivePHP\ServicesFactory\Factory;
use ObjectivePHP\ServicesFactory\Reference;

class ClassServiceBuilder extends ServiceBuilderAbstract implements FactoryAwareInterface
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
    protected $handledDefinitions = [ClassServiceDefinition::class];


    /**
     * @param ClassServiceDefinition $serviceDefinition
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public function build(ServiceDefinitionInterface $serviceDefinition, $params = [])
    {

        // check compatibility with the service definition
        if(!$this->doesHandle($serviceDefinition))
        {
            throw new Exception(sprintf('"%s" service definition is not handled by this builder.', get_class($serviceDefinition)), Exception::INCOMPATIBLE_SERVICE_DEFINITION);
        }

        $serviceClassName = $serviceDefinition->getClass();

        // merge service defined and runtime params
        $params = $serviceDefinition->getParams()->merge($params);

        // substitute params with referenced services
        $params->each(function (&$value)
        {
            if($value instanceof Reference)
            {
                $value = $this->getFactory()->get($value->getId());
            }
        });


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