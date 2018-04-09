<?php
/**
 * Created by PhpStorm.
 * User: gde
 * Date: 09/04/2018
 * Time: 13:38
 */

namespace ObjectivePHP\ServicesFactory\ParameterProcessor;


use ObjectivePHP\Config\ParameterProcessor\AbstractParameterProcessor;
use Psr\Container\ContainerInterface;

class ServiceReferenceParameterProcessor extends AbstractParameterProcessor
{
    /** @var string */
    protected $referenceKeyword = 'service';

    /** @var ContainerInterface */
    protected $servicesFactory;

    /**
     * @param $parameter
     */
    public function process($parameter)
    {
        $serviceId = $this->parseParameter($parameter);

        return $this->getServicesFactory()->get($serviceId);
    }

    /**
     * @return ContainerInterface
     */
    public function getServicesFactory(): ContainerInterface
    {
        return $this->servicesFactory;
    }

    /**
     * @param ContainerInterface $servicesFactory
     */
    public function setServicesFactory(ContainerInterface $servicesFactory)
    {
        $this->servicesFactory = $servicesFactory;

        return $this;
    }

}