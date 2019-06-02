<?php

/**
 * This file is part of the Objective PHP project
 *
 * More info about Objective PHP on www.objective-php.org
 *
 * @license http://opensource.org/licenses/GPL-3.0 GNU GPL License 3.0
 */

namespace ObjectivePHP\ServicesFactory\Config;

use ObjectivePHP\Config\Directive\AbstractMultiComplexDirective;
use ObjectivePHP\Config\Directive\IgnoreDefaultInterface;

/**
 * Class ServiceDefinition
 *
 *
 *
 * @package ObjectivePHP\ServicesFactory\Config
 */
class ServiceDefinition extends AbstractMultiComplexDirective implements IgnoreDefaultInterface
{
    const KEY = 'services';

    /**
     * @config-example-reference "service.id"
     */
    protected $reference;


    /**
     * Service reference
     *
     * This id can be used to override the default reference. This is usually neither necessary to fill it, nor a good
     * practice.
     *
     * @config-attribute
     * @config-example-value "my.service.id"
     * @var string
     *
     */
    protected $id;


    /**
     * Service class name
     *
     * Define here the FQCN of the class to instantiate to build the service.
     *
     * @config-attribute
     * @config-example-value "Fully\\Qualified\\Class\\Name"
     * @var string
     */
    protected $class;

    /**
     * Constructor parameters
     *
     * Array of parameters to pass to the service constructor. If none provided, the ServiceFactory will attempt to autowire the class.
     *
     * @config-attribute
     * @config-example-value array("constructor", "params")
     * @var array
     */
    protected $params = [];

    /**
     * Dependency injection using setters
     *
     * You can provide the ServicesFactory with an array of setters to be called after service instantiation
     *
     * @config-attribute
     * @config-example-value {"setDependency":"service(dependency.id)"}
     * @var array
     */
    protected $setters = [];


    /**
     * Set static flag
     *
     * Services are static by default, meaning that whe you get several time the same service, the very same object
     * is returned. If you want ServicesFactory to build new instances each time you get the service, set the the
     * static flag to false.
     *
     * @config-attribute
     * @var bool
     */
    protected $static = true;

    /**
     * Service aliases
     *
     * You can alias a service using any string. The most common use case is to
     * alias a service using an interface name in order to make it available for
     * autowiring
     *
     * @config-attribute
     * @config-example-value array("Package\\ComponentInterface")
     *
     * @var array
     */
    protected $aliases = [];

    /**
     * ClassServiceConfig constructor.
     *
     * @param array $parameters
     *
     * @throws \ObjectivePHP\Config\Exception\ParamsProcessingException
     */
    public function __construct(array $parameters = [])
    {
        $this->hydrate($parameters);
    }

    /**
     * Get Id
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set Id
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId(string $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get Class
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Set Class
     *
     * @param string $class
     *
     * @return $this
     */
    public function setClass(string $class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get Params
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Set Params
     *
     * @param array $params
     *
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get Setters
     *
     * @return array
     */
    public function getSetters(): array
    {
        return $this->setters;
    }

    /**
     * Set Setters
     *
     * @param array $setters
     *
     * @return $this
     */
    public function setSetters(array $setters)
    {
        $this->setters = $setters;

        return $this;
    }

    /**
     * Get Static
     *
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->static;
    }

    /**
     * Set Static
     *
     * @param bool $static
     *
     * @return $this
     */
    public function setStatic(bool $static)
    {
        $this->static = $static;

        return $this;
    }

    /**
     * Get Alias
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Set Alias
     *
     * @param string[] $aliases
     *
     * @return $this
     */
    public function setAliases(array $aliases)
    {
        $this->aliases = $aliases;

        return $this;
    }

    /**
     * Returns the service specification
     *
     * @return array
     */
    public function getSpecifications(): array
    {
        return [
            'class' => $this->getClass(),
            'params' => $this->getParams(),
            'setters' => $this->getSetters(),
            'static' => $this->isStatic(),
            'aliases' => $this->getAliases()
        ];
    }
}
