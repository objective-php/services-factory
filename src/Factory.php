<?php
namespace ObjectivePHP\ServicesFactory;

use ObjectivePHP\Primitives\Collection;
use ObjectivePHP\Primitives\String;
use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\FactoryAwareInterface;
use ObjectivePHP\ServicesFactory\Builder\PrefabServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\ServiceBuilderInterface;
use ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface;

class Factory
{

    /**
     * @var Collection
     */
    protected $services;

    /**
     * @var Collection
     */
    protected $builders;

    /**
     * @var Collection
     */
    protected $instances;

    public function __construct()
    {
        // init collections
        $this->services = (new Collection())->restrictTo(ServiceSpecsInterface::class);
        $this->builders = (new Collection())->restrictTo(ServiceBuilderInterface::class);
        $this->instances = new Collection();

        // load default builders
        $this->builders->append(new ClassServiceBuilder(), new PrefabServiceBuilder());
    }

    /**
     * @param      $service string      Service ID or class name
     * @param null $params
     */
    public function get($service, $params = [])
    {

        $serviceSpecs = $this->getServiceSpecs($service);

        if (
            !$serviceSpecs->isStatic()
            || $this->getInstances()->lacks($service)
            || ($this->getInstances()->has($service) && $params)
        )
        {
            $builder = $this->resolveBuilder($serviceSpecs);

            if ($builder instanceof FactoryAwareInterface)
            {
                $builder->setFactory($this);
            }

            $instance = $builder->build($serviceSpecs, $params);;

            if (!$serviceSpecs->isStatic() || $params)
            {
                // if params are passed, we don't store the instance for
                // further reference, even if the service is static
                return $instance;
            }
            else
            {
                $this->instances[$service] = $instance;
            }

        }

        return $this->instances[$service];

    }

    /**
     * @param ServiceSpecsInterface $serviceSpecs
     */
    public function registerService(ServiceSpecsInterface $serviceSpecs)
    {
        $serviceId = String::cast($serviceSpecs->getId())->lower()->getInternalValue();
        $this->services[$serviceId] = $serviceSpecs;
    }

    /**
     * @param $serviceId
     *
     * @return ServiceSpecsInterface
     */
    public function getServiceSpecs($serviceId)
    {
        return @$this->services[$serviceId] ?: null;
    }

    /**
     * @param $serviceId
     *
     * @return bool
     */
    public function isServiceRegistered($serviceId)
    {
        return isset($this->services[$serviceId]);
    }

    /**
     * @param ServiceBuilderInterface $builder
     */
    public function registerBuilder(ServiceBuilderInterface $builder)
    {
        // append new builder
        $this->builders[] = $builder;
    }

    public function resolveBuilder(ServiceSpecsInterface $serviceSpecs)
    {

        /** @var ServiceBuilderInterface $builder */
        foreach ($this->getBuilders() as $builder)
        {
            if ($builder->doesHandle($serviceSpecs)) return $builder;
        }

        return null;
    }

    /**
     * @return Collection
     */
    public function getBuilders()
    {
        return $this->builders;
    }

    /**
     * @return Collection
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * @return Collection
     */
    public function getInstances()
    {
        return $this->instances;
    }

    /**
     * @param Collection $instances
     *
     * @return $this
     */
    public function setInstances($instances)
    {
        $this->instances = $instances;
        return $this;
    }

}