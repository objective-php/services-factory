<?php

namespace ObjectivePHP\ServicesFactory;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Interop\Container\ContainerInterface;
use ObjectivePHP\Config\Config;
use ObjectivePHP\Config\ConfigAccessorsTrait;
use ObjectivePHP\Config\ConfigAwareInterface;
use ObjectivePHP\Config\ConfigInterface;
use ObjectivePHP\Config\ConfigProviderInterface;
use ObjectivePHP\Matcher\Matcher;
use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\Primitives\String\Str;
use ObjectivePHP\ServicesFactory\Builder\ClassServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\DelegatedFactoryBuilder;
use ObjectivePHP\ServicesFactory\Builder\PrefabServiceBuilder;
use ObjectivePHP\ServicesFactory\Builder\ServiceBuilderInterface;
use ObjectivePHP\ServicesFactory\Exception\ServiceNotFoundException;
use ObjectivePHP\ServicesFactory\Exception\ServicesFactoryException;
use ObjectivePHP\ServicesFactory\Injector\AutowireInjector;
use ObjectivePHP\ServicesFactory\Injector\InjectorInterface;
use ObjectivePHP\ServicesFactory\Injector\ServicesFactoryAwareInjector;
use ObjectivePHP\ServicesFactory\ParameterProcessor\ServiceReferenceParameterProcessor;
use ObjectivePHP\ServicesFactory\Specification\AbstractServiceSpecification;
use ObjectivePHP\ServicesFactory\Specification\InjectionAnnotationProvider;
use ObjectivePHP\ServicesFactory\Specification\ServiceSpecificationInterface;
use phpDocumentor\Reflection\DocBlockFactory;

class ServicesFactory implements ContainerInterface, ConfigAwareInterface, ConfigProviderInterface
{

    use ConfigAccessorsTrait;

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
     * ServicesFactory constructor.
     */
    public function __construct()
    {
        // init collections
        $this->services = (new Collection())->restrictTo(ServiceSpecificationInterface::class);
        $this->builders = (new Collection())->restrictTo(ServiceBuilderInterface::class);
        $this->injectors = new Collection();
        $this->instances = new Collection();

        // register default annotation reader
        AnnotationRegistry::registerFile(__DIR__ . '/Annotation/Inject.php');
        $this->setAnnotationsReader(new AnnotationReader());
        $this->registerInjector(new ServicesFactoryAwareInjector());

        // load default builders
        $this->builders->append(new ClassServiceBuilder(), new PrefabServiceBuilder(), new DelegatedFactoryBuilder());
    }

    public function registerRawService($rawServiceSpecs)
    {
        $specs = AbstractServiceSpecification::factory($rawServiceSpecs);
        $this->registerService($specs);

        return $this;
    }

    /**
     *
     * @param array $servicesSpecs
     *
     * @return $this
     * @throws ServicesFactoryException
     */
    public function registerService(...$servicesSpecs)
    {

        foreach ($servicesSpecs as $serviceSpecs) {

            // if service specs is not an instance of ServiceSpecsInterface,
            // try to build the specs using factory
            if (!$serviceSpecs instanceof ServiceSpecificationInterface) {
                try {
                    $serviceSpecs = AbstractServiceSpecification::factory($serviceSpecs);
                } catch (\Exception $e) {
                    throw new ServicesFactoryException(AbstractServiceSpecification::class . '::factory() was unable to build service specifications',
                        ServicesFactoryException::INVALID_SERVICE_SPECS, $e);
                }
            }

            $serviceId = Str::cast($serviceSpecs->getId())->lower();

            // prevent final services from being overridden
            if ($previouslyRegistered = $this->getServiceSpecification((string)$serviceId)) {
                // a service with same name already has been registered
                if ($previouslyRegistered->isFinal()) {
                    // as it is marked as final, it cannot be overridden
                    throw new ServicesFactoryException(sprintf('Cannot override service "%s" as it has been registered as a final service',
                        $serviceId), ServicesFactoryException::FINAL_SERVICE_OVERRIDING_ATTEMPT);
                }
            }

            // store the service specs for further reference
            $this->services[(string)$serviceId] = $serviceSpecs;

            // register service's aliases
            $aliases = $serviceSpecs->getAliases() ?: [];
            foreach ($aliases as $alias) {

                if ($previouslyRegistered = $this->getServiceSpecification((string)$alias)) {
                    // a service with same name already has been registered
                    if ($previouslyRegistered->isFinal()) {
                        // as it is marked as final, it cannot be overridden
                        throw new ServicesFactoryException(sprintf('Cannot override service "%s" using alias "%s" as it has been registered as a final service',
                            $serviceId, $alias), ServicesFactoryException::FINAL_SERVICE_OVERRIDING_ATTEMPT);
                    }
                }

                $this->registeredAliases[$this->normalizeServiceId($alias)] = (string)$serviceId;
            }

            // register service's auto-aliases (with different overwriting rules than explicit aliases
            $autoAliases = $serviceSpecs->getAutoAliases();
            foreach ($autoAliases as $alias) {
                if ($previouslyRegistered = $this->getServiceSpecification((string)$alias)) {
                    if ($previouslyRegistered->isFinal()) {
                        continue;
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
     * @return ServiceSpecificationInterface
     */
    public function getServiceSpecification($service)
    {

        $service = $this->normalizeServiceId($service);

        $specs = $this->services[$service] ?? null;

        if (is_null($specs)) {
            if (isset($this->registeredAliases[$service])) {
                $service = $this->registeredAliases[$service];
                $specs = $this->services[$service] ?? null;
            }
        }

        // on the fly generated specs
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
        if ($builder instanceof ServicesFactoryAwareInterface) {
            $builder->setServicesFactory($this);
        }
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
     * @param $injector InjectorInterface
     *
     * @return $this
     */
    public function registerInjector(InjectorInterface $injector)
    {
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
     * Proxy for isServiceRegistered()
     *
     * This method ensures ContainerInterface compliance
     *
     * @param string $serviceId
     *
     * @return bool
     */
    public function has($serviceId)
    {
        return $this->isServiceRegistered($serviceId);
    }

    /**
     * @param            $serviceId string      Service ID or class name
     * @param array|null $params
     *
     * @return mixed|null
     * @throws ServicesFactoryException
     */
    public function get($serviceId, $params = [])
    {

        $serviceSpecification = $this->getServiceSpecification($serviceId);

        if (is_null($serviceSpecification)) {
            foreach ($this->delegateContainers as $delegate) {
                if ($instance = $delegate->get($serviceId)) {
                    $this->injectDependencies($instance);

                    return $instance;
                }
            }

            throw new ServiceNotFoundException(sprintf('Service reference "%s" matches no registered service in this factory or its delegate containers',
                $serviceId), ServiceNotFoundException::UNREGISTERED_SERVICE_REFERENCE);
        }

        // overwrite serviceId with service id from specs, in case serviceId was an alias
        $serviceId = $this->normalizeServiceId($serviceSpecification->getId());

        if (
            !$serviceSpecification->isStatic()
            || $this->getInstances()->lacks($serviceId)
            || $params
        ) {
            $builder = $this->resolveBuilder($serviceSpecification);

            if (is_null($builder)) {
                throw new ServicesFactoryException(sprintf('No builder found to handle service specs (%s)',
                    get_class($serviceSpecification)));
            }

            if ($builder instanceof ServicesFactoryAwareInterface) {
                $builder->setServicesFactory($this);
            }

            $instance = $builder->build($serviceSpecification, $params, $serviceId);;

            $this->injectDependencies($instance, $serviceSpecification);


            if (!$serviceSpecification->isStatic() || $params) {
                // if params are passed, we don't store the instance for
                // further reference, even if the service is static
                return $instance;
            } else {
                $this->instances[$serviceId] = $instance;
            }

        }

        return $this->instances[$serviceId];

    }

    /**
     * @param string $service
     *
     * @return bool
     */
    public function isServiceRegistered($service)
    {
        $service = $this->normalizeServiceId($service);

        $has = (bool)$this->getServiceSpecification($service);

        if (!$has) {
            foreach ($this->getDelegateContainers() as $container) {
                $has = $container->has($service);
                if ($has) {
                    break;
                }
            }

            // before returning false, check if the service id does match an existing class name
            if (class_exists($service)) {
                $this->registerService(['id' => $service, 'class' => $service]);
                $has = true;
            }

        }

        return $has;

    }

    /**
     * @param $instance
     * @param $serviceSpecification
     *
     * @return $this
     */
    public function injectDependencies($instance, $serviceSpecification = null)
    {
        if (is_object($instance)) {
            // call injectors if any
            $this->getInjectors()->each(function (InjectorInterface $injector) use ($instance, $serviceSpecification) {
                $injector->injectDependencies($instance, $this, $serviceSpecification);
            });

            if ($instance instanceof InjectionAnnotationProvider) {
                // automated injections
                $reflectedInstance = new \ReflectionObject($instance);
                $reflectedProperties = $reflectedInstance->getProperties();

                foreach ($reflectedProperties as $reflectedProperty) {
                    $injectionAnnotation = $this->getAnnotationsReader()
                        ->getPropertyAnnotation($reflectedProperty, Annotation\Inject::class);
                    if ($injectionAnnotation) {
                        if ($injectionAnnotation->param) {
                            if ($this->has('config')) {
                                $config = $this->get('config');

                                if ($config instanceof Config) {

                                    if ($config->has($injectionAnnotation->param)) {
                                        $dependency = $config->get($injectionAnnotation->param);
                                    } else {
                                        if (isset($injectionAnnotation->default)) {
                                            $dependency = $injectionAnnotation->default;
                                        } else {
                                            throw new ServicesFactoryException(sprintf('Config instance registered as "config" does not have a "%s" param, and no default value is provided',
                                                $injectionAnnotation->param));
                                        }
                                    }

                                } else {
                                    throw new ServicesFactoryException('Service registered as "config" in this factory is no a Config instance');
                                }
                            } else {
                                throw new ServicesFactoryException('No Config is registered as "config" in this factory');
                            }
                        } else {
                            if ($injectionAnnotation->class || !$injectionAnnotation->service) {

                                $className = $injectionAnnotation->getDependency();
                                if (!$className) {
                                    // use phpdocumentor to get var type
                                    $docblock = DocBlockFactory::createInstance()->create($reflectedProperty);
                                    if ($docblock->hasTag('var')) {
                                        $className = (string)$docblock->getTagsByName('var')[0]->getType()->getFqsen();
                                    } else {
                                        throw new ServicesFactoryException('Undefined dependency. Use either dependency="<className>|<serviceName>" or "@var $property ClassName"',
                                            ServicesFactoryException::MISSING_DEPENDENCY_DEFINITION);
                                    }
                                }

                                $dependency = new $className;
                                $this->injectDependencies($dependency);
                            } else {
                                $serviceName = $injectionAnnotation->getDependency();
                                if (!$this->has($serviceName)) {
                                    throw new ServicesFactoryException(sprintf('Dependent service "%s" is not registered',
                                        $serviceName),
                                        ServicesFactoryException::DEPENDENCY_NOT_FOUND);
                                }
                                $dependency = $this->get($serviceName);
                            }
                        }

                        if ($injectionAnnotation->setter) {
                            $setter = $injectionAnnotation->setter;
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
     * @return Collection
     */
    public function getInstances()
    {
        return $this->instances;
    }

    /**
     * @param ServiceSpecificationInterface $serviceSpecs
     *
     * @return null|ServiceBuilderInterface
     */
    public function resolveBuilder(ServiceSpecificationInterface $serviceSpecs)
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
        return strtolower($service);
    }

    /**
     * @param Config $config
     */
    public function setConfig(ConfigInterface $config)
    {
        // inject service parameter processor
        $config->registerParameterProcessor((new ServiceReferenceParameterProcessor())->setServicesFactory($this));

        $this->config = $config;

        return $this;
    }

}
