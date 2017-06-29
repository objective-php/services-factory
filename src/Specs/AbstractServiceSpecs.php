<?php

namespace ObjectivePHP\ServicesFactory\Specs;

use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\Primitives\String\Str;
use ObjectivePHP\ServicesFactory\Exception\Exception;
use ObjectivePHP\ServicesFactory\Specs\UndefinedServiceSpecs;

abstract class AbstractServiceSpecs implements ServiceSpecsInterface
{

    /**
     * @var string
     */
    protected $id;

    /**
     * @var Collection
     */
    protected $aliases = [];

    /**
     * @var Collection
     */
    protected $params = [];

    /**
     * @var boolean
     */
    protected $static = true;

    /**
     * @var bool
     */
    protected $final = false;
    
    /**
     * @var bool
     */
    protected $autoAliasing = true;
    
    
    public function __construct($serviceId, $params = [])
    {
        // assign default values
        $this->setId($serviceId);
        
        foreach($params as $param => $value)
        {
            $paramParts = explode('-', strtolower($param));
            array_walk($paramParts, function(&$part) { $part = ucfirst($part);});
            $setter = implode($paramParts);
            $this->$setter($value);
        }
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (string) Str::cast($id);

        return $this;
    }

    /**
     * @return array
     */
    public function getAliases()
    {
        $aliases = $this->aliases;
        
        if($autoAlias = $this->getAutoAlias())
        {
            $aliases[] = $autoAlias;
        }
        
        return $aliases;
    }

    /**
     * @param Collection|array $aliases
     *
     * @return $this
     */
    public function setAliases($aliases)
    {
        $this->aliases = Collection::cast($aliases)->toArray();

        return $this;
    }

    

    /**
     * @return Collection
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param Collection|array $params
     *
     * @return $this
     */
    public function setParams($params)
    {
        $this->params = Collection::cast($params);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isStatic()
    {
        return $this->static;
    }

    /**
     * @param boolean $static
     *
     * @return $this
     */
    public function setStatic($static)
    {
        $this->static = (bool) $static;

        return $this;
    }

    static function factory($rawDefinition)
    {

        $rawDefinition = Collection::cast($rawDefinition);

        // first check an id has been provided
        if ($rawDefinition->lacks('id'))
        {
            throw new Exception('Missing mandatory \'id\' parameter in service definition', Exception::INCOMPLETE_SERVICE_SPECS);
        }

        // try to guess service type if not provided
        if($rawDefinition->lacks('type'))
        {
            $matchingTypes = [];

            foreach(['instance' => PrefabServiceSpecs::class, 'class' => ClassServiceSpecs::class, 'factory' => DelegatedFactorySpecs::class] as $key => $type)
            {
                if($rawDefinition->has($key)) $matchingTypes[] = $type;
            }

            if(!$matchingTypes)
            {
                // throw new Exception('The service specs factory has not been able to guess what type of service has been passed. Please check your syntax, or explicitly define the "type" key in your service specifications', Exception::INCOMPLETE_SERVICE_SPECS);
                // default to UndefinedService
                $matchingTypes[] = UndefinedServiceSpecs::class;
            }

            if(count($matchingTypes) > 1)
            {
                throw new Exception('Service specifications are ambiguous: they contain both "instance" and "class" key. Please remove the unneeded oneor explicitly define the "type" key in your service specifications ', Exception::AMBIGUOUS_SERVICE_SPECS);
            }

            // only one match
            $rawDefinition['type'] = array_pop($matchingTypes);
        }

        $serviceDefinition = call_user_func([$rawDefinition['type'], 'factory'], $rawDefinition);

        // static
        if ($rawDefinition->has('static'))
        {
            $serviceDefinition->setStatic($rawDefinition['static']);
        }

        // aliases
        if ($rawDefinition->has('alias') || $rawDefinition->has('aliases'))
        {
            $aliases = new Collection();
            if ($rawDefinition->has('alias')) $aliases[] = $rawDefinition['alias'];
            if ($rawDefinition->has('aliases')) $aliases->merge($rawDefinition['aliases']);

            $serviceDefinition->setAliases($aliases);
        }

        return $serviceDefinition;
    }

    /**
     * @return boolean
     */
    public function isFinal()
    {
        return $this->final;
    }

    /**
     * @param boolean $final
     *
     * @return $this
     */
    public function setFinal($final = true)
    {
        $this->final = (bool) $final;
        return $this;
    }
    
    /**
     * @return boolean
     */
    public function isAutoAliasingEnabled(): bool
    {
        return $this->autoAliasing;
    }
    
    /**
     * @return $this
     */
    public function enableAutoAliasing()
    {
        $this->autoAliasing = true;
        
        return $this;
    }
    
    
    /**
     * @return $this
     */
    public function disableAutoAliasing()
    {
        $this->autoAliasing = false;
        
        return $this;
    }
    
    protected function getAutoAlias()
    {
        return null;
    }

}
