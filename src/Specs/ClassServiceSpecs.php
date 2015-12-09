<?php

namespace ObjectivePHP\ServicesFactory\Specs;


use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\Primitives\String\Str;
use ObjectivePHP\ServicesFactory\Exception\Exception;

class ClassServiceSpecs extends AbstractServiceSpecs
{

    /**
     * @var Str
     */
    protected $class;


    /**
     * @var Collection $setters
     */
    protected $setters;

    /**
     * @param $id
     * @param $class
     */
    public function __construct($id, $class)
    {
        parent::__construct($id);

        $this->setters = new Collection();

        $this->setClass($class);
    }

    /**
     * Service definition factory
     *
     * IT IS NOT RECOMMENDED TO CALL THIS METHOD EXPLICITLY
     *
     * Please call AbstractServiceSpecs::factory(), that will
     * forward to the appropriate factory after having performed
     * basic sanity checks ('id' presence)
     *
     * @param array|Collection $rawDefinition
     * @throws Exception
     */
    static public function factory($rawDefinition)
    {

        $rawDefinition = Collection::cast($rawDefinition);

        // then check check a class has been provided
        if (!$rawDefinition->has('class'))
        {
            throw new Exception('Missing \'class\' parameter', Exception::INCOMPLETE_SERVICE_SPECS);
        }

        if (!is_string($class = $rawDefinition['class']))
        {
            throw new Exception('\'class\' parameter has to be a string', Exception::INVALID_SERVICE_SPECS);
        }

        $serviceDefinition = new ClassServiceSpecs($rawDefinition['id'], $class);

        // constructor params
        if ($rawDefinition->has('params'))
        {
            $serviceDefinition->setParams($rawDefinition['params']);
        }

        // setters
        if ($rawDefinition->has('setters'))
        {
            $serviceDefinition->setSetters($rawDefinition['setters']);
        }

        return $serviceDefinition;
    }

    /**
     * @return Collection
     */
    public function getSetters()
    {
        return $this->setters;
    }

    /**
     * @param Collection|array $setters
     *
     * @return $this
     */
    public function setSetters($setters)
    {
        $this->setters = Collection::cast($setters);

        return $this;
    }


}