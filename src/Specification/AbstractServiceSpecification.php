<?php

namespace ObjectivePHP\ServicesFactory\Specification;

use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\Primitives\String\Camel;
use ObjectivePHP\Primitives\String\Str;
use ObjectivePHP\ServicesFactory\Exception\ServicesFactoryException;

abstract class AbstractServiceSpecification implements ServiceSpecificationInterface
{

    /**
     * @var string
     */
    protected $id;

    /**
     * @var array
     */
    protected $aliases = [];

    /**
     * @var boolean
     */
    protected $static = true;

    /**
     * @var bool
     */
    protected $final = false;

    /**
     * AbstractServiceSpecs constructor.
     * @param $serviceId
     * @param array $params
     */
    public function __construct($serviceId, $params = [])
    {
        // assign default values
        $this->setId($serviceId);

        foreach ($params as $param => $value) {

            $setter = 'set' . Camel::case($param);
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
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
        $this->id = (string)Str::cast($id);

        return $this;
    }

    /**
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
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
        $this->static = (bool)$static;

        return $this;
    }

    /**
     * @param $rawDefinition
     * @return mixed
     * @throws ServicesFactoryException
     */
    static function factory($rawDefinition)
    {

        $rawDefinition = Collection::cast($rawDefinition);

        // first check an id has been provided
        if ($rawDefinition->lacks('id')) {
            throw new ServicesFactoryException('Missing mandatory \'id\' parameter in service definition', ServicesFactoryException::INCOMPLETE_SERVICE_SPECS);
        }

        // try to guess service type if not provided
        if ($rawDefinition->lacks('type')) {
            $matchingTypes = [];

            foreach (['instance' => PrefabServiceSpecification::class, 'class' => ClassServiceSpecification::class, 'factory' => DelegatedFactorySpecification::class] as $key => $type) {
                if ($rawDefinition->has($key)) $matchingTypes[] = $type;
            }

            if (!$matchingTypes) {
                // throw new Exception('The service specs factory has not been able to guess what type of service has been passed. Please check your syntax, or explicitly define the "type" key in your service specifications', Exception::INCOMPLETE_SERVICE_SPECS);
                // default to UndefinedService
                throw new ServicesFactoryException('ServicesFactory was unable to guess service specification type');
            }

            if (count($matchingTypes) > 1) {
                throw new ServicesFactoryException('Service specifications are ambiguous: they contain both "instance" and "class" key. Please remove the unneeded oneor explicitly define the "type" key in your service specifications ', ServicesFactoryException::AMBIGUOUS_SERVICE_SPECS);
            }

            // only one match
            $rawDefinition['type'] = array_pop($matchingTypes);
        }

        $serviceDefinition = call_user_func([$rawDefinition['type'], 'factory'], $rawDefinition);

        // static
        if ($rawDefinition->has('static')) {
            $serviceDefinition->setStatic($rawDefinition['static']);
        }

        // aliases
        if ($rawDefinition->has('alias') || $rawDefinition->has('aliases')) {
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
        $this->final = (bool)$final;
        return $this;
    }

}
