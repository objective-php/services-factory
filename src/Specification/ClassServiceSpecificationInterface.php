<?php

namespace ObjectivePHP\ServicesFactory\Specification;


/**
 * Class ClassServiceSpecification
 *
 * @package ObjectivePHP\ServicesFactory\Specification
 */
interface ClassServiceSpecificationInterface
{
    public function setConstructorParams($params);

    public function setClass(string $class);

    /**
     * @return string
     */
    public function getClass(): string;

    /**
     * @return array
     */
    public function getConstructorParams(): array;
}
