<?php
/**
 * Created by PhpStorm.
 * User: gde
 * Date: 28/03/2018
 * Time: 14:05
 */

namespace ObjectivePHP\ServicesFactory\Injector;


use ObjectivePHP\ServicesFactory\ServicesFactory;
use ObjectivePHP\ServicesFactory\Specification\ServiceSpecificationInterface;

interface InjectorInterface
{
    public function injectDependencies($instance, ServicesFactory $servicesFactory, ServiceSpecificationInterface $serviceSpecification = null);
}