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

class ServiceDefinition extends AbstractMultiComplexDirective
{

    protected $key = 'services';

    protected $ignoreDefault = true;

    /** @var string */
    protected $id;

    /** @var array */
    protected $specifications = [];

    /**
     * ClassServiceConfig constructor.
     * @param array $parameters
     * @throws \ObjectivePHP\Config\Exception\ParamsProcessingException
     */
    public function __construct(array $parameters = [])
    {
        $this->hydrate(['specifications' => $parameters]);
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return ServiceDefinition
     */
    public function setKey(string $key): ServiceDefinition
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return array
     */
    public function getSpecifications(): array
    {
        return $this->specifications;
    }

    /**
     * @param array $specifications
     * @return ServiceDefinition
     */
    public function setSpecifications(array $specifications): ServiceDefinition
    {
        $this->specifications = $specifications;

        return $this;
    }

}
