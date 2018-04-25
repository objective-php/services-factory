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

class ServiceDefinition extends AbstractMultiComplexDirective implements IgnoreDefaultInterface
{
    
    const KEY = 'services';
    
    /** @var string */
    protected $id;
    
    /** @var array */
    protected $specifications = [];
    
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
     * @return array
     */
    public function getSpecifications(): array
    {
        return $this->specifications;
    }
    
    /**
     * @param array $specifications
     *
     * @return ServiceDefinition
     */
    public function setSpecifications(array $specifications): ServiceDefinition
    {
        $this->specifications = $specifications;
        
        return $this;
    }
    
    /**
     * @param $value
     *
     * @return $this
     */
    public function hydrate($data)
    {
        return parent::hydrate(['specifications' => $data]);
    }
}
