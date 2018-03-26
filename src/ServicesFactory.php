<?php

namespace ObjectivePHP\ServicesFactory;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Interop\Container\ContainerInterface;
use ObjectivePHP\Config\Config;
use ObjectivePHP\Invokable\Invokable;
use ObjectivePHP\Matcher\Matcher;
use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\Primitives\String\Str;
use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\DelegatedFactoryBuilder;
use ObjectivePHP\ServicesFactory\Builder\PrefabServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\ServiceBuilderInterface;
use ObjectivePHP\ServicesFactory\Exception\Exception;
use ObjectivePHP\ServicesFactory\Exception\ServiceNotFoundException;
use ObjectivePHP\ServicesFactory\Specs\AbstractServiceSpecs;
use ObjectivePHP\ServicesFactory\Specs\InjectionAnnotationProvider;
use ObjectivePHP\ServicesFactory\Specs\ServiceSpecsInterface;
use phpDocumentor\Reflection\DocBlockFactory;

class ServicesFactory implements ContainerInterface
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
    
    /**
     * @var Collection
     */
    protected $injectors;
    
    /**
     * @var AnnotationReader
     */
    protected $annotationsReader;
    
    /**
     * @var array
     */
    protected $delegateContainers = [];

    /**
     * @var array
     */
    protected $registeredAliases = [];

    /**
     * @var array
     */
    protected $parents = [];

    /**
     * ServicesFactory constructor.
     */
    public function __construct()
    {
        $this->parents[] = $this;

        // init collections
        $this->services  = (new Collection())->restrictTo(ServiceSpecsInterface::class);
        $this->builders  = (new Collection())->restrictTo(ServiceBuilderInterface::class);
        $this->injectors = new Collection();
        $this->instances = new Collection();
        
        // register default annotation reader
        AnnotationRegistry::registerFile(__DIR__ . '/Annotation/Inject.php');
        $this->setAnnotationsReader(new AnnotationReader());
        
        // load default builders
        $this->builders->append(new ClassServiceBuilder(), new PrefabServiceBuilder(), new DelegatedFactoryBuilder());
    }
    
    public function registerRawService($rawServiceSpecs)
    {
        $specs = AbstractServiceSpecs::factory($rawServiceSpecs);
        $this->registerService($specs);
        
        return $this;
    }
    
    /**
     *
     * @param array $servicesSpecs
     *
     * @return $this
     * @throws Exception
     */
    public function registerService(...$servicesSpecs)
    {
        foreach ($servicesSpecs as $serviceSpecs) {
            // if service specs is not an instance of ServiceSpecsInterface,
            // try to build the specs using factory
            if (!$serviceSpecs instanceof ServiceSpecsInterface) {
                try {
                    $serviceSpecs = AbstractServiceSpecs::factory($serviceSpecs);
                } catch (\Exception $e) {
                    throw new Exception(AbstractServiceSpecs::class . '::factory() was unable to build service specifications',
                        Exception::INVALID_SERVICE_SPECS, $e);
                }
            }
            
            $serviceId = Str::cast($serviceSpecs->getId())->lower();
            
            // prevent final services from being overridden
            if ($previouslyRegistered = $this->getServiceSpecs((string)$serviceId)) {
                // a service with same name already has been registered
                if ($previouslyRegistered->isFinal()) {
                    // as it is marked as final, it cannot be overridden
                    throw new Exception(sprintf('Cannot override service "%s" as it has been registered as a final service',
                        $serviceId), Exception::FINAL_SERVICE_OVERRIDING_ATTEMPT);
                }
            }
            
            // store the service specs for further reference
            $this->services[(string)$serviceId] = $serviceSpecs;

            $aliases = $serviceSpecs->getAliases() ?: [];
            foreach($aliases as $alias) {

                if ($previouslyRegistered = $this->getServiceSpecs((string)$alias)) {
                    // a service with same name already has been registered
                    if ($previouslyRegistered->isFinal()) {
                        // as it is marked as final, it cannot be overridden
                        throw new Exception(sprintf('Cannot override service "%s" using alias "%s" as it has been registered as a final service',
                            $serviceId, $alias), Exception::FINAL_SERVICE_OVERRIDING_ATTEMPT);
                    }
                }

                $this->registeredAliases[$this->normalizeServiceId($alias)] = (string)$serviceId;
            }
        }
        
        return $this;
    }
    
    /**
     * @param $service
     *
     * @return ServiceSpecsInterface
     */
    public function getServiceSpecs($service)
    {

        if ($service instanceof ServiceReference) {
            $service = $service->getId();
        }

        $service = $this->normalizeServiceId($service);

        $specs = $this->services[$service] ?? null;

        if (is_null($specs)) {
            if (isset($this->registeredAliases[$service])) {
                $service = $this->registeredAliases[$service];
            }
        }
        $specs = $this->services[$service] ?? null;

        if (is_null($specs)) {
            $matcher = new Matcher();
            foreach ($this->services as $id => $specs) {
                if ($matcher->match($service, $id)) {
                    return (clone $specs)->setId($service);
                }
                $specs = null;
            }
        }
        
        return $specs;
    }
    
    /**
     * @param ServiceBuilderInterface $builder
     */
    public function registerBuilder(ServiceBuilderInterface $builder)
    {
        // append new builder
        $this->builders[] = $builder;
    }
    
    /**
     * @return Collection
     */
    public function getServices()
    {
        return $this->services;
    }
    
    /**
     * @param $injector invokable
     *
     * @return $this
     */
    public function registerInjector($injector)
    {
        
        if (!is_callable($injector)) {
            // turn injector to Invokable if it is not a native callable
            $injector = Invokable::cast($injector);
        }
        
        $this->injectors[] = $injector;
        
        return $this;
    }
    
    /**
     * @param ContainerInterface $delegate
     *
     * @return $this
     */
    public function registerDelegateContainer(ContainerInterface $delegate)
    {
        $this->delegateContainers[] = $delegate;
        
        return $this;
    }
    
    /**
     *
     */
    public function getConfig(): Config
    {
        if (!$this->has('config')) {
            throw new Exception('No "config" service has been registered in this factory',
                Exception::UNKNOWN_SERVICE_SPECS);
        }
        
        $config = $this->get('config');
        
        if (!$config instanceof Config) {
            throw new Exception('Registered service "config" is not an instance of ' . Config::class,
                Exception::INCOMPATIBLE_SERVICE_DEFINITION);
        }
        
        return $config;
    }
    
    /**
     * Proxy for isServiceRegistered()
     *
     * This method ensures ContainerInterface compliance
     *
     * @param string|ServiceReference $service
     *
     * @return bool
     */
    public function has($service)
    {
        return $this->isServiceRegistered($service);
    }
    
    /**
     * @param            $service string      Service ID or class name
     * @param array|null $params
     *
     * @return mixed|null
     * @throws Exception
     */
    public function get($service, $params = [])
    {
        
        $service = $this->normalizeServiceId($service);
        
        $serviceSpecs = $this->getServiceSpecs($service);
        
        if (is_null($serviceSpecs)) {
            foreach ($this->delegateContainers as $delegate) {
                if ($instance = $delegate->get($service)) {
                    $this->injectDependencies($instance);
                    
                    return $instance;
                }
            }
            
            throw new ServiceNotFoundException(sprintf('Service reference "%s" matches no registered service in this factory or its delegate containers',
                $service), ServiceNotFoundException::UNREGISTERED_SERVICE_REFERENCE);
        }
        
        if (
            !$serviceSpecs->isStatic()
            || $this->getInstances()->lacks($service)
            || $params
        ) {
            $builder = $this->resolveBuilder($serviceSpecs);
            
            if (is_null($builder)) {
                throw new Exception(sprintf('No builder found to handle service specs (%s)', get_class($serviceSpecs)));
            }
            
            if ($builder instanceof ServicesFactoryAwareInterface) {
                $builder->setServicesFactory($this);
            }
            
            $instance = $builder->build($serviceSpecs, $params, $service);;
            
            $this->injectDependencies($instance, $serviceSpecs);
            
            
            if (!$serviceSpecs->isStatic() || $params) {
                // if params are passed, we don't store the instance for
                // further reference, even if the service is static
                return $instance;
            } else {
                $this->instances[$service] = $instance;
            }
            
        }
        
        return $this->instances[$service];
        
    }
    
    /**
     * @param string|ServiceReference $service
     *
     * @return bool
     */
    public function isServiceRegistered($service)
    {
        $service = $this->normalizeServiceId($service);
        
        $has = (bool)$this->getServiceSpecs($service);
        
        if (!$has) {
            foreach ($this->getDelegateContainers() as $container) {
                $has = $container->has($service);
                if ($has) {
                    break;
                }
            }
        }
        
        return $has;
        
    }

    /**
     * @param $instance
     * @param $serviceSpecs
     *
     * @return $this
     * @throws \ObjectivePHP\Primitives\Exception
     */
    public function injectDependencies($instance, $serviceSpecs = null)
    {
        if (is_object($instance)) {
            // call injectors if any
            $this->getInjectors()->each(function ($injector) use ($instance, $serviceSpecs) {
                $injector($instance, $this, $serviceSpecs);
            })
            ;

            if ($instance instanceof InjectionAnnotationProvider) {
                // automated injections
                $reflectedInstance   = new \ReflectionObject($instance);
                $reflectedProperties = $reflectedInstance->getProperties();

                foreach ($reflectedProperties as $reflectedProperty) {
                    $injection = $this->getAnnotationsReader()
                                      ->getPropertyAnnotation($reflectedProperty, Annotation\Inject::class)
                    ;
                    if ($injection) {
                        if ($injection->param) {
                            $dependency = $this->getConfigParamToInject($injection);
                        } else if ($injection->class || !$injection->service) {
                            $className = $injection->getDependency();

                            if (!$className) {
                                // use phpdocumentor to get var type
                                $docblock = DocBlockFactory::createInstance()->create($reflectedProperty);
                                if ($docblock->hasTag('var')) {
                                    $className = (string)$docblock->getTagsByName('var')[0]->getType()->getFqsen();
                                } else {
                                    throw new Exception('Undefined dependency. Use either dependency="<className>|<serviceName>" or "@var $property ClassName"',
                                        Exception::MISSING_DEPENDENCY_DEFINITION);
                                }
                            }

                            $dependency = new $className;
                            $this->injectDependencies($dependency);
                        } else {
                            $dependency = $this->getServiceToInject($injection->getDependency());
                        }

                        if ($injection->setter) {
                            $setter = $injection->setter;
                            $instance->$setter($dependency);
                        } else {
                            if (!$reflectedProperty->isPublic()) {
                                $reflectedProperty->setAccessible(true);
                            }

                            $reflectedProperty->setValue($instance, $dependency);

                            if (!$reflectedProperty->isPublic()) {
                                $reflectedProperty->setAccessible(false);
                            }
                        }
                    }
                }
            }
        }
        
        return $this;
    }

    /**
     * @param $serviceName
     * @return mixed|null
     * @throws Exception
     */
    protected function getServiceToInject($serviceName)
    {
        foreach ($this->parents as $parent) {
            if ($parent->has($serviceName)) {
                return $parent->get($serviceName);
            }
        }

        throw new Exception(sprintf('Dependent service "%s" is not registered', $serviceName),
            Exception::DEPENDENCY_NOT_FOUND);
    }

    /**
     * @throws Exception
     */
    protected function getConfigParamToInject($injection)
    {
        $hasConfig = false;
        foreach ($this->parents as $servicesFactory) {
            if ($servicesFactory->has('config')) {
                $hasConfig = true;

                $config = $servicesFactory->get('config');

                if ($config instanceof Config) {
                    $params = $config->subset('ObjectivePHP\Application\Config\Param');

                    if ($params->has($injection->param)) {
                        return $dependency = $params->get($injection->param);
                    } else {
                        if (isset($injection->default)) {
                            return $dependency = $injection->default;
                        }
                    }

                } else {
                    throw new Exception('Service registered as "config" in this factory is no a Config instance');
                }
            }
        }

        if (false === $hasConfig) {
            throw new Exception('No Config is registered as "config" neither in this factory nor in its parents');
        }

        throw new Exception(sprintf('Config instance registered as "config" does not have a "%s" param, and no default value is provided',
            $injection->param));
    }
    
    /**
     * @return Collection
     */
    public function getInstances()
    {
        return $this->instances;
    }
    
    /**
     * @param ServiceSpecsInterface $serviceSpecs
     *
     * @return null|ServiceBuilderInterface
     */
    public function resolveBuilder(ServiceSpecsInterface $serviceSpecs)
    {
        
        /** @var ServiceBuilderInterface $builder */
        foreach ($this->getBuilders() as $builder) {
            if ($builder->doesHandle($serviceSpecs)) {
                return $builder;
            }
        }
        
        return null;
    }
    
    /**
     * @return array
     */
    public function getDelegateContainers()
    {
        return $this->delegateContainers;
    }
    
    /**
     * @return Collection
     */
    public function getInjectors()
    {
        return $this->injectors;
    }
    
    /**
     * @return AnnotationReader
     */
    public function getAnnotationsReader()
    {
        return $this->annotationsReader;
    }
    
    /**
     * @param AnnotationReader $annotationsReader
     */
    public function setAnnotationsReader(AnnotationReader $annotationsReader)
    {
        $this->annotationsReader = $annotationsReader;
        
        return $this;
    }
    
    /**
     * @return Collection
     */
    public function getBuilders()
    {
        return $this->builders;
    }
    
    /**
     * @param $service
     *
     * @return string
     */
    protected function normalizeServiceId($service)
    {
        // normalize service id
        return strtolower(($service instanceof ServiceReference) ? $service->getId() : $service);
    }

    /**
     * Register one or multiple parent containers.
     *
     * @param ContainerInterface[] ...$containers
     *
     * @return $this
     */
    public function registerParentContainer(ContainerInterface ...$containers)
    {
        array_push($this->parents, ...$containers);

        return $this;
    }
}
