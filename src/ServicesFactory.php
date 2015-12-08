<?php
namespace ObjectivePHP\ServicesFactory;

use ObjectivePHP\Events\EventsHandler;
use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\Primitives\String\Str;
use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\FactoryAwareInterface;
use ObjectivePHP\ServicesFactory\Builder\PrefabServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\ServiceBuilderInterface;
use ObjectivePHP\ServicesFactory\Specs\AbstractServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface;

class ServicesFactory
{

    const EVENT_INSTANCE_BUILT = 'services-factory.instance.built';

    /**
     * @var EventsHandler
     */
    protected $eventsHandler;

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


        if($service instanceof ServiceReference)
        {
            $service = $service->getId();
        }

        $serviceSpecs = $this->getServiceSpecs($service);

        if(is_null($serviceSpecs))
        {
            throw new Exception(sprintf('Service reference "%s" matches no registered service in this factory', $service), Exception::UNREGISTERED_SERVICE_REFERENCE);
        }

        if (
            !$serviceSpecs->isStatic()
            || $this->getInstances()->lacks($service)
            || $params
        )
        {
            $builder = $this->resolveBuilder($serviceSpecs);

            if ($builder instanceof FactoryAwareInterface)
            {
                $builder->setFactory($this);
            }

            $instance = $builder->build($serviceSpecs, $params);;

            // before going further, let the rest of application know
            // that a new service instance has been built
            if($this->getEventsHandler())
            {
                // event name is suffixed with service reference to
                // ease specific matching for callbacks, especially
                // for Injectors
                $eventName = self::EVENT_INSTANCE_BUILT . '.' . $service;
                $this->getEventsHandler()->trigger($eventName, $this, compact('serviceSpecs', 'instance'));
            }

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
     * @param array $serviceSpecs Array of ServiceSpecsInterface of raw services (array)
     */
    public function registerService(...$servicesSpecs)
    {
        foreach($servicesSpecs as $serviceSpecs)
        {
            // if service specs is not an instance of ServiceSpecsInterface,
            // try to build the specs using factory
            if(!$serviceSpecs instanceof ServiceSpecsInterface)
            {
                try {
                    $serviceSpecs = AbstractServiceSpecs::factory($serviceSpecs);
                } catch (\Exception $e)
                {
                    throw new Exception(AbstractServiceSpecs::class . '::factory() was unable to build service specifications', Exception::INVALID_SERVICE_SPECS, $e);
                }
            }

            if(!$serviceSpecs instanceof ServiceSpecsInterface)
            {
                // the specs are still not valid
                throw new Exception('Service specifications are not an instance of ' . ServiceSpecsInterface::class , Exception::INVALID_SERVICE_SPECS);
            }

            $serviceId                  = Str::cast($serviceSpecs->getId())->lower();

            // prevent final services from being overridden
            if($previouslyRegistered = $this->getServiceSpecs((string) $serviceId))
            {
                // a service with same name already has been registered
                if($previouslyRegistered->isFinal())
                {
                    // as it is marked as final, it cannot be overridden
                    throw new Exception(sprintf('Cannot override service "%s" as it has been registered as a final service', $serviceId), Exception::FINAL_SERVICE_OVERRIDING_ATTEMPT);
                }
            }

            // store the service specs for further reference
            $this->services[(string) $serviceId] = $serviceSpecs;
        }

        return $this;
    }

    public function registerRawService($rawServiceSpecs)
    {
        $specs = AbstractServiceSpecs::factory($rawServiceSpecs);
        $this->registerService($specs);

        return $this;
    }

    /**
     * @param $serviceId
     *
     * @return ServiceSpecsInterface
     */
    public function getServiceSpecs($serviceId)
    {
        if($serviceId instanceof ServiceReference)
        {
            $serviceId = $serviceId->getId();
        }

        return $this->services[$serviceId] ?? null;
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
     * @return EventsHandler
     */
    public function getEventsHandler()
    {
        return $this->eventsHandler;
    }

    /**
     * @param EventsHandler $eventsHandler
     *
     * @return $this
     */
    public function setEventsHandler($eventsHandler)
    {
        $eventsHandler->setServicesFactory($this);

        $this->eventsHandler = $eventsHandler;

        return $this;
    }


}