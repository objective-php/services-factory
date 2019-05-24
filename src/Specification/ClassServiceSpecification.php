<?php

namespace ObjectivePHP\ServicesFactory\Specification;

use ObjectivePHP\Primitives\Collection\Collection;
use ObjectivePHP\ServicesFactory\Exception\ServicesFactoryException;

/**
 * Class ClassServiceSpecification
 *
 * @package ObjectivePHP\ServicesFactory\Specification
 */
class ClassServiceSpecification extends AbstractServiceSpecification implements ClassServiceSpecificationInterface
{
    /**
     * @var string
     */
    protected $class;

    /** @var array */
    protected $constructorParams = [];

    /**
     * @var array
     */
    protected $setters = [];

    /**
     * ClassServiceSpecification constructor.
     *
     * @param string $id
     * @param string $class
     * @param array $params
     * @param array $setters
     */
    public function __construct($id, $class, $params = [], $setters = [])
    {
        parent::__construct($id);

        $this->setClass($class);
        $this->setConstructorParams($params);
        $this->setSetters($setters);

        $this->setAliases([$class]);
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
     *
     * @return ClassServiceSpecification
     *
     * @throws ServicesFactoryException
     */
    public static function factory($rawDefinition)
    {
        $rawDefinition = Collection::cast($rawDefinition);

        // then check check a class has been provided
        if (!$rawDefinition->has('class')) {
            throw new ServicesFactoryException(
                'Missing \'class\' parameter',
                ServicesFactoryException::INCOMPLETE_SERVICE_SPECS
            );
        }

        if (!is_string($class = $rawDefinition['class'])) {
            throw new ServicesFactoryException(
                '\'class\' parameter has to be a string',
                ServicesFactoryException::INVALID_SERVICE_SPECS
            );
        }

        $serviceDefinition = new ClassServiceSpecification($rawDefinition['id'], $class);

        // constructor params
        if ($rawDefinition->has('params')) {
            $serviceDefinition->setConstructorParams($rawDefinition['params']);
        }

        if ($rawDefinition->has('setters')) {
            $serviceDefinition->setSetters($rawDefinition['setters']);
        }

        return $serviceDefinition;
    }

    /**
     * @param Collection|array $setters
     *
     * @return $this
     */
    public function setSetters(array $setters)
    {
        $this->setters = $setters;

        return $this;
    }

    public function setConstructorParams($params)
    {
        $this->constructorParams = $params;
    }

    public function setClass(string $class)
    {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return array
     */
    public function getConstructorParams(): array
    {
        return $this->constructorParams;
    }

    /**
     * @return array
     */
    public function getSetters(): array
    {
        return $this->setters;
    }

    public function getAutoAliases()
    {
        $autoAliases = [$this->class];
        // do not fail if class is not yet available
        if (class_exists($this->class)) {
            $autoAliases = array_merge($autoAliases, class_implements($this->class));
        }
        return array_unique($autoAliases);
    }

}
