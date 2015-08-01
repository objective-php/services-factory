<?php

namespace ObjectivePHP\ServicesFactory\Definition;


use ObjectivePHP\Primitives\Collection;
use ObjectivePHP\ServicesFactory\Exception;

class ClassServiceDefinition extends AbstractServiceDefinition
{

    /**
     * @param $id
     * @param $class
     */
    public function __construct($id, $class)
    {
        parent::__construct($id);

        $this->setClass($class);
    }

    /**
     * Service definition factory
     *
     * IT IS NOT RECOMMENDED TO CALL THIS METHOD EXPLICITLY
     *
     * Please call AbstractServiceDefinition::factory(), that will
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
            throw new Exception('Missing \'class\' parameter', Exception::INCOMPLETE_SERVICE_DEFINITION);
        }

        if(!is_string($class = $rawDefinition['class']))
        {
            throw new Exception('\'class\' parameter has to be a string', Exception::INVALID_SERVICE_DEFINITION);
        }

        $serviceDefinition = new ClassServiceDefinition($rawDefinition['id'], $class);

        // params
        if ($rawDefinition->has('params'))
        {
            $serviceDefinition->setParams($rawDefinition['params']);
        }

        return $serviceDefinition;
    }

}